<?php

namespace app\models\scrape;

use Goutte\Client;

use Symfony\Component\DomCrawler\Crawler;

use yii\base\Component;

/**
 * WJModel is the model that scrape worldjournal.com ads
 * 
 */
class WJModel extends Component {
	/**
	 * @var string    
	 */
	private $_hostname;

	/**
	 * @var string 	Ajax request Url  
	 */	
	private $_requestUrl;

	/**
	 * @var string 	language   
	 */	
	private $_wjlang;

	/**
	 * @var integer  Ad Category Id
	 */	
	private $_currentCatId;

	/**
	 * @var string 	Current Category name;   
	 */		
	private $_currentCatName;

	/**
	 * @var string 	Current Region name;   
	 */		
	private $_currentRegionName;

	/**
	 * @var integer  Ad State Id
	 */	
	private $_currentStateId;

	/**
	 * @var integer  Ad numbers to fetch per ajax call
	 */	
	private $_pageSize;

	/**
	 * @var array 	ajax query data options 
	 */	
	private $_optionVaules;

	/**
	 * @var array
	 */
	private $_requestHeader;

	/**
	 * @var Goutte\Client 	    
	 */
	private $_client;

	/**
	 * @var DomCrawler\Crawler
	 */
	private $_crawler;

	public function init(){
		parent::init();

		$this->_hostname = 'http://www.wjlife.com';

		$this->_wjlang = "zh-cn"; 

		$this->_currentRegionName = "state_ny";

		$this->_requestUrl = $this->_hostname
			. "/wp-content/themes/wjlife/includes/classified-core.php?regions="
			. $this->_currentRegionName
			. "&variant=" 
			. $this->_wjlang
			. "&t=" . time();

		// fetch first 100 ad links
		$this->_pageSize = 100;

		$this->_optionVaules = [
			"relation" => "AND",
			"0" => [
				"relation" => "AND",
				"0" => [
					"key" => "wj_order_id"
				]
			]
		];

        $this->_requestHeader = [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            'contentType' => 'application/x-www-form-urlencoded;charset=utf-8',
        ];

        $this->_currentStateId = 327; //restaurant help, hardcoded in their js code
        $this->_currentCatName = 'restaurant help'; 
        $this->_currentRegionName = 152;

        $this->setClient();

        $this->setCrawler();
	}

	public function setClient() {
		$this->_client = new Client();
	}

	public function getClient() {
		return $this->_client;
	}

	public function setCrawler() {
		$this->_crawler = new Crawler();
	}

	public function getCrawler() {
		return $this->_crawler;
	}

	public function fetchAdData() {
		$adLinks = $this->fetchAdLinksFromAdCategoryAjaxCall();

		return $this->fetchAdContentsFromAdLinks( $adLinks );
	}

	// Step1: fetch currentCatId's ajax call data, and filter out list of ads
	public function fetchAdLinksFromAdCategoryAjaxCall() {
		$queryObject = [
            "keyword" => "",
            "pagesize" => $this->_pageSize, //specify how many rows you want to pull each request
            "pno" => 0, // only need fetch the first page
            "optionVaules" => $this->_optionVaules, 
            "currentURL" => $this->_hostname, // . $currentURL,
            "currentCatId" => $this->_currentStateId, //restaurant help, hardcoded in their js code
            "currentStateId" => $this->_currentRegionName,
        ];

        $crawler = $this->getClient()->request( "POST", $this->_requestUrl,  $queryObject , [], $this->_requestHeader );

        $rowHtml = $crawler->html();

        return $this->fetchAdLinksFromAdCategoryContent( $rowHtml );
	}

	/**
	 * fetchAdLinksFromAdCategoryContent fetch the ad links from a html content.
	 * @param  string   HTML content
	 * @return array    ad itemlinks
	 */
	protected function fetchAdLinksFromAdCategoryContent( $rowHtml ) {
		$crawler = $this->getCrawler();
        $crawler->addHtmlContent($rowHtml);

		return $crawler->filter(".catDesc a")->each( function( $node, $index ){
            return $href = $node->attr('href');
        } );
	}

	// Step2: fetch each item's link content and filter out ad content, 
	/**
	 * fetchAdContentsFromAdLinks description]
	 * @param  array $adLinks 
	 * @return array array of post data
	 */
	public function fetchAdContentsFromAdLinks( $adLinks ) {
		$posts = array();

		foreach ( $this->generateAdLinks( $adLinks ) as $adlink) {
			echo "adLink: ".$adlink. "\n";
	        $posts[] = $this->fetchPostDataFromAdContent( $adlink );
		}
		
		return $posts;
	}

	/**
	 * generateAdLinks uses php 5.6 feature "generator" to loop through array.
	 * it also sleep between requests to prevent continuous requests from being blocked. 
	 * @param  [type] $adLinks [description]
	 * @return [type]          [description]
	 */
	protected function generateAdLinks( $adLinks ) {
		$length = count( $adLinks );
		for( $i = 0; $i < $length; $i++ ){
			usleep(20000); // sleep 0.02 seconds between requests
			yield $adLinks[$i];
		}
	}

	/**
	 * fetchPostDataFromAdContent crawl link data and save ad data to post array
	 * @param  string $adlink 
	 * @return array  post data
	 */
	protected function fetchPostDataFromAdContent( $adlink ) {
		// add languague preference to link
		$adlink = $adlink . "?variant=" . $this->_wjlang;

		$crawler = $this->getClient()->request( 'GET', $adlink );

		$post = array();

		// get title:
		$title = $crawler->filter(".classifiedTitle h4")->text();
		$post['title'] = trim( $title );

		$rawContent = $crawler->filter(".classifiedDetails")->text();
		$contentArr = explode( "\n", trim( $rawContent ) );

		// get location:
		$location = $this->findAdLocation( $contentArr[ 0 ] );
		$post[ 'location' ] = $location;

		// get content:
		$post[ 'content' ] = trim( $contentArr[ 1 ] );

		// get website:
		$post[ 'website' ] = $this->_hostname;

		// get section:
        $post[ 'section' ] = $this->_currentCatName;

		return $post;
	}

	/**
	 * findAdLocation uses pattern to mach string like "地区: 纽约上州 / Upstate NY",
	 * and return location string "纽约上州 / Upstate NY"
	 * 
	 * @param  string $content 
	 * @return string          location string
	 */
    protected function findAdLocation( $content ) {
    	
        $pattern = "/\:(.+)/"; 

        if( preg_match($pattern, $content, $matches) ) {
            return trim( $matches[ 1 ] );
        }

        return null;
    }
}

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
	public $_hostname;

	/**
	 * @var string 	Ajax request Url  
	 */	
	public $_requestUrl;

	/**
	 * @var string 	language   
	 */	
	public $_wjlang;

	/**
	 * @var integer  Ad Category Id
	 */	
	public $_currentCatId;

	/**
	 * @var string 	Current Category name;   
	 */		
	public $_currentCatName;

	/**
	 * @var string 	Current Region name;   
	 */		
	public $_currentRegionName;

	/**
	 * @var integer  Ad State Id
	 */	
	public $_currentStateId;

	/**
	 * @var integer  Ad numbers to fetch per ajax call
	 */	
	public $_pageSize;

	/**
	 * @var array 	ajax query data options 
	 */	
	public $_optionVaules;

	/**
	 * @var array
	 */
	public $_requestHeader;

	/**
	 * @var Goutte\Client 	    
	 */
	public $_client;

	/**
	 * @var DomCrawler\Crawler
	 */
	public $_crawler;

	public function init(){
		parent::init();

        $this->setClient();

        $this->setCrawler();

		/**
		 *  The following settings are copied from worldjournal ajax request
		 */

		$this->_requestUrl = $this->_hostname
			. "/wp-content/themes/wjlife/includes/classified-core.php"
			. "?regions=". $this->_currentRegionName
			. "&variant=". $this->_wjlang
			. "&t=" . time();

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
            "currentURL" => $this->_hostname, 
            "currentCatId" => $this->_currentCatId, 
            "currentStateId" => $this->_currentStateId,
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
	public function fetchPostDataFromAdContent( $adlink ) {
		// add languague preference to link
		$adlink = $adlink . "?variant=" . $this->_wjlang;

		$crawler = $this->getClient()->request( 'GET', $adlink );

		$post = array();

		// get title:
		$title = $crawler->filter(".classifiedTitle h4")->text();
		$post['title'] = trim( $title );

		// get website:
		$shortUrl = $crawler->filter("#qr_code")->attr("data-url");
		$post[ 'website' ] = $shortUrl;

		$rawContent = $crawler->filter(".classifiedDetails")->text();
		$contentArr = explode( "\n", trim( $rawContent ) );

		// get location:
		$location = $this->findAdLocation( $contentArr[ 0 ] );
		$post[ 'location' ] = $location;

		// get content:
		$post[ 'content' ] = trim( $contentArr[ 1 ] );

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

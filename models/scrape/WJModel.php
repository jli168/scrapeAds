<?php

namespace app\models\scrape;

use Goutte\Client;

use Symfony\Component\DomCrawler\Crawler;

use yii\base\Component;

use Yii;

/**
 * WJModel is the model that scrape worldjournal.com ads
 * 
 */
class WJModel extends BaseModel {
	/**
	 * @var string    
	 */
	public $_hostname;

	/**
	 * @var string 	Ajax request Url  
	 */	
	public $_requestUrl;

	/**
	 * @var string Endpoint
	 */
	public $_endpoint;

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
	 * @var integer  Due to wj's unpredictable ad listing order, 
	 * we only stop crawling when we find $_dupAdCount number of consective already-crawled links.
	 */
	public $_dupAdCount;

	public function init(){
		parent::init();

		/**
		 *  The following settings are copied from worldjournal ajax request
		 */
		$this->_requestUrl = $this->_hostname
			. $this->_endpoint
			. "?regions=". $this->_currentRegionName
			. "&variant=". $this->_wjlang
			. "&t=" . time();

		var_dump( "request Url is ". $this->_requestUrl );

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

	public function fetchAdData() {
		echo "Fetch WJ feature ads and regualr ads. ".PHP_EOL;
		
		$adLinks = $this->fetchAdLinksFromAdCategoryAjaxCall();

		return array_merge(
			$this->fetchAdContentsFromAdLinks( $adLinks['featureUrls'] ),
			$this->fetchAdContentsFromAdLinks( $adLinks['regularUrls'] )
		);
	}

	/**
	 * fetchAdContentsFromAdLinks: fetch all newly found ads and return those ads data in array.
	 * Due to wj's unpredictable new ad listing order, we only stop crawling when we find 
	 * $_dupAdCount number of consective already crawled ads.
	 * 
	 * @param  array $adLinks 
	 * 
	 * @return array array of post data
	 */
	public function fetchAdContentsFromAdLinks( $adLinks ) {
		$posts = array();

		$adCrawled = 0;

		echo PHP_EOL;

		foreach ( $this->generateAdLinks( $adLinks ) as $adlink) {
			echo "adLink: ".$adlink. PHP_EOL;

			$post = $this->fetchAdContentFromAdLink ( $adlink );

			if( $this->isAdLinkCrawled( $post['website'] ) ) {
				echo "this link is already fetched." . PHP_EOL;
				$adCrawled++;

				if($adCrawled === $this->_dupAdCount){
					break;
				}
			} else {
				echo "add it!" . PHP_EOL;
				$adCrawled = 0; // Reset & recount

		        $posts[] = $post;
			}
		}

		return $posts;
	}

	/**
	 * fetchAdLinksFromAdCategoryAjaxCall fetch ad links from current ad category by ajax call
	 * 
	 * @return multi-dimensional array    Array of featureUrls array and regularUrls array
	 */
	public function fetchAdLinksFromAdCategoryAjaxCall() {
		$queryObject = [
            "keyword" => "",
            "pagesize" => $this->_pageSize, //specify how many rows you want to pull each request
            "pno" => 1, // only need fetch the first page, and pno index start from 1
            "optionVaules" => $this->_optionVaules, 
            "currentURL" => $this->_hostname, 
            "currentCatId" => $this->_currentCatId, 
            "currentStateId" => $this->_currentStateId,
        ];

        $crawler = $this->getClient()->request( "POST", $this->_requestUrl,  $queryObject , [], $this->_requestHeader );

        return $this->fetchAdLinksFromAdCategoryCrawler( $crawler );
	}

	/**
	 * fetchAdLinksFromAdCategoryCrawler fetch the ad links from the crawler 
	 * which has the complete content fetched from ajax call.
	 *
	 * one charater of wj ad list page is that, it has some feature ads with images always on top,
	 * so if we split ad list into "feature ads" and "regular ads" and crawl separately, 
	 * it will be more efficient to decide which ads we've crawled already.
	 * 
	 * @param  Symfony\Component\DomCrawler\Crawler   $cralwer
	 * 
	 * @return multi-dimensional array    Array of featureUrls array and regularUrls array
	 */
	protected function fetchAdLinksFromAdCategoryCrawler( $crawler ) {
		$urls = array(
			"featureUrls" => array(),
			"regularUrls" => array(),
		);

		$crawler->filter(".product .frame a")->each( function( $node, $index ) use ( &$urls ){
			$href = $this->normalizeUrl( $node->attr( 'href' ) );

        	if ( count( $node->filter( ".image" ) ) ) {
        		$urls[ "featureUrls" ][] = $href;
        	} else {
        		$urls[ "regularUrls" ][] = $href;
        	}
        } );

		return $urls;
	}

	/**
	 * fetchAdContentFromAdLink crawl link data and save ad data to post array
	 * @param  string $adlink
	 * @return array  post data
	 */
	public function fetchAdContentFromAdLink( $adlink ) {
		$crawler = $this->getClient()->request( 'GET', $adlink );

		$post = array();

		// get title:
		$post['title'] = trim( utf8_decode( $crawler->filter(".title")->text() ) );

		// get phone number as location:
		$infoLink = $crawler->filter( ".infolink");
		$post[ "location" ] = count( $infoLink ) ? $infoLink->attr( "href" ) : "";

		// get content:
		$post[ "content" ] = utf8_decode( $crawler->filter(".details-holder .text")->text() );

		// get website:
        $post[ 'website' ] = $this->_hostname.$adlink;

		// get section:
        $post[ 'section' ] = $this->_currentCatName;

		return $post;
	}

	// fix wjlife bug of unable to change variant
	protected function normalizeUrl( $href ) {
		return str_replace( "zh-tw", $this->_wjlang, $href);
	}
}

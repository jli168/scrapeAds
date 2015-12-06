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

	public function init(){
		parent::init();

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

	public function fetchAdData() {
		echo "Fetch WJ feature ads and regualr ads. ".PHP_EOL;
		
		$adLinks = $this->fetchAdLinksFromAdCategoryAjaxCall();

		return array_merge( 
			$this->fetchAdContentsFromAdLinks( $adLinks['featureUrls'] ), 
			$this->fetchAdContentsFromAdLinks( $adLinks['regularUrls'] )
		);
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
        // feature urls
        $featureUrls = $crawler->filter(".catImg a")->each( function( $node, $index ){
            return $node->attr('href');
        } );

        // all urls
        $allUrls = $crawler->filter(".catDesc a")->each( function( $node, $index ){
            return $node->attr('href');
        } );

		// regular urls are urls that are not feature urls
		// here, we use array_values to reindex the array, so that it can feeds php generator correctly!
        $regularUrls = array_values( array_diff( $allUrls, $featureUrls ) );

 		return array(
 			'featureUrls' => $featureUrls,
 			'regularUrls' => $regularUrls,
 		);
	}

	/**
	 * fetchAdContentFromAdLink crawl link data and save ad data to post array
	 * @param  string $adlink 
	 * @return array  post data
	 */
	public function fetchAdContentFromAdLink( $adlink ) {
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

    public function isAdLinkCrawled ( $adlink ) {
    	$adData = $this->fetchAdContentFromAdLink ( $adlink );

    	$website = $adData[ 'website' ];

    	return parent::isAdLinkCrawled( $website );
    }
}

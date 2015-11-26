<?php

namespace app\models\scrape;

use Goutte\Client;

use Symfony\Component\DomCrawler\Crawler;

use yii\base\Component;

use Yii;

/**
 * CLModel is the model that scrape newyork.craiglist.org ads
 * 
 */
class CLModel extends Component {

	public $_hostname;

	/**
	 * @var string 
	 */
	public $_sectionName;

	/**
	 * @var string  Url Endpoint for the section. 
	 * Example: section "software" has endpoin "search/sof"
	 */
	public $_sectionEndpoint;

	/**
	 * @var string 
	 */
	public $_location;

	/**
	 * @var int  Number of ad links to fetch each time
	 */
	public $_linkCount;

	/**
	 * @var Goutte\Client 	    
	 */
	public $_client;

	/**
	 * @var DomCrawler\Crawler
	 */
	public $_crawler;

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

	public function init(){
		parent::init();

        $this->setClient();

        $this->setCrawler();
    }

	public function fetchAdData() {
		$adLinks = $this->fetchAdLinksFromSection();

		$adLinks = array_slice($adLinks, 0, $this->_linkCount);
		return $this->fetchAdContentsFromAdLinks( $adLinks );
	}

    /**
     * fetchAdLinksFromSection fetch ad links from current section
     * 
     * @return array   Ad links
     */
    public function fetchAdLinksFromSection(){
    	$requestUrl = $this->_hostname . $this->_sectionEndpoint;

     	$crawler = $this->getClient()->request( 'GET', $requestUrl );

		$titleLinkFilter = ".hdrlnk";

		return $crawler->filter( $titleLinkFilter )->each( function( $node, $index ) {
			return $this->_hostname . $node->attr("href");
		} );
    }

	/**
	 * fetchAdContentsFromAdLinks description]
	 * @param  array $adLinks 
	 * @return array array of post data
	 */
	public function fetchAdContentsFromAdLinks( $adLinks ) {
		$posts = array();

		foreach ( $this->generateAdLinks( $adLinks ) as $adlink) {
			echo "adLink: ".$adlink. "\n";
	        $posts[] = $this->fetchAdContentFromAdLink( $adlink );
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
     * fetchAdContentFromAdLink crawl $adlink and fetch ad content,
     * returns ad content as an array
     * @param  [string] $requestUrl  Ad link
     * @return [array]     ad content
     */
    public function fetchAdContentFromAdLink( $requestUrl ){
     	$crawler = $this->getClient()->request( 'GET', $requestUrl );

		$postContentFilter = "#postingbody";

		$data = [];
		$data[ 'title' ] = $crawler->filter(".postingtitletext")->text();
		$data[ 'content' ] = $crawler->filter( $postContentFilter )->text();

		// compensation and employment type info
		$comp = $crawler->filter(".attrgroup > span")->each(function($node){
			return $node->text();
		});

		$data[ 'content' ] .= "\n" . ucwords(implode("\n", $comp));

		return $data;		
    }

}
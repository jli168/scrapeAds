<?php

namespace app\models\scrape;

use Goutte\Client;

use Symfony\Component\DomCrawler\Crawler;

use yii\base\Component;

/**
 * BaseModel provides common properties and functions for all crawling models
 */
abstract class BaseModel extends Component {
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

	/**
	 * fetchAdData will be overriden by subclasses
	 * 
	 * @return array   Ad data
	 */
	abstract public function fetchAdData();
	
	/**
	 * fetchAdContentFromAdLink will be overriden by subclasses
	 * @param  string $adlink 
	 * 
	 * @return  array  Ad content
	 */
	abstract protected function fetchAdContentFromAdLink( $adlink );
	
	/**
	 * fetchAdContentsFromAdLinks description]
	 * @param  array $adLinks 
	 * @return array array of post data
	 */
	public function fetchAdContentsFromAdLinks( $adLinks ) {
		$posts = array();

		foreach ( $this->generateAdLinks( $adLinks ) as $adlink) {
			echo "adLink: ".$adlink. "\n";
			if( $this->isCrawled( $adlink ) ) {
				echo "this link is already fetched...";
				break;
			}
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
	 * isCrawled return true if $adlink is already in Post database table's 'website' column. 
	 * it can be overriden if subclass does not store adlink there.
	 *
	 * @param  string  $adlink  
	 * @return boolean         
	 */
	public function isCrawled( $adlink ) {
		$ad = Post::findOne( [
			'website' => $adlink,
		] );

		return $ad !== null;
	}

}
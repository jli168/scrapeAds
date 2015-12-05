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
	 * @var integer  If there are $_existedLinkCount links already fetched, we can stop continuing. 
	 */
	public $_existedLinkCount = 2;

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
		echo "fetch !!!". PHP_EOL;
		$posts = array();

		$alreadyFetched = 0;

		foreach ( $this->generateAdLinks( $adLinks ) as $adlink) {
			echo "adLink: ".$adlink. PHP_EOL;

			if( $this->isAdLinkCrawled( $adlink ) ) {
				$alreadyFetched++;
				echo "this link is already fetched." . PHP_EOL;
				
				if( $alreadyFetched  >= $this->_existedLinkCount ) {
					break;
				}
			} else {
				// Reset count. Always start counting from last newly added ad
				$alreadyFetched = 0;
				echo "add it!" . PHP_EOL;
				
		        $posts[] = $this->fetchAdContentFromAdLink( $adlink );
			}
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
	 * isAdLinkCrawled return true if $adlink is already in Post database table's 'website' column. 
	 * it can be overriden if subclass does not store adlink there.
	 *
	 * @param  string  $adlink  
	 * @return boolean         
	 */
	public function isAdLinkCrawled( $adlink ) {
		$ad = Post::findOne( [
			'website' => $adlink,
		] );

		return $ad !== null;
	}

}
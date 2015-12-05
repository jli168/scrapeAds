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
class CLModel extends BaseModel {

	/**
	 * @var string 
	 */
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

	public function fetchAdData() {
		echo "Fetch CL ads. ".PHP_EOL;

		$adLinks = $this->fetchAdLinksFromSection();

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

		$data[ 'website' ] = $requestUrl;
		$data[ 'section' ] = $this->_sectionName;
		$data[ 'location' ] = $this->_location;

		return $data;		
    }
}

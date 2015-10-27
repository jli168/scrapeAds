<?php

namespace app\models\scrape;

use Goutte\Client;

use yii\base\Component;

/**
 * BaseModel is the model behind scraping 
 */
class BaseModel extends Component {

	private $_baseUrl;

	private $_client;

	private $_actions;

	//number of links to fetch each time
	private $_linkNumbers;

	/**
	 * @param Client $client
	 * @param string $baseUrl
	 */
	public function __construct( $client, $baseUrl ) {
		$this->_client = $client;
		$this->_baseUrl = $baseUrl;
		$this->_linkNumbers = 10;
		parent::__construct();
	}

	public function init() {
		$_actions = [
			'clickLinkInHomePage',
			'clickPostLinks',
		];
	}

	public function getClient() {
		return $this->_client;
	}

	public function clickLinkInHomePage($linkName) {

        $crawler = $this->getClient()->request( 'GET', $this->_baseUrl );

        $link = $crawler->selectLink( $linkName )->link();
        
        return $this->getClient()->click( $link );  
	}

	public function getPosts( $crawler ) {
		$titleLinkFilter = ".hdrlnk";

		$count = 0;

		$client = $this->getClient();

		$data = [];

		$crawler->filter($titleLinkFilter)->each( function ($node, $i) use ( & $count, $client, & $data )  {
			$postContentFilter = "#postingbody";
            
            if($count > $this->_linkNumbers) return;

            $count++;
            //fetch post link
            $link = $node->link(); 
            //click the link, get content
            $subCrawler = $client->click($link);

            $data[] = [
            	'title' => $node->text(),
            	'content' =>  $subCrawler->filter($postContentFilter)->text()
            ];
        });

        return $data;
	}


}
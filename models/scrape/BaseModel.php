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

	public function __construct( $baseUrl ) {
		$this->_baseUrl = $baseUrl;
		$this->_client = new Client();

		parent::__construct();
	}

	public function init() {
		$_actions = [
			'clickSoftwareSection',
			'clickPostLinks',
		];
	}

	public function getClient() {
		return $this->_client;
	}

	public function clickSoftwareSection() {
		$linkName = "software";

        $crawler = $this->getClient()->request( 'GET', $this->_baseUrl );

        // click "software" link
        $link = $crawler->selectLink( $linkName )->link();
        
        $crawler = $this->getClient()->click( $link );  

        return $crawler; 
	}

	public function clickPostLinks( $crawler ) {
		$titleLinkFilter = ".hdrlnk";

		$count = 0;

		$client = $this->getClient();

		$data = [];

		$crawler->filter($titleLinkFilter)->each( function ($node, $i) use ( & $count, $client, & $data )  {
			$postContentFilter = "#postingbody";
            
            //try 3 posts so far
            if($count > 2) return;

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
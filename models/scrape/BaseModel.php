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

	/**
	 * [clickLinkInHomePage2 is able to use hard coded pattern 
	 * to find the "help-wanted" section in the ad!
	 *
	 * TODO: find all job listing!
	 * @param  [type] $linkName [description]
	 * @return [type]           [description]
	 */
	public function clickLinkInHomePage2( $linkName ) {
        $crawler = $this->getClient()->request( 'GET', $this->_baseUrl );
		$category = $crawler->filter("body .container .rightBlue ")->eq(1);
		echo "current category: ". $category->filter("h3")->text() ."\n";

		$catNode =  $crawler->filter(".rightBlue > ul > li > a")
			->reduce( function( $node, $i ) {
				$pattern = "/ny-help-wanted/";
				$url = $node->attr("href");
				return ( bool ) preg_match($pattern, $url, $matches);
			} );

		echo "we get: " . $catNode->attr("href"). '---' . $catNode->text() ."\n";
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
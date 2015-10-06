<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;

use Goutte\Client;


/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ScrapeController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex($message = 'scrape')
    {
        echo $message . " the world \n";
    }

    /**
     * try out craigslist and find all titles in software category.
     * @return [type] [description]
     */
    public function actionTry(){
		$client = new Client();
    	$crawler = $client->request('GET', 'http://newyork.craigslist.org/');
    	$link = $crawler->selectLink('software')->link();
		$crawler = $client->click($link);	
		$count = 0;
		// Get the latest post in this category and display the titles
		$crawler->filter('.hdrlnk')->each(function ($node, $i) use (&$count) {
		    print $node->text(). "--- $i". "\n";
		    $count++;	
		});

		print "total count is ".$count ."\n";
    }

    /**
     * open post links and fetch post data
     * @return [type] [description]
     */
    public function actionTrypostlink() {
        $client = new Client();

        // step1: go to home page
        $crawler = $client->request('GET', 'http://newyork.craigslist.org/');

        // step2: click "software" link
        $link = $crawler->selectLink('software')->link();
        $crawler = $client->click($link);   
        
        //step3: for each job post link, click and fetch html data
        $count = 0;
        $crawler->filter('.hdrlnk')->each( function ($node, $i) use ( & $count, $client )  {
            //try 3 posts so far
            if($count > 2) return;

            $count++;
            //fetch post link
            $link = $node->link(); 
            //click the link, get content
            $subCrawler = $client->click($link);
            //print out ;)
            print $node->text(). "\n";
            print $subCrawler->filter("#postingbody")->html();   
            print "\n-------------===============------------------\n";
        });
    }


}

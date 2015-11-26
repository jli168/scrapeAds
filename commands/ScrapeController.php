<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\scrape\BaseModel;
use app\models\scrape\WJModel;
use app\models\scrape\CLModel;
use app\models\scrape\Post;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;
use yii\console\Controller;

use yii\helpers\ArrayHelper;

use Yii;

/**
 * ScrapeController does the work of scraping website data
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
     * test if database connection works
     */
    public function actionTrydb() {

        $posts = Post::find()->asArray()->all();

        echo "<pre>";
        var_dump(json_decode(json_encode($posts)));
        echo "</pre>";
    }

    /**
     * test if inserting to active record works
     */
    public function actionTryinsert(){
        $post = new Post();
        $post->attributes = [
            'content' => "abalkjdfadf",
            'website' => 'http://newyork.craigslist.org/',
            'section' => 'software',
            'location' => 'nyc'
        ];
        $post->save();
    }

    /**
     * simulate worldjournal ajax call to fetch content data
     */
    public function actionTrypostdata() {
        $hostname = 'www.wjlife.com';
        $optionVaules = [
            "relation" => "AND",
            "0" => [
                "relation" => "AND",
                "0" => [
                    "key" => "wj_order_id"
                ]
            ]
        ];     
        //all help wanted
        $currentURL="/cls_category/03-ny-help-wanted/";      

        //temp page number
        $pno = 0;

        $queryObject= [
            "keyword" => "",
            "pagesize" => 40, //specify how many rows you want to pull each request
            "pno" => $pno,
            "optionVaules" => $optionVaules, 
            "currentURL" => "http://" . $hostname . $currentURL,
            // "currentCatId" => 326, //general help
            "currentCatId" => 327, //restaurant help, hardcoded in their js code
            "currentStateId" => 152,
        ];

        //language: chinese simplified
        $wjlang = "zh-cn";
        $requestUrl =  "http://" . $hostname
                        . "/wp-content/themes/wjlife/includes/classified-core.php?regions=state_ny&variant=" . $wjlang
                        . "&t=" . time();
        // echo "start...\n";
        $client = new Client();
        $crawler = $client->request( "POST", $requestUrl,  $queryObject , [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            'contentType' => 'application/x-www-form-urlencoded;charset=utf-8',
        ] );
        $rowHtml = $crawler->html();
        // if you want to echo out with correct encoding, do `echo utf8_decode($rowHtml)`
        // echo utf8_decode($rowHtml);
        // echo "end...\n";
        
        $subCrawler = new Crawler();
        $subCrawler->addHtmlContent($rowHtml);

        $linkArray = $subCrawler->filter(".catDesc a")->each( function( $node, $index ){
            return $href = $node->attr('href');
        } );

        print_r($linkArray);
    }

    /**
     * try out worldjournal.com
     *
     * it seems not working, because the content is from ajax call! 
     * can not crawl this!
     */
    public function actionTrywj() {
        $baseData = [
            //select chinese simplified font, and location: ny
            'website' => 'http://www.wjlife.com/classifieds/?variant=zh-cn&regions=state_ny',
            //select 'help-wanted' section
            'section' => 'help-wanted',
            'location' => 'ny'
        ];
        echo "start wj \n";
        $scrapeModel = new BaseModel( new Client(), $baseData['website'] );
        $crawler = $scrapeModel->clickLinkInHomePage2($baseData['section']);
        echo "<pre>";
        var_dump(json_decode(json_encode($crawler->html())));
        echo "</pre>";
    }

    /**
     * actionTrywj2 fetch data from a single ad link using straight forward way
     * 
     */
    public function actionTrywj2() {
        $link = 'http://www.wjlife.com/classified/l-i-japanese-hiring-servers-631-486-8900/?variant=zh-cn';
        $client = new Client();
        $crawler = $client->request( 'GET', $link);

        // get title:
        $title = $crawler->filter(".classifiedTitle h4")->text();
        echo "title: " . trim( $title ). "\n";

        $rawContent = $crawler->filter(".classifiedDetails")->text();

        $contentArr = explode( "\n", trim( $rawContent ) );
        print_r($contentArr);

        // get location:
        $location = $this->findAdLocation( trim( $contentArr[0] ) );
        echo "location: " . $location . "\n";

        // get content:
        $content = trim( $contentArr[1] );

        echo "content: $content \n";
    }

    /**
     * try to use class configuration instead of init in class
     */
    public function actionTrywj3() {
        $wjModel = Yii::$app->wjscraper;
        $posts = $wjModel->fetchAdData();
        echo "<pre>";
        var_dump(json_decode(json_encode($posts)));
        echo "</pre>";
    }

    /**
     * actionTrywj4 fetch ad data by an ad link
     * @return array  ad postdata
     */
    public function actionTrywj4() {
        $wjModel = Yii::$app->wjscraper;
        $adlink = "http://www.wjlife.com/classified/l-i-japanese-hiring-servers-631-486-8900/";
        $post = $wjModel->fetchPostDataFromAdContent($adlink);
        echo "<pre>";
        var_dump(json_decode(json_encode($post)));
        echo "</pre>";
        
    }

    /**
     * actionCrawlWJ crawls worldjournal's restaurant help wanted section
     */
    public function actionCrawlwj() {
        // set 500 seconds time limit to run this program
        set_time_limit(500);

        $time_start = microtime(true);

        $posts = Yii::$app->wjscraper->fetchAdData();

        $time_end = microtime(true);

        echo "time spent on crawling: " . ( $time_end - $time_start ) . "\n";
        
        Post::batchInsert( $posts );  

        echo "data inserted \n";
    }

    /**
     * insert fetched data into database
     */
    public function actionTrymodel() {
        $baseData = [
            'website' => 'http://newyork.craigslist.org/',
            'section' => 'software',
            'location' => 'nyc'
        ];

        $scrapeModel = new BaseModel( new Client(), $baseData['website'] );
   
        $crawler = $scrapeModel->clickLinkInHomePage($baseData['section']);

        $data = $scrapeModel->getPosts($crawler);

        $resultArr = array_map( function( $item ) use ( $baseData ) {
            return ArrayHelper::merge( $item, $baseData );
        }, $data );

/*       
        // insert using active record, one by one
        foreach($resultArr as $result) {
            $post = new Post();
            $post->attributes = $result;
            $post->save();
        }
*/        
        // my fav: batch insert
        Post::batchInsert( $resultArr );

        echo "good to go! \n";            
    }

    /**
     * [actionCrawlcl description]
     * @return [type] [description]
     */
    public function actionCrawlcl() {
        echo "in action crawllc". PHP_EOL;
        $clscraper = Yii::$app->clscraper;

        $time_start = microtime(true);

        $posts = $clscraper->fetchAdData();

        $time_end = microtime(true);

        echo "time spent on crawling: " . ( $time_end - $time_start ) . "\n";

        Post::batchInsert( $posts );  
        
    }

}

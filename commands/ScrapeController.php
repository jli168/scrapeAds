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
        echo $message . " the world" . PHP_EOL;
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

    public function actionTryiscrawled() {
        $adlink = 'http://www.wjlife.com/176820/article-shortlink/';

        if( Yii::$app->wjscraper->isCrawled($adlink) ){
            echo "crawled already";
        }else {
            echo "not yet";
        }
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
     * crawl fetch data and insert it into database.
     * 
     * @param  BaseModel $model 
     */
    public function crawl( BaseModel $model ) {
        // set 500 seconds time limit to run this program
        set_time_limit(500);

        $time_start = microtime(true);
        
        $date = date('m/d/Y h:i:s a', time());
        echo '[ '. $date . ' ]: ';

        $posts = $model->fetchAdData();

        $time_end = microtime(true);

        echo " [ time spent on crawling: " . ( $time_end - $time_start ). ' ] ';
        
        if( !empty( $posts ) ) {
            Post::batchInsert( $posts );
            echo "There are [ ". count($posts). " ] ad inserted" . PHP_EOL;
        } else {
            echo "No action taken" . PHP_EOL;
        }
    }

    /**
     * actionCrawlWJ crawls worldjournal's restaurant help wanted section
     */
    public function actionCrawlwj() {
        $this->crawl( Yii::$app->wjscraper );
    }

    /**
     * [actionCrawlcl description]
     * @return [type] [description]
     */
    public function actionCrawlcl() {
        $this->crawl( Yii::$app->clscraper );
    }
}

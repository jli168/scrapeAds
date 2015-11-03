<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\scrape\BaseModel;

use app\models\scrape\Post;

use Goutte\Client;

use yii\console\Controller;

use yii\helpers\ArrayHelper;

use Yii;

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
     * try out worldjournal.com
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
        echo "end wj \n";
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

}

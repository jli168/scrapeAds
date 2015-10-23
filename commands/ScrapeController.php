<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\scrape\BaseModel;

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

    public function actionTrydb() {
        $posts = Yii::$app->db->createCommand('SELECT * FROM post')
                    -> queryAll();
        echo "<pre>";
        var_dump(json_decode(json_encode($posts)));
        echo "</pre>";
        
    }

    public function actionTrymodel() {
        $baseData = [
            'url' => 'http://newyork.craigslist.org/',
            'section' => 'software'
        ];

        $scrapeModel = new BaseModel( new Client(), $baseData['url'] );
   
        $crawler = $scrapeModel->clickLinkInHomePage($baseData['section']);

        $data = $scrapeModel->getPosts($crawler);

        $resultArr = array_map( function( $item ) use ( $baseData ) {
            return ArrayHelper::merge( $item, $baseData );
        }, $data );
        
    }

}

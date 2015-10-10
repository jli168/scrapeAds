<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;

use Goutte\Client;

use app\models\scrape\BaseModel;


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

    public function actionTrymodel() {
        $baseUrl = 'http://newyork.craigslist.org/';

        $scrapeModel = new BaseModel( new Client(), $baseUrl );
   
        $crawler = $scrapeModel->clickLinkInHomePage("software");

        $data = $scrapeModel->getPosts($crawler);

        echo "_________result data __________ \n";
        echo "<pre>";
        var_dump(json_decode(json_encode($data)));
        echo "</pre>";   
    }

}

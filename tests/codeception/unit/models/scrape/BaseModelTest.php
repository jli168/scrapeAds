<?php
namespace tests\codeception\unit\models\scrape;

use Goutte\Client;
use app\models\scrape\BaseModel;
use Codeception\Specify;
use yii\codeception\TestCase;

class BaseModelTest extends TestCase
{
    //must specify it!
    use Specify;

    // tests
    public function testClickLinkInHomePage()
    {
        //mock client
        $model = $this->getMockBuilder('app\models\scrape\BaseModel')
                    ->disableOriginalConstructor()
                    ->getMock();
        $model->method("getClient")->willReturn("whatever");

        $this->specify("return from getClient is string", function() use ($model) {
                expect($model->getClient())->equals("whatever");
        });

        // TODO: mock all client functions.

    }

}
<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\scrape\Post;


class HomeController extends Controller {

	// access by http://192.168.33.11/?r=home
	public function actionIndex() {
		echo "in Home Controller";
		return $this->render('about');
	}

	public function actionShowwjposts() {

	}




}
<?php

namespace app\models\scrape;

use yii\db\ActiveRecord;

use Yii;

class Post extends ActiveRecord {

	public static function tableName(){
		return 'post';
	}

	public function rules() {
		return [ 
			[['title', 'content', 'website', 'section', 'location'] , 'safe']
		];
	}

	/**
	 * use command to do batch insert, 
	 * ref: http://stackoverflow.com/a/29581770/1369136
	 * @param  [array] $posts 
	 */
	public static function batchInsert( $posts ) {
		$batchInsertArr = array_map( function( $item ){
            return [
                    isset( $item['title'] ) ? $item['title'] : null, 
                    isset( $item['content'] ) ? $item['content'] : null, 
                    isset( $item['website'] ) ? $item['website'] : null, 
                    isset( $item['section'] ) ? $item['section'] : null, 
                    isset( $item['location'] ) ? $item['location'] : null
            ];
        }, $posts );

        Yii::$app->db->createCommand()->batchInsert(
            'post', 
            ['title', 'content', 'website', 'section', 'location'], 
            $batchInsertArr
        )->execute();
	}
}

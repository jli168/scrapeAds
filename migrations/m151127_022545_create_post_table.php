<?php

use yii\db\Migration;

class m151127_022545_create_post_table extends Migration
{
    protected $MySqlOptions = 'ENGINE=InnoDB CHARSET=utf8';

    /*
        CREATE TABLE `post` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(52) NOT NULL,
          `content` text,
          `website` varchar(100) DEFAULT NULL,
          `section` varchar(52) DEFAULT NULL,
          `location` varchar(52) DEFAULT NULL,
          `create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=8572 DEFAULT CHARSET=utf8;
    */
    public function up()
    {
        $this->createTable('post', [
            'id' => $this->primaryKey(),
            'title' => $this->string(52)->notNull(),
            'content' => $this->text(),
            'website' => $this->string(168),
            'section' => $this->string(52),
            'location' => $this->string(52),
            'create_at' => $this->timestamp()->notNull(),
        ], $this->MySqlOptions);
    }

    public function down()
    {
        $this->dropTable('post');
    }
}

<?php

use yii\db\Migration;

/**
 * Class m190411_090329_CreateLiskCountt
 */
class m190411_090329_CreateLiskCountt extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
//            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_bin ENGINE=InnoDB';
        }

        $this->createTable('{{%lisk_counter}}', [
            'counter_name' => $this->string(32)->unique(),
            'counter_value' => $this->bigInteger(20),
        ], $tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190411_090329_CreateLiskCountt cannot be reverted.\n";

        return false;
    }
    */
}

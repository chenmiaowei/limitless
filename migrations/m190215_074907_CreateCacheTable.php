<?php

use yii\db\Migration;

/**
 * Class m190215_074907_CreateCacheTable
 */
class m190215_074907_CreateCacheTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
//        $this->db = Yii::$app->get("orangins_cache");

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_bin ENGINE=InnoDB';
        }

        $this->createTable('{{%cache_general}}', [
            'id' => $this->primaryKey(),
            'cache_key_hash' => $this->string(12)->unique(),
            'cache_key' => $this->string(128)->notNull(),
            'cache_format' => $this->string(16)->notNull(),
            'cache_data' => $this->text()->notNull(),
            'cache_expires' => $this->integer(11)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-cache_key_hash', '{{%cache_general}}', 'cache_key_hash', true);
        $this->createIndex('idx-created_at', '{{%cache_general}}', 'created_at');
        $this->createIndex('idx-cache_expires', '{{%cache_general}}', 'cache_expires');


        $this->createTable('{{%cache_markupcache}}', [
            'id' => $this->primaryKey(),
            'cache_key' => $this->string(128)->notNull(),
            'cache_data' => $this->text()->notNull(),
            'metadata' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-cache_key', '{{%cache_markupcache}}', 'cache_key', true);
        $this->createIndex('idx-created_at', '{{%cache_markupcache}}', 'created_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
       $this->dropTable("cache_general");
       $this->dropTable("cache_markupcache");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190215_074907_CreateCacheTable cannot be reverted.\n";

        return false;
    }
    */
}

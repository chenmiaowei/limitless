<?php

use yii\db\Migration;

/**
 * Class m180827_143424_CreateFeed
 */
class m180827_143424_CreateFeed extends Migration
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
        $this->createTable('{{%feed_storydata}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'chronological_key' => $this->bigInteger(20)->notNull(),
            'story_type' => $this->string(64)->notNull(),
            'story_data' => $this->text()->notNull(),
            'author_phid' => $this->string(64)->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-chronological_key', '{{%feed_storydata}}', ['chronological_key'], true);


        $this->createTable('{{%feed_storynotification}}', [
            'id' => $this->primaryKey(),
            'user_phid' => $this->string(64)->notNull()->unique(),
            'primary_object_phid' => $this->string(64)->notNull(),
            'chronological_key' => $this->bigInteger(20)->notNull(),
            'has_viewed' => $this->integer(1)->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-user_phid', '{{%feed_storynotification}}', ['user_phid', 'has_viewed', 'primary_object_phid'], false);
        $this->createIndex('idx-primary_object_phid', '{{%feed_storynotification}}', ['primary_object_phid'], false);
        $this->createIndex('idx-chronological_key', '{{%feed_storynotification}}', ['chronological_key'], false);

        $this->createTable('{{%feed_storyreference}}', [
            'id' => $this->primaryKey(),
            'object_phid' => $this->string(64)->notNull(),
            'chronological_key' => $this->bigInteger(20)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-object_phid', '{{%feed_storyreference}}', ['object_phid', 'chronological_key'], true);
        $this->createIndex('idx-chronological_key', '{{%feed_storyreference}}', ['chronological_key'], false);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable("feed_storydata");
        $this->dropTable("feed_storynotification");
        $this->dropTable("feed_storyreference");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180827_143424_CreateFeed cannot be reverted.\n";

        return false;
    }
    */
}

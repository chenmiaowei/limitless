<?php

use yii\db\Migration;

/**
 * Class m180821_031615_CreateConfigTable
 */
class m180821_031615_CreateConfigTable extends Migration
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

        $this->createTable('{{%config}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'namespace' => $this->string(64)->notNull()->comment("命名空间"),
            'config_key' => $this->string(64)->notNull()->comment("配置项"),
            'value' => $this->text()->notNull()->comment("值"),
            'is_deleted' => $this->integer(1)->notNull()->comment("是否删除"),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);


        $this->createIndex('idx-namespace', '{{%config}}', ['namespace', 'config_key']);


        $this->createTable('{{%config_transactions}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'author_phid' => $this->string(64)->notNull()->comment("作者"),
            'object_phid' => $this->string(64)->notNull()->comment("对象"),
            'view_policy' => $this->string(64)->notNull()->comment("显示权限"),
            'edit_policy' => $this->string(64)->notNull()->comment("编辑权限"),
            'comment_phid' => $this->string(64)->null()->comment("评论"),
            'comment_version' => $this->integer(10)->notNull()->comment("评论"),
            'transaction_type' => $this->string(32)->notNull()->comment("交易类型"),
            'old_value' => $this->text()->notNull()->comment("旧值"),
            'new_value' => $this->text()->notNull()->comment("新值"),
            'metadata' => $this->text()->notNull()->comment("额外数据"),
            'content_source' => $this->text()->notNull()->comment("内容"),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);


        $this->createIndex('idx-object_phid', '{{%config_transactions}}', ['object_phid']);



        $this->createTable('{{%config_manualactivity}}', [
            'id' => $this->primaryKey(),
            'activity_type' => $this->string(64)->notNull(),
            'parameters' => $this->text()->notNull(),

        ], $tableOptions);


        $this->createIndex('idx-activity_type', '{{%config_manualactivity}}', ['activity_type']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%config}}');
        $this->dropTable('{{%config_transactions}}');
        $this->dropTable('{{%config_manualactivity}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180821_031615_CreateConfigTable cannot be reverted.\n";

        return false;
    }
    */
}

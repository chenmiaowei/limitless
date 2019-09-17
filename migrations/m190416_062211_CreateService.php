<?php

use yii\db\Migration;

/**
 * Class m190416_062211_CreateService
 */
class m190416_062211_CreateService extends Migration
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

        $this->createTable('{{%userservice}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'user_phid' => $this->string(64)->notNull(),
            'type' => $this->string(64)->notNull(),
            'parameters' => $this->text(),
            'amount' => $this->double()->defaultValue(0)->notNull(),

            'author_phid' => $this->string(64)->null(),
            'view_phid' => $this->string(64)->null(),
            'edit_phid' => $this->string(64)->null(),
            'status' => $this->string(32)->defaultValue('ACTIVE'),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-status', '{{%userservice}}', ['status'], false);
        $this->createIndex('idx-user_phid', '{{%userservice}}', ['user_phid'], false);
        $this->createIndex('idx-author_phid', '{{%userservice}}', ['author_phid'], false);

        $this->createTable('{{%userservice_transactions}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'object_phid' => $this->string(64)->notNull()->comment("对象ID"),
            'comment_phid' => $this->string(64)->null()->comment("评论"),
            'comment_version' => $this->integer(11)->defaultValue(0)->comment("评论版本"),
            'transaction_type' => $this->string(32)->notNull()->comment("类型"),
            'old_value' => $this->text()->notNull()->comment("旧值"),
            'new_value' => $this->text()->notNull()->comment("新值"),
            'content_source' => $this->text()->notNull()->comment("内容"),
            'metadata' => $this->text()->notNull()->comment("数据"),

            'author_phid' => $this->string(64)->notNull()->comment("作者"),
            'view_policy' => $this->string(64)->notNull()->comment("显示权限"),
            'edit_policy' => $this->string(64)->notNull()->comment("编辑权限"),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-object_phid', '{{%userservice_transactions}}', 'object_phid');


        $this->createTable('{{%userservice_edge}}', [
            'id' => $this->primaryKey(),
            'src' => $this->string(64)->notNull(),
            'type' => $this->integer(11)->notNull(),
            'dst' => $this->string(64)->notNull(),
            'seq' => $this->integer(11)->notNull(),
            'data_id' => $this->integer(11)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-primary', '{{%userservice_edge}}', ['src', 'type', 'dst'], true);
        $this->createIndex('idx-key_dst', '{{%userservice_edge}}', ['dst', 'type', 'src'], true);
        $this->createIndex('idx-src', '{{%userservice_edge}}', ['src', 'type', 'created_at', 'seq'], true);

        $this->createTable('{{%userservice_edgedata}}', [
            'id' => $this->primaryKey(),
            'data' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createTable('{{%userservice_cache}}', [
            'id' => $this->primaryKey(),
            'object_phid' => $this->string(64)->notNull(),
            'amount' => $this->double()->defaultValue(0)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-object_phid', '{{%userservice_cache}}', ['object_phid'], false);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable("{{%userservice}}");
        $this->dropTable("{{%userservice_transactions}}");
        $this->dropTable("{{%userservice_edge}}");
        $this->dropTable("{{%userservice_edgedata}}");
        $this->dropTable("{{%userservice_cache}}");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190416_062211_CreateService cannot be reverted.\n";

        return false;
    }
    */
}

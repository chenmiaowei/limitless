<?php

use yii\db\Migration;

/**
 * Class m190321_105254_CreateConpherence
 */
class m190321_105254_CreateConpherence extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_bin ENGINE=InnoDB';
        }

        $this->createTable('{{%conpherence_index}}', [
            'id' => $this->primaryKey(),
            'thread_phid' => $this->string(64)->notNull(),
            'transaction_phid' => $this->string(64)->notNull(),
            'previous_transaction_phid' => $this->string(64)->null(),
            'corpus' => $this->text()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-transaction_phid', '{{%conpherence_index}}', ['transaction_phid'], true);
        $this->createIndex('idx-previous_transaction_phid', '{{%conpherence_index}}', ['previous_transaction_phid'], true);
        $this->createIndex('idx-thread_phid', '{{%conpherence_index}}', ['thread_phid'], false);
        $this->execute("ALTER TABLE `conpherence_index` ADD FULLTEXT INDEX `idx-corpus` (corpus ASC)");


        $this->createTable('{{%conpherence_participant}}', [
            'id' => $this->primaryKey(),
            'participant_phid' => $this->string(64)->notNull(),
            'conpherence_phid' => $this->string(64)->notNull(),
            'seen_message_count' => $this->integer(20)->notNull(),
            'settings' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-conpherence_phid', '{{%conpherence_participant}}', ['conpherence_phid', 'participant_phid'], true);
        $this->createIndex('idx-participant_phid', '{{%conpherence_participant}}', ['participant_phid', 'conpherence_phid'], false);


        $this->createTable('{{%conpherence_thread}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'title' => $this->string(255)->null(),
            'message_count' => $this->integer(20)->notNull(),
            'view_policy' => $this->string(64)->notNull(),
            'edit_policy' => $this->string(64)->notNull(),
            'join_policy' => $this->string(64)->notNull(),
            'mail_key' => $this->string(20)->notNull(),
            'topic' => $this->string(255)->notNull(),
            'profileImage_phid' => $this->string(64)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createTable('{{%conpherence_threadtitle_ngrams}}', [
            'id' => $this->primaryKey(),
            'object_id' => $this->integer(11)->notNull(),
            'ngram' => $this->string(3)->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-object_id', '{{%conpherence_threadtitle_ngrams}}', ['object_id'], false);
        $this->createIndex('idx-ngram-object_id', '{{%conpherence_threadtitle_ngrams}}', ['ngram', 'object_id'], false);


        $this->createTable('{{%conpherence_transaction}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'object_phid' => $this->string(64)->notNull()->comment("对象_id"),
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

        $this->createIndex('idx-object_phid', '{{%conpherence_transaction}}', 'object_phid');

        $this->createTable('{{%conpherence_transaction_comment}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'transaction_phid' => $this->string(64)->null(),

            'author_phid' => $this->string(64)->notNull()->comment("作者"),
            'view_policy' => $this->string(64)->notNull()->comment("显示权限"),
            'edit_policy' => $this->string(64)->notNull()->comment("编辑权限"),

            'comment_version' => $this->integer(11)->defaultValue(0)->comment("评论版本"),
            'content' => $this->text()->notNull(),
            'content_source' => $this->text()->notNull(),
            'is_deleted' => $this->integer(1)->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-transaction_phid', '{{%conpherence_transaction_comment}}', ['transaction_phid', 'comment_version']);


        $this->createTable('{{%conpherence_edge}}', [
            'id' => $this->primaryKey(),
            'src' => $this->string(64)->notNull(),
            'type' => $this->integer(11)->notNull(),
            'dst' => $this->string(64)->notNull(),
            'seq' => $this->integer(11)->notNull(),
            'data_id' => $this->integer(11)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-primary', '{{%conpherence_edge}}', ['src', 'type', 'dst'], true);
        $this->createIndex('idx-key_dst', '{{%conpherence_edge}}', ['dst', 'type', 'src'], true);
        $this->createIndex('idx-src', '{{%conpherence_edge}}', ['src', 'type', 'created_at', 'seq'], true);

        $this->createTable('{{%conpherence_edgedata}}', [
            'id' => $this->primaryKey(),
            'data' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%conpherence_index}}');
        $this->dropTable('{{%conpherence_participant}}');
        $this->dropTable('{{%conpherence_thread}}');
        $this->dropTable('{{%conpherence_threadtitle_ngrams}}');
        $this->dropTable('{{%conpherence_transaction}}');
        $this->dropTable('{{%conpherence_transaction_comment}}');
        $this->dropTable('{{%conpherence_edge}}');
        $this->dropTable('{{%conpherence_edgedata}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190321_105254_CreateConpherence cannot be reverted.\n";

        return false;
    }
    */
}

<?php

use yii\db\Migration;

/**
 * Class m180818_081803_CreateFIle
 */
class m180818_081803_CreateFIle extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
//            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_bin ENGINE=InnoDB';
        }

        $this->createTable('{{%file}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'name' => $this->string(128)->null()->comment("文件名称"),
            'mime_type' => $this->string(128)->null()->comment("文件类型"),
            'byte_size' => $this->integer(11)->defaultValue(0)->comment("文件大小"),
            'storage_engine' => $this->string(32)->notNull()->comment("存储引擎"),
            'storage_format' => $this->string(32)->notNull()->comment("存储格式"),
            'storage_handle' => $this->string(255)->notNull()->comment("存储处理"),
            'author_phid' => $this->string(64)->null()->comment("作者"),
            'metadata' => $this->text()->notNull()->comment("数据"),
            'view_policy' => $this->string(64)->null()->comment("查看权限"),
            'edit_policy' => $this->string(64)->null()->comment("编辑权限"),
            'builtin_key' => $this->string(64)->null()->comment("内建属性")->unique(),
            'content_hash' => $this->string(64)->null(),
            'is_partial' => $this->integer(1)->defaultValue(0)->comment(""),
            'is_deleted' => $this->integer(1)->defaultValue(0)->comment(""),
            'secret_key' => $this->string(32)->null()->comment(""),
            'ttl' => $this->integer(10)->null(),
            'is_explicit_upload' => $this->integer(1)->defaultValue(0)->null(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);


        $this->createTable('{{%file_storageblobs}}', [
            'id' => $this->primaryKey(),
            'data' => $this->binary()->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);



        $this->createTable('{{%file_chunk}}', [
            'id' => $this->primaryKey(),
            'chunk_handle' => $this->string(12)->notNull(),
            'byte_start' => $this->bigInteger(20)->notNull(),
            'byte_end' => $this->bigInteger(20)->notNull(),
            'data_file_phid' => $this->string(64)->null(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-chunk_handle', '{{%file_chunk}}', ['chunk_handle', 'byte_start', 'byte_end']);
        $this->createIndex('idx-data_file_phid', '{{%file_chunk}}', ['data_file_phid']);

        $this->createTable('{{%file_externalrequest}}', [
            'id' => $this->primaryKey(),
            'file_phid' => $this->string(64)->null(),
            'ttl' => $this->integer(10)->notNull(),
            'uri' => $this->text()->notNull(),
            'uri_index' => $this->string(12)->notNull()->unique(),
            'is_successful' => $this->integer(1)->notNull(),
            'response_message' => $this->text()->null(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-ttl', '{{%file_externalrequest}}', ['ttl']);
        $this->createIndex('idx-file_phid', '{{%file_externalrequest}}', ['file_phid']);


        $this->createTable('{{%file_filename_ngrams}}', [
            'id' => $this->primaryKey(),
            'object_id' => $this->integer(10)->notNull(),
            'ngram' => $this->string(3)->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-object_id', '{{%file_filename_ngrams}}', ['object_id']);
        $this->createIndex('idx-ngram', '{{%file_filename_ngrams}}', ['ngram', 'object_id']);

        $this->createTable('{{%file_imagemacro}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'author_phid' => $this->string(64)->null(),
            'file_phid' => $this->string(64)->notNull(),
            'name' => $this->string(128)->notNull()->unique(),
            'is_disabled' => $this->integer(1)->notNull(),
            'audio_phid' => $this->string(64)->null(),
            'audio_behavior' => $this->string(64)->notNull(),
            'mail_key' => $this->string(20)->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-is_disabled', '{{%file_imagemacro}}', ['is_disabled']);
        $this->createIndex('idx-created_at', '{{%file_imagemacro}}', ['created_at']);


        $this->createTable('{{%file_transaction}}', [
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

        $this->createIndex('idx-object_phid', '{{%file_transaction}}', 'object_phid');


        $this->createTable('{{%file_transaction_comment}}', [
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

        $this->createIndex('idx-transaction_phid', '{{%file_transaction_comment}}', ['transaction_phid', 'comment_version']);


        $this->createTable('{{%file_transformedfile}}', [
            'id' => $this->primaryKey(),
            'original_phid' => $this->string(64)->notNull(),
            'transform' => $this->string(128)->notNull(),
            'transformed_phid' => $this->string(64)->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-original_phid', '{{%file_transformedfile}}', ['original_phid', 'transform'], true);
        $this->createIndex('idx-transformed_phid', '{{%file_transformedfile}}', ['transformed_phid']);


        $this->createTable('{{%file_macro_transaction}}', [
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

        $this->createIndex('idx-object_phid', '{{%file_macro_transaction}}', 'object_phid');


        $this->createTable('{{%file_macro_transaction_comment}}', [
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

        $this->createIndex('idx-transaction_phid', '{{%file_macro_transaction_comment}}', ['transaction_phid', 'comment_version']);

        $this->createTable('{{%file_edge}}', [
            'id' => $this->primaryKey(),
            'src' => $this->string(64)->notNull(),
            'type' => $this->integer(11)->notNull(),
            'dst' => $this->string(64)->notNull(),
            'seq' => $this->integer(11)->notNull(),
            'data_id' => $this->integer(11)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-primary', '{{%file_edge}}', ['src', 'type', 'dst'], true);
        $this->createIndex('idx-key_dst', '{{%file_edge}}', ['dst', 'type', 'src'], true);
        $this->createIndex('idx-src', '{{%file_edge}}', ['src', 'type', 'created_at', 'seq'], true);

        $this->createTable('{{%file_edgedata}}', [
            'id' => $this->primaryKey(),
            'data' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
       $this->dropTable("{{%file}}");
       $this->dropTable("{{%file_storageblobs}}");

        $this->dropTable('{{%file_edge}}');
        $this->dropTable('{{%file_edgedata}}');

        $this->dropTable('{{%file_chunk}}');
        $this->dropTable('{{%file_externalrequest}}');
        $this->dropTable('{{%file_filename_ngrams}}');
        $this->dropTable('{{%file_imagemacro}}');
        $this->dropTable('{{%file_transaction}}');
        $this->dropTable('{{%file_transaction_comment}}');
        $this->dropTable('{{%file_transformedfile}}');
        $this->dropTable('{{%file_macro_transaction}}');
        $this->dropTable('{{%file_macro_transaction_comment}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180818_081803_CreateFIle cannot be reverted.\n";

        return false;
    }
    */
}

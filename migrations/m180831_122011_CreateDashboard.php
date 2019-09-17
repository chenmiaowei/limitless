<?php

use yii\db\Migration;

/**
 * Class m180831_122011_CreateDashboard
 */
class m180831_122011_CreateDashboard extends Migration
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

        $this->createTable('{{%dashboard}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'name' => $this->string(64)->notNull()->comment("名称"),
            'icon' => $this->string(64)->notNull()->comment("名称"),
            'status' => $this->string(32)->notNull(),
            'layout_config' => $this->text()->notNull()->comment("配置"),

            'author_phid' => $this->string(64)->notNull()->comment("作者"),
            'view_policy' => $this->string(64)->notNull()->comment("显示权限"),
            'edit_policy' => $this->string(64)->notNull()->comment("编辑权限"),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createTable('{{%dashboard_panels}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'name' => $this->string(64)->notNull()->comment("名称"),
            'panel_type' => $this->string(64)->notNull()->comment("名称"),
            'is_archived' => $this->smallInteger(1)->defaultValue(0)->comment("归档"),
            'properties' => $this->text()->notNull()->comment("配置"),

            'author_phid' => $this->string(64)->notNull()->comment("作者"),
            'view_policy' => $this->string(64)->notNull()->comment("显示权限"),
            'edit_policy' => $this->string(64)->notNull()->comment("编辑权限"),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createTable('{{%dashboard_transactions}}', [
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
        $this->createIndex('idx-object_phid', '{{%dashboard_transactions}}', 'object_phid');

        $this->createTable('{{%dashboard_install}}', [
            'id' => $this->primaryKey(),
            'installer_phid' => $this->string(64)->notNull(),
            'object_phid' => $this->integer(64)->notNull(),
            'application_class' => $this->string(64)->notNull(),
            'dashboard_phid' => $this->string(64)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-object_phid', '{{%dashboard_install}}', ['object_phid', 'application_class'], true);


        $this->createTable('{{%dashboard_ngrams}}', [
            'id' => $this->primaryKey(),
            'object_id' => $this->integer(11)->notNull(),
            'ngram' => $this->string(3)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-object_id', '{{%dashboard_ngrams}}', ['object_id'], false);
        $this->createIndex('idx-object_id-ngram', '{{%dashboard_ngrams}}', ['object_id', 'ngram'], false);

        $this->createTable('{{%dashboard_panels_ngrams}}', [
            'id' => $this->primaryKey(),
            'object_id' => $this->integer(11)->notNull(),
            'ngram' => $this->string(3)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-object_id', '{{%dashboard_panels_ngrams}}', ['object_id'], false);
        $this->createIndex('idx-object_id-ngram', '{{%dashboard_panels_ngrams}}', ['object_id', 'ngram'], false);




        $this->createTable('{{%dashboard_edge}}', [
            'id' => $this->primaryKey(),
            'src' => $this->string(64)->notNull(),
            'type' => $this->integer(11)->notNull(),
            'dst' => $this->string(64)->notNull(),
            'seq' => $this->integer(11)->notNull(),
            'data_id' => $this->integer(11)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-primary', '{{%dashboard_edge}}', ['src', 'type', 'dst'], true);
        $this->createIndex('idx-key_dst', '{{%dashboard_edge}}', ['dst', 'type', 'src'], true);
        $this->createIndex('idx-src', '{{%dashboard_edge}}', ['src', 'type', 'created_at', 'seq'], true);


        $this->createTable('{{%dashboard_edgedata}}', [
            'id' => $this->primaryKey(),
            'data' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);


        $this->createTable('{{%dashboard_panels_transactions}}', [
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

        $this->createIndex('idx-object_phid', '{{%dashboard_panels_transactions}}', 'object_phid');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable("dashboard");
        $this->dropTable("dashboard_panels");
        $this->dropTable("dashboard_transactions");

        $this->dropTable('{{%dashboard_edge}}');
        $this->dropTable('{{%dashboard_edgedata}}');
        $this->dropTable('{{%dashboard_panels_transactions}}');
        $this->dropTable('{{%dashboard_install}}');
        $this->dropTable('{{%dashboard_panels_ngrams}}');
        $this->dropTable('{{%dashboard_ngrams}}');

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180831_122011_CreateDashboard cannot be reverted.\n";

        return false;
    }
    */
}

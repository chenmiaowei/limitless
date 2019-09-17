<?php

use yii\db\Migration;

/**
 * Class m190114_063933_CreateSearch
 */
class m190114_063933_CreateSearch extends Migration
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

        $this->createTable('{{%search_document}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'document_type' => $this->string(4)->notNull(),
            'document_title' => $this->string(255)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-created_at', '{{%search_document}}', 'created_at');
        $this->createIndex('idx-document_type', '{{%search_document}}', ['document_type', 'created_at']);


        $this->createTable('{{%search_documentfield}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'phid_type' => $this->string(4)->notNull(),
            'field' => $this->string(4)->notNull(),
            'aux_phid' => $this->string(64)->null(),
            'corpus' => $this->text()->null(),
            'stemmed_corpus' => $this->text()->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->execute("ALTER TABLE `search_documentfield` ADD FULLTEXT( `corpus`, `stemmed_corpus`);");


        $this->createTable('{{%search_documentrelationship}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'related_phid' => $this->string(64)->notNull(),
            'relation' => $this->string(4)->notNull(),
            'related_type' => $this->string(4)->notNull(),
            'related_time' => $this->integer(11)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-related_phid', '{{%search_documentrelationship}}', ['related_phid', 'relation']);
        $this->createIndex('idx-relation', '{{%search_documentrelationship}}', ['relation', 'related_phid']);


        $this->createTable('{{%search_editengineconfiguration}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'engine_key' => $this->string(64)->notNull(),
            'builtin_key' => $this->string(64)->null(),
            'name' => $this->string(255)->notNull(),
            'view_policy' => $this->string(64)->notNull(),
            'properties' => $this->text()->notNull(),
            'is_disabled' => $this->integer(1)->notNull(),
            'is_default' => $this->integer(1)->notNull(),
            'is_edit' => $this->integer(1)->notNull(),
            'create_order' => $this->integer(11)->notNull(),
            'edit_order' => $this->integer(11)->notNull(),
            'subtype' => $this->string(64)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-engine_key', '{{%search_editengineconfiguration}}', ['engine_key', 'builtin_key']);
        $this->createIndex('idx-engineKey-is_default', '{{%search_editengineconfiguration}}', ['engine_key', 'is_default', 'is_disabled']);
        $this->createIndex('idx-engineKey-is_edit', '{{%search_editengineconfiguration}}', ['engine_key', 'is_edit', 'is_disabled']);

        $this->createTable('{{%search_editengineconfigurationtransaction}}', [
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

        $this->createIndex('idx-object_phid', '{{%search_editengineconfigurationtransaction}}', 'object_phid');


        $this->createTable('{{%search_indexversion}}', [
            'id' => $this->primaryKey(),
            'object_phid' => $this->string(64)->unique(),
            'extension_key' => $this->string(64)->notNull(),
            'version' => $this->string(128)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-related_phid', '{{%search_indexversion}}', ['object_phid', 'extension_key']);

        $this->createTable('{{%search_namedquery}}', [
            'id' => $this->primaryKey(),
            'user_phid' => $this->string(64)->notNull(),
            'engine_class_name' => $this->string(128)->notNull(),
            'query_name' => $this->string(255)->notNull(),
            'query_key' => $this->string(12)->notNull(),
            'is_builtin' => $this->integer(1)->notNull(),
            'is_disabled' => $this->integer(1)->notNull(),
            'sequence' => $this->integer(10)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-related_phid', '{{%search_namedquery}}', ['user_phid', 'engine_class_name', 'query_key'], true);


        $this->createTable('{{%search_namedqueryconfig}}', [
            'id' => $this->primaryKey(),
            'engine_class_name' => $this->string(128)->notNull(),
            'scope_phid' => $this->string(64)->notNull(),
            'properties' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-related_phid', '{{%search_namedqueryconfig}}', ['engine_class_name', 'scope_phid'], true);


        $this->createTable('{{%search_profilepanelconfiguration}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'profile_phid' => $this->string(64)->notNull(),
            'menu_item_key' => $this->string(64)->notNull(),
            'builtin_key' => $this->string(64)->null(),
            'menu_item_order' => $this->integer(11)->null(),
            'visibility' => $this->string(32)->notNull(),
            'menu_item_properties' => $this->text()->notNull(),
            'custom_phid' => $this->string(64)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-related_phid', '{{%search_profilepanelconfiguration}}', ['profile_phid', 'menu_item_order']);


        $this->createTable('{{%search_profilepanelconfigurationtransaction}}', [
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

        $this->createIndex('idx-object_phid', '{{%search_profilepanelconfigurationtransaction}}', 'object_phid');


        $this->createTable('{{%search_savedquery}}', [
            'id' => $this->primaryKey(),
            'engine_class_name' => $this->string(255)->notNull(),
            'parameters' => $this->getDb()->getSchema()->createColumnSchemaBuilder('longtext'),
            'query_key' => $this->string(12)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-related_phid', '{{%search_savedquery}}', ['query_key'], true);


        $this->createTable('{{%stopwords}}', [
            'id' => $this->primaryKey(),
            'value' => $this->string(32)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%search_document}}');
        $this->dropTable('{{%search_documentfield}}');
        $this->dropTable('{{%search_documentrelationship}}');
        $this->dropTable('{{%search_editengineconfiguration}}');
        $this->dropTable('{{%search_editengineconfigurationtransaction}}');
        $this->dropTable('{{%search_indexversion}}');
        $this->dropTable('{{%search_namedquery}}');
        $this->dropTable('{{%search_namedqueryconfig}}');
        $this->dropTable('{{%search_profilepanelconfiguration}}');
        $this->dropTable('{{%search_profilepanelconfigurationtransaction}}');
        $this->dropTable('{{%search_savedquery}}');
        $this->dropTable('{{%stopwords}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190114_063933_CreateSearch cannot be reverted.\n";

        return false;
    }
    */
}

<?php

use yii\db\Migration;

/**
 * Class m190605_061436_CreateOauthServer
 */
class m190605_061436_CreateOauthServer extends Migration
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

        $this->createTable('{{%oauth_server_oauthclientauthorization}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'user_phid' => $this->string(64)->notNull(),
            'client_phid' => $this->string(64)->notNull(),
            'scope' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-user_phid', '{{%oauth_server_oauthclientauthorization}}', ['user_phid', 'client_phid'], true);


        $this->createTable('{{%oauth_server_oauthserveraccesstoken}}', [
            'id' => $this->primaryKey(),
            'token' => $this->string(32)->notNull(),
            'user_phid' => $this->string(64)->notNull(),
            'client_phid' => $this->string(64)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-token', '{{%oauth_server_oauthserveraccesstoken}}', ['token'], true);


        $this->createTable('{{%oauth_server_oauthserverauthorizationcode}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(32)->notNull(),
            'client_phid' => $this->string(64)->notNull(),
            'client_secret' => $this->string(32)->notNull(),
            'user_phid' => $this->string(64)->notNull(),
            'redirect_uri' => $this->string(255)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-code', '{{%oauth_server_oauthserverauthorizationcode}}', ['code'], true);


        $this->createTable('{{%oauth_server_oauthserverclient}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'name' => $this->string(255)->notNull(),
            'secret' => $this->string(32)->notNull(),
            'redirect_uri' => $this->string(255)->notNull(),
            'creator_phid' => $this->string(64)->notNull(),
            'is_trusted' => $this->tinyInteger(1)->notNull(),
            'view_policy' => $this->string(64)->notNull(),
            'edit_policy' => $this->string(64)->notNull(),
            'is_disabled' => $this->tinyInteger(1)->notNull(),
            'is_system' => $this->tinyInteger(1)->defaultValue(0),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-creator_phid', '{{%oauth_server_oauthserverclient}}', ['creator_phid'], false);
        $this->createIndex('idx-is_system', '{{%oauth_server_oauthserverclient}}', ['is_system'], false);

        $this->createTable('{{%oauth_server_edge}}', [
            'id' => $this->primaryKey(),
            'src' => $this->string(64)->notNull(),
            'type' => $this->integer(11)->notNull(),
            'dst' => $this->string(64)->notNull(),
            'seq' => $this->integer(11)->notNull(),
            'data_id' => $this->integer(11)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-primary', '{{%oauth_server_edge}}', ['src', 'type', 'dst'], true);
        $this->createIndex('idx-key_dst', '{{%oauth_server_edge}}', ['dst', 'type', 'src'], true);
        $this->createIndex('idx-src', '{{%oauth_server_edge}}', ['src', 'type', 'created_at', 'seq'], true);


        $this->createTable('{{%oauth_server_edgedata}}', [
            'id' => $this->primaryKey(),
            'data' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createTable('{{%oauth_server_transactions}}', [
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

        $this->createIndex('idx-object_phid', '{{%oauth_server_transactions}}', 'object_phid');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable("{{%oauth_server_oauthclientauthorization}}");
        $this->dropTable("{{%oauth_server_oauthserveraccesstoken}}");
        $this->dropTable("{{%oauth_server_oauthserverauthorizationcode}}");
        $this->dropTable("{{%oauth_server_oauthserverclient}}");
        $this->dropTable("{{%oauth_server_edge}}");
        $this->dropTable("{{%oauth_server_edgedata}}");
        $this->dropTable("{{%oauth_server_transactions}}");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190605_061436_CreateOauthServer cannot be reverted.\n";

        return false;
    }
    */
}

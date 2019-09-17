<?php

use yii\db\Migration;

/**
 * _class m190215_101354_CreateAuth
 */
class m190215_101354_CreateAuth extends Migration
{
//    /**
//     * @author 陈妙威
//     */
//    public function init()
//    {
//        $this->execute("CREATE DATABASE IF NOT EXISTS orangins_auth");
//        /** @var \yii\db\Connection $connection */
//        $connection = Yii::$app->get('db_common');
//        $this->db = Yii::createObject([
//            'class' => 'yii\db\Connection',
//            'dsn' => strtr($connection->dsn, [
//                '{dbname}' => 'orangins_auth',
//            ]),
//            'username' => $connection->username,
//            'password' => $connection->password,
//            'charset' => 'utf8mb4',
//        ]);
//    }


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

        $this->createTable('{{%auth_factorconfig}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'user_phid' => $this->string(64)->notNull(),
            'factor_key' => $this->string(64)->notNull(),
            'factor_name' => $this->text()->notNull(),
            'factor_secret' => $this->text()->notNull(),
            'properties' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-user_phid', '{{%auth_factorconfig}}', 'user_phid');

        $this->createTable('{{%auth_hmackey}}', [
            'id' => $this->primaryKey(),
            'key_name' => $this->string(64)->notNull(),
            'key_value' => $this->string(128)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);


        $this->createIndex('idx-key_name', '{{%auth_hmackey}}', 'key_name', true);

        $this->createTable('{{%auth_password}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'object_phid' => $this->string(64)->notNull(),
            'password_type' => $this->string(64)->notNull(),
            'password_hash' => $this->string(128)->notNull(),
            'is_revoked' => $this->integer(1)->notNull(),
            'password_salt' => $this->string(64)->notNull(),
            'legacy_digest_format' => $this->string(32)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-object_phid', '{{%auth_password}}', ['object_phid', 'password_type']);


        $this->createTable('{{%auth_passwordtransaction}}', [
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

        $this->createIndex('idx-object_phid', '{{%auth_passwordtransaction}}', 'object_phid');


        $this->createTable('{{%auth_providerconfig}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'provider_class' => $this->string(128)->notNull(),
            'provider_type' => $this->string(32)->notNull(),
            'provider_domain' => $this->string(128)->notNull(),
            'is_enabled' => $this->integer(1)->notNull(),
            'should_allow_login' => $this->integer(1)->notNull(),
            'should_allow_registration' => $this->integer(1)->notNull(),
            'should_allow_link' => $this->integer(1)->notNull(),
            'should_allow_unlink' => $this->integer(1)->notNull(),
            'should_trust_emails' => $this->integer(1)->defaultValue(0)->notNull(),
            'should_auto_login' => $this->integer(1)->defaultValue(0)->notNull(),
            'properties' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-provider_type', '{{%auth_providerconfig}}', ['provider_type', 'provider_domain'], true);
        $this->createIndex('idx-provider_class', '{{%auth_providerconfig}}', 'provider_class');

        $this->createTable('{{%auth_providerconfigtransaction}}', [
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

        $this->createIndex('idx-object_phid', '{{%auth_providerconfigtransaction}}', 'object_phid');


        $this->createTable('{{%auth_sshkey}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'object_phid' => $this->string(64)->notNull(),
            'name' => $this->string(255)->notNull(),
            'key_type' => $this->string(255)->notNull(),
            'key_body' => $this->text()->notNull(),
            'key_comment' => $this->string(255)->notNull(),
            'key_index' => $this->string(12)->notNull(),
            'is_trusted' => $this->integer(1)->notNull(),
            'is_active' => $this->integer(1)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-key_index', '{{%auth_sshkey}}', ['key_index', 'is_active'], true);
        $this->createIndex('idx-object_phid', '{{%auth_sshkey}}', ['object_phid']);
        $this->createIndex('idx-is_active', '{{%auth_sshkey}}', ['is_active', 'object_phid']);

        $this->createTable('{{%auth_sshkeytransaction}}', [
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

        $this->createIndex('idx-object_phid', '{{%auth_sshkeytransaction}}', 'object_phid');

        $this->createTable('{{%auth_temporarytoken}}', [
            'id' => $this->primaryKey(),
            'token_resource' => $this->string(64)->notNull(),
            'token_type' => $this->string(64)->notNull(),
            'token_expires' => $this->integer(11)->notNull(),
            'token_code' => $this->string(64)->notNull(),
            'user_phid' => $this->string(64)->null(),
            'properties' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-token_resource', '{{%auth_temporarytoken}}', ['token_resource', 'token_type', 'token_code'], true);
        $this->createIndex('idx-token_expires', '{{%auth_temporarytoken}}', ['token_expires']);
        $this->createIndex('idx-user_phid', '{{%auth_temporarytoken}}', ['user_phid']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable("auth_factorconfig");
        $this->dropTable("auth_hmackey");
        $this->dropTable("auth_password");
        $this->dropTable("auth_passwordtransaction");
        $this->dropTable("auth_providerconfig");
        $this->dropTable("auth_providerconfigtransaction");
        $this->dropTable("auth_sshkey");
        $this->dropTable("auth_sshkeytransaction");
        $this->dropTable("auth_temporarytoken");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190215_101354_CreateAuth cannot be reverted.\n";

        return false;
    }
    */
}

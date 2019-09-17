<?php

use yii\db\Migration;

class m130524_201442_init extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
//            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_bin ENGINE=InnoDB';
        }

        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->null()->unique()->comment("PHID"),
            'username' => $this->string(150)->notNull()->unique(),
            'real_name' => $this->string(64)->notNull()->comment("真是名称"),
            'profile_image_phid' => $this->string(64)->null()->comment("缩略图"),
            'conduit_certificate' => $this->string(255)->notNull(),
            'is_system_agent' => $this->integer(1)->notNull()->defaultValue(0),
            'is_disabled' => $this->integer(1)->notNull()->defaultValue(0),
            'is_admin' => $this->integer(1)->notNull()->defaultValue(0)->comment("是否管理员"),
            'is_email_verified' => $this->integer(1)->notNull()->defaultValue(0),
            'is_approved' => $this->integer(1)->notNull()->defaultValue(1)->comment("是否通过"),
            'account_secret' => $this->string(64)->notNull()->comment("是否通过"),
            'is_mailing_list' => $this->integer(1)->defaultValue(0),
            'is_enrolled_in_multi_factor' => $this->integer(1)->notNull()->defaultValue(0),
            'availability_cache' => $this->string(255)->null(),
            'availability_cache_ttl' => $this->integer(11)->null(),
            'default_profile_image_phid' => $this->string(64)->null(),
            'default_profile_image_version' => $this->string(64)->null(),
            'is_manager' => $this->tinyInteger(1)->defaultValue(0)->comment('是否为管理员'),
            'is_merchant' => $this->integer(1)->defaultValue(0),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);


        $this->createTable('{{%user_profile}}', [
            'id' => $this->primaryKey(),
            'user_phid' => $this->string(64)->notNull()->unique(),
            'title' => $this->string(64)->null()->comment("名称"),
            'icon' => $this->string(64)->null()->comment("名称"),
            'blurb' => $this->text()->null()->comment("介绍"),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),

        ], $tableOptions);


        $this->createTable('{{%user_transactions}}', [
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


        $this->createTable('{{%user_authinvite}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'author_phid' => $this->string(64)->notNull(),
            'email_address' => $this->string(128)->notNull()->unique(),
            'verification_hash' => $this->string(12)->notNull()->unique(),
            'accepted_by_phid' => $this->string(64)->null(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),

        ], $tableOptions);

        $this->createTable('{{%user_cache}}', [
            'id' => $this->primaryKey(),
            'user_phid' => $this->string(64)->notNull(),
            'cache_index' => $this->string(12)->notNull(),
            'cache_key' => $this->string(255)->notNull(),
            'cache_data' => $this->text()->notNull(),
            'cache_type' => $this->string(32)->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);


        $this->createIndex('idx-user_phid', '{{%user_cache}}', ['user_phid', 'cache_index'], true);
        $this->createIndex('idx-cache_index', '{{%user_cache}}', 'cache_index');
        $this->createIndex('idx-cache_type', '{{%user_cache}}', 'cache_type');

        $this->createTable('{{%user_email}}', [
            'id' => $this->primaryKey(),
            'user_phid' => $this->string(64)->notNull(),
            'address' => $this->string(128)->notNull()->unique(),
            'is_verified' => $this->tinyInteger(1)->defaultValue(0)->notNull(),
            'is_primary' => $this->tinyInteger(1)->defaultValue(0)->notNull(),
            'verification_code' => $this->string(64)->null(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-user_phid', '{{%user_email}}', ['user_phid', 'is_primary']);

        $this->createTable('{{%user_externalaccount}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'user_phid' => $this->string(64)->null(),
            'account_type' => $this->string(16)->notNull(),
            'account_domain' => $this->string(64)->notNull(),
            'account_secret' => $this->text()->null(),
            'account_id' => $this->string(64)->notNull(),
            'display_name' => $this->string(255)->null(),
            'username' => $this->string(255)->null(),
            'real_name' => $this->string(255)->null(),
            'email' => $this->string(255)->null(),
            'email_verified' => $this->tinyInteger(1)->notNull(),
            'account_uri' => $this->string(255)->null(),
            'profile_image_phid' => $this->string(64)->null(),
            'properties' => $this->text()->notNull(),
            'provider_config_phid' => $this->string(64)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-account_details', '{{%user_externalaccount}}', ['account_type', 'account_domain', 'account_id'], true);
        $this->createIndex('idx-user_phid', '{{%user_externalaccount}}', 'user_phid');


        $this->createTable('{{%user_log}}', [
            'id' => $this->primaryKey(),
            'actor_phid' => $this->string(64)->null(),
            'user_phid' => $this->string(64)->notNull(),
            'action' => $this->string(16)->notNull(),
            'old_value' => $this->string(64)->notNull(),
            'new_value' => $this->text()->notNull(),
            'details' => $this->string(64)->notNull(),
            'remote_addr' => $this->string(255)->notNull(),
            'session' => $this->string(40)->null(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-created_at', '{{%user_log}}', 'created_at');
        $this->createIndex('idx-actor_phid', '{{%user_log}}', ['actor_phid', 'created_at']);
        $this->createIndex('idx-user_phid', '{{%user_log}}', ['user_phid', 'created_at']);
        $this->createIndex('idx-action', '{{%user_log}}', ['action', 'created_at']);
        $this->createIndex('idx-remote_addr', '{{%user_log}}', ['remote_addr', 'created_at']);
        $this->createIndex('idx-session', '{{%user_log}}', ['session', 'created_at']);

        $this->createTable('{{%user_nametoken}}', [
            'id' => $this->primaryKey(),
            'token' => $this->string(255)->notNull(),
            'user_id' => $this->integer(11)->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-user_id', '{{%user_nametoken}}', 'user_id');

        $this->createTable('{{%user_preferences}}', [
            'id' => $this->primaryKey(),
            'user_phid' => $this->string(64)->null()->unique(),
            'preferences' => $this->text()->notNull(),
            'phid' => $this->string(64)->notNull()->unique(),
            'builtin_key' => $this->string(32)->null()->unique(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createTable('{{%user_preferencestransaction}}', [
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

        $this->createIndex('idx-object_phid', '{{%user_preferencestransaction}}', 'object_phid');

        $this->createTable('{{%user_edge}}', [
            'id' => $this->primaryKey(),
            'src' => $this->string(64)->notNull(),
            'type' => $this->integer(11)->notNull(),
            'dst' => $this->string(64)->notNull(),
            'seq' => $this->integer(11)->notNull(),
            'data_id' => $this->integer(11)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-primary', '{{%user_edge}}', ['src', 'type', 'dst'], true);
        $this->createIndex('idx-key_dst', '{{%user_edge}}', ['dst', 'type', 'src'], true);
        $this->createIndex('idx-src', '{{%user_edge}}', ['src', 'type', 'created_at', 'seq'], true);

        $this->createTable('{{%user_edgedata}}', [
            'id' => $this->primaryKey(),
            'data' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);


        $this->createTable('{{%user_user_fdocument}}', [
            'id' => $this->primaryKey(),
            'object_phid' => $this->string(64)->notNull()->unique(),
            'is_closed' => $this->tinyInteger(1)->notNull(),

            'author_phid' => $this->string(64)->null(),
            'owner_phid' => $this->string(64)->null(),

            'epoch_created' => $this->integer(11)->null(),
            'epoch_modified' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-author_phid', '{{%user_user_fdocument}}', ['author_phid'], false);
        $this->createIndex('idx-owner_phid', '{{%user_user_fdocument}}', ['owner_phid'], false);
        $this->createIndex('idx-epoch_created', '{{%user_user_fdocument}}', ['epoch_created'], false);
        $this->createIndex('idx-epoch_modified', '{{%user_user_fdocument}}', ['epoch_modified'], false);

        $this->createTable('{{%user_user_ffield}}', [
            'id' => $this->primaryKey(),
            'document_id' => $this->integer(11)->notNull(),
            'field_key' => $this->string(4)->notNull(),
            'raw_corpus' => $this->text()->notNull(),
            'term_corpus' => $this->text()->notNull(),
            'normal_corpus' => $this->text()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-document_id', '{{%user_user_ffield}}', ['document_id', 'field_key'], false);

        $this->createTable('{{%user_user_fngrams}}', [
            'id' => $this->primaryKey(),
            'document_id' => $this->integer(11)->notNull(),
            'ngram' => $this->string(3)->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-ngram', '{{%user_user_fngrams}}', ['ngram', 'document_id'], false);
        $this->createIndex('idx-document_id', '{{%user_user_fngrams}}', ['document_id'], false);


        $this->createTable('{{%user_user_fngrams_common}}', [
            'id' => $this->primaryKey(),
            'ngram' => $this->string(3)->notNull()->unique(),
            'needs_collection' => $this->tinyInteger(1)->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-needs_collection', '{{%user_user_fngrams_common}}', ['needs_collection'], false);
    }


    public function down()
    {
        $this->dropTable('{{%user}}');

        $this->dropTable("{{%user_profile}}");
        $this->dropTable("{{%user_transaction}}");


        $this->dropTable('{{%user_edge}}');
        $this->dropTable('{{%user_edgedata}}');

        $this->dropTable("{{%user_authinvite}}");
        $this->dropTable("{{%user_cache}}");
        $this->dropTable("{{%user_email}}");
        $this->dropTable("{{%user_externalaccount}}");
        $this->dropTable("{{%user_log}}");
        $this->dropTable("{{%user_nametoken}}");
        $this->dropTable("{{%user_preferences}}");
        $this->dropTable("{{%user_preferencestransaction}}");
        $this->dropTable('{{%user_user_fdocument}}');
        $this->dropTable('{{%user_user_ffield}}');
        $this->dropTable('{{%user_user_fngrams}}');
        $this->dropTable('{{%user_user_fngrams_common}}');
    }
}

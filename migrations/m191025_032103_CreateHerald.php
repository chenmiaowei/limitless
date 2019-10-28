<?php

use yii\db\Migration;

/**
 * Class m191025_032103_CreateHerald
 */
class m191025_032103_CreateHerald extends Migration
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
        $this->createTable('{{%herald_action}}', [
            'id' => $this->primaryKey(),
            'rule_id' => $this->integer(11)->notNull(),
            'action' => $this->string(255)->notNull(),
            'target' => $this->text()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-rule_id', '{{%herald_action}}', ['rule_id'], false);

        $this->createTable('{{%herald_condition}}', [
            'id' => $this->primaryKey(),
            'rule_id' => $this->integer(11)->notNull(),
            'field_name' => $this->string(255)->notNull(),
            'field_condition' => $this->string(255)->notNull(),
            'value' => $this->text()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-rule_id', '{{%herald_condition}}', ['rule_id'], false);

        $this->createTable('{{%herald_rule}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'name' => $this->string(255)->notNull(),
            'author_phid' => $this->string(64)->notNull(),
            'content_type' => $this->string(255)->notNull(),
            'must_match_all' => $this->integer(1)->notNull(),
            'config_version' => $this->integer(10)->defaultValue(1)->notNull(),
            'repetition_policy' => $this->string(32)->notNull(),
            'rule_type' => $this->string(32)->notNull(),
            'is_disabled' => $this->integer(1)->defaultValue(0)->notNull(),
            'trigger_object_phid' => $this->string(64)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-trigger_object_phid', '{{%herald_rule}}', ['trigger_object_phid'], false);
        $this->createIndex('idx-name', '{{%herald_rule}}', ['name'], false);
        $this->createIndex('idx-author_phid', '{{%herald_rule}}', ['author_phid'], false);
        $this->createIndex('idx-rule_type', '{{%herald_rule}}', ['rule_type'], false);

        $this->createTable('{{%herald_ruleapplied}}', [
            'rule_id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull(),
        ], $tableOptions);
        $this->createIndex('idx-phid', '{{%herald_ruleapplied}}', ['phid'], false);

        $this->createTable('{{%herald_edge}}', [
            'id' => $this->primaryKey(),
            'src' => $this->string(64)->notNull(),
            'type' => $this->integer(11)->notNull(),
            'dst' => $this->string(64)->notNull(),
            'seq' => $this->integer(11)->notNull(),
            'data_id' => $this->integer(11)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-primary', '{{%herald_edge}}', ['src', 'type', 'dst'], true);
        $this->createIndex('idx-key_dst', '{{%herald_edge}}', ['dst', 'type', 'src'], true);
        $this->createIndex('idx-src', '{{%herald_edge}}', ['src', 'type', 'created_at', 'seq'], true);


        $this->createTable('{{%herald_edgedata}}', [
            'id' => $this->primaryKey(),
            'data' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createTable('{{%herald_ruletransaction}}', [
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

        $this->createIndex('idx-object_phid', '{{%herald_ruletransaction}}', 'object_phid');


        $this->createTable('{{%herald_savedheader}}', [
            'phid' => $this->primaryKey(),
            'header' => $this->text()->notNull(),
        ], $tableOptions);

        $this->createTable('{{%herald_transcript}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'time' => $this->integer(11)->notNull(),
            'host' => $this->string(255)->notNull(),
            'duration' => $this->double()->notNull(),
            'object_phid' => $this->string(64)->notNull(),
            'dry_run' => $this->integer(1)->notNull(),
            'object_transcript' => $this->binary(429496729)->notNull(),
            'rule_transcripts' => $this->binary(429496729)->notNull(),
            'condition_transcripts' => $this->binary(429496729)->notNull(),
            'apply_transcripts' => $this->binary(429496729)->notNull(),
            'garbage_collected' => $this->integer(1)->defaultValue(0)->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-object_phid', '{{%herald_transcript}}', 'object_phid');
        $this->createIndex('idx-garbage_collected', '{{%herald_transcript}}', ['garbage_collected', 'time']);


        $this->createTable('{{%herald_webhook}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'name' => $this->string(128)->notNull(),
            'webhook_uri' => $this->string(255)->notNull(),
            'view_policy' => $this->string(64)->notNull(),
            'edit_policy' => $this->string(64)->notNull(),
            'status' => $this->string(32)->notNull(),
            'hmac_key' => $this->string(32)->notNull(),
            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-status', '{{%herald_webhook}}', 'status');


        $this->createTable('{{%herald_webhooktransaction}}', [
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

        $this->createIndex('idx-object_phid', '{{%herald_webhooktransaction}}', 'object_phid');


        $this->createTable('{{%herald_webhookrequest}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'webhook_phid' => $this->string(128)->notNull(),
            'object_phid' => $this->string(255)->notNull(),
            'status' => $this->string(64)->notNull(),
            'properties' => $this->string(64)->notNull(),
            'last_request_result' => $this->string(32)->notNull(),
            'last_request_epoch' => $this->string(32)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-webhook_phid', '{{%herald_webhookrequest}}', ['webhook_phid', 'last_request_result', 'last_request_epoch']);
        $this->createIndex('idx-created_at', '{{%herald_webhookrequest}}', ['created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%herald_action}}');
        $this->dropTable('{{%herald_condition}}');
        $this->dropTable('{{%herald_rule}}');
        $this->dropTable('{{%herald_ruleapplied}}');
        $this->dropTable('{{%herald_ruletransaction}}');
        $this->dropTable('{{%herald_savedheader}}');
        $this->dropTable('{{%herald_transcript}}');
        $this->dropTable('{{%herald_webhook}}');
        $this->dropTable('{{%herald_webhookrequest}}');
        $this->dropTable('{{%herald_webhooktransaction}}');
        $this->dropTable('{{%herald_edge}}');
        $this->dropTable('{{%herald_edgedata}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191025_032103_CreateHerald cannot be reverted.\n";

        return false;
    }
    */
}

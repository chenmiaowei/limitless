<?php

use yii\db\Migration;

/**
 * Class m181209_031316_CreateWorker
 */
class m181209_031316_CreateWorker extends Migration
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

        $this->createTable('{{%daemon_locklog}}', [
            'id' => $this->primaryKey(),
            'lock_name' => $this->string(64)->notNull()->unique(),
            'lock_released' => $this->integer(11)->null(),
            'lock_parameters' => $this->text()->notNull(),
            'lock_context' => $this->text()->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-lock_name', '{{%daemon_locklog}}', 'lock_name');
        $this->createIndex('idx-created_at', '{{%daemon_locklog}}', 'created_at');

        $this->createTable('{{%daemon_log}}', [
            'id' => $this->primaryKey(),
            'daemon' => $this->string(255),
            'host' => $this->string(255),
            'pid' => $this->integer(11),
            'argv' => $this->text(),
            'explicit_argv' => $this->text(),
            'running_as_user' => $this->string(255)->null(),
            'daemon_id' => $this->string(64)->unique(),
            'status' => $this->string(16),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-status', '{{%daemon_log}}', 'status');
        $this->createIndex('idx-updated_at', '{{%daemon_log}}', 'updated_at');

        $this->createTable('{{%daemon_logevent}}', [
            'id' => $this->primaryKey(),
            'log_id' => $this->integer(11),
            'log_type' => $this->string(4),
            'message' => $this->text(),
            'epoch' => $this->integer(11),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-log_id', '{{%daemon_logevent}}', 'log_id');
        $this->createIndex('idx-epoch', '{{%daemon_logevent}}', 'epoch');


        $this->createTable('{{%worker_lisk_counter}}', [
            'id' => $this->primaryKey(),
            'counter_name' => $this->string(32)->unique(),
            'counter_value' => $this->bigInteger(20),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);


        $this->createTable('{{%worker_activetask}}', [
            'id' => $this->primaryKey(),
            'task_class' => $this->string(64),
            'lease_owner' => $this->string(64)->null(),
            'lease_expires' => $this->integer(11)->null(),
            'failure_count' => $this->integer(11),
            'data_id' => $this->integer(11)->null(),
            'failure_time' => $this->integer(11)->null(),
            'priority' => $this->integer(11),
            'object_phid' => $this->string(64)->null(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-lease_expires', '{{%worker_activetask}}', 'lease_expires');
        $this->createIndex('idx-lease_owner', '{{%worker_activetask}}', 'lease_owner');
        $this->createIndex('idx-failure_time', '{{%worker_activetask}}', 'failure_time');
        $this->createIndex('idx-task_class', '{{%worker_activetask}}', 'task_class');
        $this->createIndex('idx-leaseOwner-priority', '{{%worker_activetask}}', ['lease_owner', 'priority', 'id']);
        $this->createIndex('idx-object_phid', '{{%worker_activetask}}', 'object_phid');


        $this->createTable('{{%worker_archivetask}}', [
            'id' => $this->primaryKey(),
            'task_class' => $this->string(64),
            'lease_owner' => $this->string(64)->null(),
            'lease_expires' => $this->integer(11)->null(),
            'failure_count' => $this->integer(11),
            'data_id' => $this->integer(11)->null(),
            'result' => $this->integer(11)->null(),
            'duration' => $this->bigInteger(20)->null(),
            'priority' => $this->integer(11),
            'object_phid' => $this->string(64)->null(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-created_at', '{{%worker_archivetask}}', 'created_at');
        $this->createIndex('idx-leaseOwner-priority', '{{%worker_archivetask}}', ['lease_owner', 'priority', 'id']);
        $this->createIndex('idx-updated_at', '{{%worker_archivetask}}', 'updated_at');
        $this->createIndex('idx-object_phid', '{{%worker_archivetask}}', 'object_phid');

        $this->createTable('{{%worker_bulkjob}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'author_phid' => $this->string(64),
            'job_type_key' => $this->string(32),
            'status' => $this->string(32),
            'parameters' => $this->text(),
            'size' => $this->integer(11),
            'is_silent' => $this->integer(1),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-author_phid', '{{%worker_bulkjob}}', 'author_phid');
        $this->createIndex('idx-job_type_key', '{{%worker_bulkjob}}', 'job_type_key');
        $this->createIndex('idx-status', '{{%worker_bulkjob}}', 'status');

        $this->createTable('{{%worker_bulkjobtransaction}}', [
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
        $this->createIndex('idx-object_phid', '{{%worker_bulkjobtransaction}}', 'object_phid');


        $this->createTable('{{%worker_bulktask}}', [
            'id' => $this->primaryKey(),
            'bulk_job_phid' => $this->string(64),
            'object_phid' => $this->string(64),
            'status' => $this->string(32),
            'data' => $this->text(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-bulk_job_phid', '{{%worker_bulktask}}', 'bulk_job_phid');
        $this->createIndex('idx-object_phid', '{{%worker_bulktask}}', 'object_phid');

        $this->createTable('{{%worker_taskdata}}', [
            'id' => $this->primaryKey(),
            'data' => $this->text(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createTable('{{%worker_trigger}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'trigger_version' => $this->integer(11),
            'clock_class' => $this->string(64),
            'clock_properties' => $this->text(),
            'action_class' => $this->string(64),
            'action_properties' => $this->text(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-trigger_version', '{{%worker_trigger}}', 'trigger_version');

         $this->createTable('{{%worker_triggerevent}}', [
            'id' => $this->primaryKey(),
            'trigger_id' => $this->integer(11),
            'last_event_epoch' => $this->integer(11)->null(),
            'next_event_epoch' => $this->integer(11)->null(),


             'created_at' => $this->integer(11)->null(),
             'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-trigger_id', '{{%worker_triggerevent}}', 'trigger_id');
        $this->createIndex('idx-next_event_epoch', '{{%worker_triggerevent}}', 'next_event_epoch');

        $this->createTable('{{%worker_edge}}', [
            'id' => $this->primaryKey(),
            'src' => $this->string(64)->notNull(),
            'type' => $this->integer(11)->notNull(),
            'dst' => $this->string(64)->notNull(),
            'seq' => $this->integer(11)->notNull(),
            'data_id' => $this->integer(11)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-primary', '{{%worker_edge}}', ['src', 'type', 'dst'], true);
        $this->createIndex('idx-key_dst', '{{%worker_edge}}', ['dst', 'type', 'src'], true);
        $this->createIndex('idx-src', '{{%worker_edge}}', ['src', 'type', 'created_at', 'seq'], true);


        $this->createTable('{{%worker_edgedata}}', [
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
        $this->dropTable("{{%daemon_locklog}}");
        $this->dropTable("{{%daemon_log}}");
        $this->dropTable("{{%daemon_logevent}}");
        $this->dropTable("{{%worker_lisk_counter}}");
        $this->dropTable("{{%worker_activetask}}");
        $this->dropTable("{{%worker_archivetask}}");
        $this->dropTable("{{%worker_bulkjob}}");
        $this->dropTable("{{%worker_bulkjobtransaction}}");
        $this->dropTable("{{%worker_bulktask}}");
        $this->dropTable("{{%worker_taskdata}}");
        $this->dropTable("{{%worker_trigger}}");
        $this->dropTable("{{%worker_triggerevent}}");
        $this->dropTable("{{%worker_edge}}");
        $this->dropTable("{{%worker_edgedata}}");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181209_031316_CreateWorker cannot be reverted.\n";

        return false;
    }
    */
}

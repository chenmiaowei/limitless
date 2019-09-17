<?php

use yii\db\Migration;

/**
 * Class m190408_035241_CreateConduit
 */
class m190408_035241_CreateConduit extends Migration
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

        $this->createTable('{{%conduit_certificatetoken}}', [
            'id' => $this->primaryKey(),
            'user_phid' => $this->string(64)->notNull(),
            'token' => $this->string(64)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-user_phid', '{{%conduit_certificatetoken}}', ['user_phid'], true);
        $this->createIndex('idx-token', '{{%conduit_certificatetoken}}', ['token'], true);

        $this->createTable('{{%conduit_methodcalllog}}', [
            'id' => $this->primaryKey(),
            'connection_id' => $this->integer(20)->null(),
            'method' => $this->string(64)->notNull(),
            'error' => $this->string(255)->notNull(),
            'duration' => $this->integer(20)->notNull(),
            'caller_phid' => $this->string(64)->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-method', '{{%conduit_methodcalllog}}', ['method'], false);
        $this->createIndex('idx-caller_phid', '{{%conduit_methodcalllog}}', ['caller_phid', 'method'], false);
        $this->createIndex('idx-created_at', '{{%conduit_methodcalllog}}', ['created_at'], false);


        $this->createTable('{{%conduit_token}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'object_phid' => $this->string(64)->null(),
            'token_type' => $this->string(32)->notNull(),
            'token' => $this->string(32)->notNull(),
            'expires' => $this->integer(10)->null(),
            'parameters' => $this->text()->null(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-token', '{{%conduit_token}}', ['token'], true);
        $this->createIndex('idx-object_phid', '{{%conduit_token}}', ['object_phid', 'token_type'], false);
        $this->createIndex('idx-expires', '{{%conduit_token}}', ['expires'], false);

        $this->createTable('{{%conduit_token_transaction}}', [
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

        $this->createIndex('idx-object_phid', '{{%conduit_token_transaction}}', 'object_phid');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable("{{%conduit_certificatetoken}}");
        $this->dropTable("{{%conduit_methodcalllog}}");
        $this->dropTable("{{%conduit_token}}");
        $this->dropTable("{{%conduit_token_transaction}}");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190408_035241_CreateConduit cannot be reverted.\n";

        return false;
    }
    */
}

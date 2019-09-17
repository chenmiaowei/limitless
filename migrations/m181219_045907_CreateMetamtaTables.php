<?php

use yii\db\Migration;

/**
 * Class m181219_045907_CreateMetamtaTables
 */
class m181219_045907_CreateMetamtaTables extends Migration
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

        $this->createTable('{{%metamta_applicationemail}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'application_phid' => $this->string(64)->notNull(),
            'address' => $this->string(128)->notNull(),
            'space_phid' => $this->string(64)->null(),
            'config_data' => $this->text()->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-address', '{{%metamta_applicationemail}}', 'address', true);
        $this->createIndex('idx-application_phid', '{{%metamta_applicationemail}}', 'application_phid');
        $this->createIndex('idx-space_phid', '{{%metamta_applicationemail}}', 'space_phid');

        $this->createTable('{{%metamta_applicationemailtransaction}}', [
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

        $this->createIndex('idx-object_phid', '{{%metamta_applicationemailtransaction}}', 'object_phid');

        $this->createTable('{{%metamta_mail}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'actor_phid' => $this->string(64)->null(),
            'parameters' => $this->text()->notNull(),
            'status' => $this->string(32)->notNull(),
            'message' => $this->text()->null(),
            'related_phid' => $this->string(64)->null(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-related_phid', '{{%metamta_mail}}', 'related_phid');
        $this->createIndex('idx-created_at', '{{%metamta_mail}}', 'created_at');
        $this->createIndex('idx-status', '{{%metamta_mail}}', 'status');
        $this->createIndex('idx-actor_phid', '{{%metamta_mail}}', 'actor_phid');

        $this->createTable('{{%metamta_mailproperties}}', [
            'id' => $this->primaryKey(),
            'object_phid' => $this->string(64)->notNull()->unique(),
            'mail_properties' => $this->text()->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);


        $this->createTable('{{%metamta_receivedmail}}', [
            'id' => $this->primaryKey(),
            'headers' => $this->text()->notNull(),
            'bodies' => $this->text()->notNull(),
            'attachments' => $this->text()->notNull(),
            'related_phid' => $this->string(64)->null(),
            'author_phid' => $this->string(64)->null(),
            'message' => $this->text()->null(),
            'message_id_hash' => $this->string(12)->notNull(),
            'status' => $this->string(32)->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-related_phid', '{{%metamta_receivedmail}}', 'related_phid');
        $this->createIndex('idx-author_phid', '{{%metamta_receivedmail}}', 'author_phid');
        $this->createIndex('idx-message_id_hash', '{{%metamta_receivedmail}}', 'message_id_hash');
        $this->createIndex('idx-created_at', '{{%metamta_receivedmail}}', 'created_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable("{{%metamta_applicationemail}}");
        $this->dropTable("{{%metamta_applicationemailtransaction}}");
        $this->dropTable("{{%metamta_mail}}");
        $this->dropTable("{{%metamta_mailproperties}}");
        $this->dropTable("{{%metamta_receivedmail}}");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181219_045907_CreateMetamtaTables cannot be reverted.\n";

        return false;
    }
    */
}

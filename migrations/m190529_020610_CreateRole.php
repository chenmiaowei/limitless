<?php

use yii\db\Migration;

/**
 * Class m190529_020610_CreateRole
 */
class m190529_020610_CreateRole extends Migration
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

        $this->createTable('{{%rbac_role}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'name' => $this->string(64)->notNull()->comment("权限名称")->unique(),
            'description' => $this->string(255)->notNull()->comment("权限解释"),
            'rule_name' => $this->string(64)->null()->comment("规则"),
            'parameters' => $this->text()->null(),
            'status' => $this->string(64)->notNull()->defaultValue('ACTIVE')->comment("状态"),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-name', '{{%rbac_role}}', ['name'], false);

        $this->createTable('{{%rbac_role_transactions}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->notNull()->unique(),
            'author_phid' => $this->string(64)->notNull()->comment("作者"),
            'object_phid' => $this->string(64)->notNull()->comment("对象"),
            'view_policy' => $this->string(64)->notNull()->comment("显示权限"),
            'edit_policy' => $this->string(64)->notNull()->comment("编辑权限"),
            'comment_phid' => $this->string(64)->null()->comment("评论"),
            'comment_version' => $this->integer(10)->notNull()->comment("评论"),
            'transaction_type' => $this->string(32)->notNull()->comment("交易类型"),
            'old_value' => $this->text()->notNull()->comment("旧值"),
            'new_value' => $this->text()->notNull()->comment("新值"),
            'metadata' => $this->text()->notNull()->comment("额外数据"),
            'content_source' => $this->text()->notNull()->comment("内容"),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);


        $this->createIndex('idx-object_phid', '{{%rbac_role_transactions}}', ['object_phid']);


        $this->createTable('{{%rbac_role_capability}}', [
            'id' => $this->primaryKey(),
            'object_phid' => $this->string(64)->notNull(),
            'capability' => $this->string(64)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-object_phid', '{{%rbac_role_capability}}', ['object_phid', 'capability'], true);

        $this->createTable('{{%rbac_user}}', [
            'id' => $this->primaryKey(),
            'user_phid' => $this->string(64)->notNull(),
            'object_phid' => $this->string(64)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-object_phid', '{{%rbac_user}}', ['object_phid', 'user_phid'], true);
        $this->createIndex('idx-user_phid', '{{%rbac_user}}', ['user_phid'], false);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
       $this->dropTable("{{%rbac_role}}");
       $this->dropTable("{{%rbac_role_transactions}}");
       $this->dropTable("{{%rbac_role_capability}}");
       $this->dropTable("{{%rbac_user}}");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190529_020610_CreateRole cannot be reverted.\n";

        return false;
    }
    */
}

<?php

use yii\db\Migration;

/**
 * Class m190408_080212_CreateSession
 */
class m190408_080212_CreateSession extends Migration
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

        $this->createTable('{{%session}}', [
            'id' => $this->primaryKey(),
            'user_phid' => $this->string(64)->notNull(),
            'type' => $this->string(32)->notNull(),
            'session_key' => $this->string(40)->notNull(),
            'session_start' => $this->integer(10)->notNull(),
            'session_expires' => $this->integer(10)->notNull(),
            'high_security_until' => $this->integer(10)->null(),
            'is_partial' => $this->integer(1)->defaultValue(0)->notNull(),
            'signed_legalpad_documents' => $this->integer(1)->defaultValue(0)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-session_key', '{{%session}}', ['session_key'], true);
        $this->createIndex('idx-user_phid', '{{%session}}', ['user_phid', 'type'], false);


    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable("{{%session}}");
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190408_080212_CreateSession cannot be reverted.\n";

        return false;
    }
    */
}

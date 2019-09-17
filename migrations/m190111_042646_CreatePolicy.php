<?php

use yii\db\Migration;

/**
 * Class m190111_042646_CreatePolicy
 */
class m190111_042646_CreatePolicy extends Migration
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

        $this->createTable('{{%policy}}', [
            'id' => $this->primaryKey(),
            'phid' => $this->string(64)->unique(),
            'rules' => $this->text()->notNull(),
            'default_action' => $this->string(32)->notNull(),


            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%policy}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190111_042646_CreatePolicy cannot be reverted.\n";

        return false;
    }
    */
}

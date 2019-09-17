<?php

use yii\db\Migration;

/**
 * Class m190409_134104_CreateSystem
 */
class m190409_134104_CreateSystem extends Migration
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

        $this->createTable('{{%system_actionlog}}', [
            'id' => $this->primaryKey(),
            'actor_hash' => $this->string(16)->notNull(),
            'actor_identity' => $this->string(255)->notNull(),
            'action' => $this->string(32)->notNull(),
            'score' => $this->double()->notNull(),
            'epoch' => $this->integer(11)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-epoch', '{{%system_actionlog}}', ['epoch'], false);
        $this->createIndex('idx-actor_hash', '{{%system_actionlog}}', ['actor_hash', 'action', 'epoch'], false);


         $this->createTable('{{%system_destructionlog}}', [
            'id' => $this->primaryKey(),
            'object_class' => $this->string(16)->notNull(),
            'root_log_id' => $this->string(255)->notNull(),
            'object_phid' => $this->string(32)->notNull(),
            'object_monogram' => $this->double()->notNull(),
            'epoch' => $this->integer(11)->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-epoch', '{{%system_destructionlog}}', ['epoch'], false);

    }


    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190409_134104_CreateSystem cannot be reverted.\n";

        return false;
    }
    */
}

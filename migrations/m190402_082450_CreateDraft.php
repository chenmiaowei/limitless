<?php

use yii\db\Migration;

/**
 * Class m190402_082450_CreateDraft
 */
class m190402_082450_CreateDraft extends Migration
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

        $this->createTable('{{%draft}}', [
            'id' => $this->primaryKey(),
            'author_phid' => $this->string(64)->notNull(),
            'draft_key' => $this->string(64)->notNull(),
            'draft' => $this->text()->notNull(),
            'metadata' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-author_phid', '{{%draft}}', ['author_phid', 'draft_key'], true);

        $this->createTable('{{%draft_versioneddraft}}', [
            'id' => $this->primaryKey(),
            'object_phid' => $this->string(64)->notNull(),
            'author_phid' => $this->string(64)->notNull(),
            'version' => $this->integer(11)->notNull(),
            'properties' => $this->text()->notNull(),

            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);
        $this->createIndex('idx-object_phid', '{{%draft_versioneddraft}}', ['object_phid', 'author_phid', 'version'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%draft}}');
        $this->dropTable('{{%draft_versioneddraft}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190402_082450_CreateDraft cannot be reverted.\n";

        return false;
    }
    */
}

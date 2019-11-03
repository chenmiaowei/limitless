<?php

use yii\db\Migration;

/**
 * Class m191103_022845_CreateUserCustomField
 */
class m191103_022845_CreateUserCustomField extends Migration
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
        $this->createTable('{{%user_configuredcustomfieldstorage}}', [
            'id' => $this->primaryKey(),
            'object_phid' => $this->string(64)->notNull(),
            '`field_index` BINARY(12) NOT NULL',
//            'field_index' => $this->binary(12)->notNull(),
            'field_value' => $this->text()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-object_phid', '{{%user_configuredcustomfieldstorage}}', ['object_phid', 'field_index(12)'], true);

        $this->createTable('{{%user_customfieldnumericindex}}', [
            'id' => $this->primaryKey(),
            'object_phid' => $this->string(64)->notNull(),
            '`index_key` BINARY(12) NOT NULL',
            '`index_value` BINARY(20) NOT NULL',
//            'index_key' => $this->binary(12)->notNull(),
//            'index_value' => $this->binary(20)->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-object_phid', '{{%user_customfieldnumericindex}}', ['object_phid', 'index_key(12)', 'index_value(20)'], false);
        $this->createIndex('idx-index_key', '{{%user_customfieldnumericindex}}', ['index_key(12)', 'index_value(20)'], false);

        $this->createTable('{{%user_customfieldstringindex}}', [
            'id' => $this->primaryKey(),
            'object_phid' => $this->string(64)->notNull(),
            '`index_key` BINARY(12) NOT NULL',
//            'index_key' => $this->binary(12)->notNull(),
            'index_value' => $this->text()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-object_phid', '{{%user_customfieldstringindex}}', ['object_phid', 'index_key(12)', 'index_value(64)'], false);
        $this->createIndex('idx-index_key', '{{%user_customfieldstringindex}}', ['index_key(12)', 'index_value(64)'], false);


    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%user_configuredcustomfieldstorage}}');
        $this->dropTable('{{%user_customfieldnumericindex}}');
        $this->dropTable('{{%user_customfieldstringindex}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191103_022845_CreateUserCustomField cannot be reverted.\n";

        return false;
    }
    */
}

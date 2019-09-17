<?php

use yii\db\Migration;

/**
 * Class m190716_040717_CreateAuthMobileCaptcha
 */
class m190716_040717_CreateAuthMobileCaptcha extends Migration
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
        $this->createTable('{{%auth_mobile_captcha}}', [
            'id' => $this->primaryKey(),
            'mobile' => $this->string(16)->notNull(),
            'captcha' => $this->string(8)->notNull(),
            'is_expired' => $this->tinyInteger(1)->defaultValue(0)->notNull(),
            'expired_at' => $this->integer(11)->notNull(),
            'ip' => $this->string(64)->null(),
            'created_at' => $this->integer(11)->null(),
            'updated_at' => $this->integer(11)->null(),
        ], $tableOptions);

        $this->createIndex('idx-mobile', '{{%auth_mobile_captcha}}', ['mobile', 'is_expired', 'expired_at'], false);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%auth_mobile_captcha}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190716_040717_CreateAuthMobileCaptcha cannot be reverted.\n";

        return false;
    }
    */
}

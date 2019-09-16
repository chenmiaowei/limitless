<?php

namespace orangins\modules\finance\models;

use Yii;

/**
 * This is the model class for table "finance_balance_record".
 *
 * @property int $id
 * @property string $user_phid
 * @property string $type
 * @property int $data_id
 * @property double $amount
 * @property int $created_at
 * @property int $updated_at
 */
class FinanceBalanceRecord extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'finance_balance_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_phid', 'type'], 'required'],
            [['data_id', 'created_at', 'updated_at'], 'integer'],
            [['amount'], 'number'],
            [['user_phid', 'type'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_phid' => Yii::t('app', 'User Phid'),
            'type' => Yii::t('app', 'Type'),
            'data_id' => Yii::t('app', 'Data ID'),
            'amount' => Yii::t('app', 'Amount'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}

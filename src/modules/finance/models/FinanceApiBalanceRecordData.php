<?php

namespace orangins\modules\finance\models;

use Yii;

/**
 * This is the model class for table "finance_api_balance_record_data".
 *
 * @property int $id
 * @property string $data
 * @property int $created_at
 * @property int $updated_at
 */
class FinanceApiBalanceRecordData extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'finance_api_balance_record_data';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['data'], 'string'],
            [['created_at', 'updated_at'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'data' => Yii::t('app', 'Data'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}

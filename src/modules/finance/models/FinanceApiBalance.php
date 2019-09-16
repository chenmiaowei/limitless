<?php

namespace orangins\modules\finance\models;

use orangins\modules\people\models\PhabricatorUser;
use Yii;

/**
 * This is the model class for table "finance_api_balance".
 *
 * @property int $id
 * @property string $user_phid
 * @property string $api_key
 * @property double $amount
 * @property int $created_at
 * @property int $updated_at
 */
class FinanceApiBalance extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'finance_api_balance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_phid', 'api_key'], 'required'],
            [['amount'], 'number'],
            [['created_at', 'updated_at'], 'integer'],
            [['user_phid', 'api_key'], 'string', 'max' => 64],
            [['user_phid', 'api_key'], 'unique', 'targetAttribute' => ['user_phid', 'api_key']],
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
            'api_key' => Yii::t('app', 'Api Key'),
            'amount' => Yii::t('app', 'Amount'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }


    /**
     * @param PhabricatorUser $user
     * @param $apiKey
     * @author 陈妙威
     */
    public static function getObject(PhabricatorUser $user, $apiKey)
    {
        $financeApiBalance = self::find()->andWhere(['user_phid' => $user->getPHID()])->one();
        if(!$financeApiBalance) {
            $financeApiBalance = new self();
            $financeApiBalance->user_phid = $user->getPHID();
        }
    }
}

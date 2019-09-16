<?php

namespace orangins\modules\finance\models;

use orangins\modules\people\models\PhabricatorUser;
use Yii;

/**
 * This is the model class for table "finance_balance".
 *
 * @property int $id
 * @property string $user_phid
 * @property double $amount
 * @property int $created_at
 * @property int $updated_at
 */
class FinanceBalance extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'finance_balance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['amount'], 'number'],
            [['created_at', 'updated_at'], 'integer'],
            [['user_phid'], 'string', 'max' => 64],
            [['user_phid'], 'unique'],
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
            'amount' => Yii::t('app', 'Amount'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param PhabricatorUser $user
     * @author 陈妙威
     * @return array|null|FinanceBalance|\yii\db\ActiveRecord
     */
    public static function getObject(PhabricatorUser $user)
    {
        $financeApiBalance = self::find()->andWhere(['user_phid' => $user->getPHID()])->one();
        if(!$financeApiBalance) {
            $financeApiBalance = new self();
            $financeApiBalance->user_phid = $user->getPHID();
            $financeApiBalance->amount = 0;
            $financeApiBalance->save();
        }
        return $financeApiBalance;
    }
}

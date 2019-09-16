<?php

namespace orangins\modules\conpherence\models;

use Yii;

/**
 * This is the model class for table "conpherence_index".
 *
 * @property int $id
 * @property string $thread_phid
 * @property string $transaction_phid
 * @property string $previous_transaction_phid
 * @property string $corpus
 */
class ConpherenceIndex extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conpherence_index';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['thread_phid', 'transaction_phid', 'corpus'], 'required'],
            [['corpus'], 'string'],
            [['thread_phid', 'transaction_phid', 'previous_transaction_phid'], 'string', 'max' => 64],
            [['transaction_phid'], 'unique'],
            [['previous_transaction_phid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'thread_phid' => Yii::t('app', 'Thread Phid'),
            'transaction_phid' => Yii::t('app', 'Transaction Phid'),
            'previous_transaction_phid' => Yii::t('app', 'Previous Transaction Phid'),
            'corpus' => Yii::t('app', 'Corpus'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return ConpherenceEdgeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ConpherenceEdgeQuery(get_called_class());
    }
}

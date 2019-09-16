<?php

namespace orangins\modules\system\models;

use orangins\lib\infrastructure\util\PhabricatorHash;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "system_actionlog".
 *
 * @property int $id
 * @property string $actor_hash
 * @property string $actor_identity
 * @property string $action
 * @property double $score
 * @property int $epoch
 * @property int $created_at
 * @property int $updated_at
 */
class PhabricatorSystemActionLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'system_actionlog';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['actor_hash', 'actor_identity', 'action', 'score', 'epoch'], 'required'],
            [['score'], 'number'],
            [['epoch', 'created_at', 'updated_at'], 'integer'],
            [['actor_hash'], 'string', 'max' => 16],
            [['actor_identity'], 'string', 'max' => 255],
            [['action'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'actor_hash' => Yii::t('app', 'Actor Hash'),
            'actor_identity' => Yii::t('app', 'Actor Identity'),
            'action' => Yii::t('app', 'Action'),
            'score' => Yii::t('app', 'Score'),
            'epoch' => Yii::t('app', 'Epoch'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param $identity
     * @return mixed
     * @author 陈妙威
     */
    public function setActorIdentity($identity) {
        $this->setActorHash(PhabricatorHash::digestForIndex($identity));
        return parent::setActorIdentity($identity);
    }

    /**
     * @return string
     */
    public function getActorHash()
    {
        return $this->actor_hash;
    }

    /**
     * @param string $actor_hash
     * @return self
     */
    public function setActorHash($actor_hash)
    {
        $this->actor_hash = $actor_hash;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return self
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param float $score
     * @return self
     */
    public function setScore($score)
    {
        $this->score = $score;
        return $this;
    }

    /**
     * @return int
     */
    public function getEpoch()
    {
        return $this->epoch;
    }

    /**
     * @param int $epoch
     * @return self
     */
    public function setEpoch($epoch)
    {
        $this->epoch = $epoch;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function attributes()
    {
        return ArrayHelper::merge(parent::attributes(), ['total_score']);
    }
}

<?php

namespace orangins\modules\herald\models;

use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\herald\models\transcript\HeraldApplyTranscript;
use orangins\modules\herald\models\transcript\HeraldConditionTranscript;
use orangins\modules\herald\models\transcript\HeraldObjectTranscript;
use orangins\modules\herald\models\transcript\HeraldRuleTranscript;
use orangins\modules\herald\phid\HeraldTranscriptPHIDType;
use orangins\modules\herald\query\HeraldTranscriptQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\db\Expression;

/**
 * This is the model class for table "herald_transcript".
 *
 * @property int $id
 * @property string $phid
 * @property int $time
 * @property string $host
 * @property double $duration
 * @property string $object_phid
 * @property int $dry_run
 * @property string $object_transcript
 * @property string $rule_transcripts
 * @property string $condition_transcripts
 * @property string $apply_transcripts
 * @property int $garbage_collected
 */
class HeraldTranscript extends ActiveRecordPHID
    implements PhabricatorPolicyInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'herald_transcript';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function behaviors()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['time', 'host', 'duration', 'object_phid', 'dry_run', 'object_transcript', 'rule_transcripts', 'condition_transcripts', 'apply_transcripts'], 'required'],
            [['time', 'dry_run', 'garbage_collected'], 'integer'],
            [['duration'], 'number'],
            [['object_transcript', 'rule_transcripts', 'condition_transcripts', 'apply_transcripts'], 'string'],
            [['phid', 'object_phid'], 'string', 'max' => 64],
            [['host'], 'string', 'max' => 255],
            [['phid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'phid' => Yii::t('app', 'Phid'),
            'time' => Yii::t('app', 'Time'),
            'host' => Yii::t('app', 'Host'),
            'duration' => Yii::t('app', 'Duration'),
            'object_phid' => Yii::t('app', 'Object Phid'),
            'dry_run' => Yii::t('app', 'Dry Run'),
            'object_transcript' => Yii::t('app', 'Object Transcript'),
            'rule_transcripts' => Yii::t('app', 'Rule Transcripts'),
            'condition_transcripts' => Yii::t('app', 'Condition Transcripts'),
            'apply_transcripts' => Yii::t('app', 'Apply Transcripts'),
            'garbage_collected' => Yii::t('app', 'Garbage Collected'),
        ];
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param int $time
     * @return self
     */
    public function setTime($time)
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return self
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return float
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param float $duration
     * @return self
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
        return $this;
    }

    /**
     * @return string
     */
    public function getObjectPHID()
    {
        return $this->object_phid;
    }

    /**
     * @param string $object_phid
     * @return self
     */
    public function setObjectPHID($object_phid)
    {
        $this->object_phid = $object_phid;
        return $this;
    }

    /**
     * @return int
     */
    public function getDryRun()
    {
        return $this->dry_run;
    }

    /**
     * @param int $dry_run
     * @return self
     */
    public function setDryRun($dry_run)
    {
        $this->dry_run = $dry_run;
        return $this;
    }

    /**
     * @return HeraldObjectTranscript
     */
    public function getObjectTranscript()
    {
        return $this->object_transcript === null ? null : unserialize($this->object_transcript);
    }

    /**
     * @param HeraldObjectTranscript $object_transcript
     * @return self
     */
    public function setObjectTranscript($object_transcript)
    {
        $this->object_transcript = $object_transcript === null ? null : serialize($object_transcript);
        return $this;
    }

    /**
     * @return HeraldObjectTranscript[]
     */
    public function getRuleTranscripts()
    {
        return $this->rule_transcripts === null ? [] : unserialize($this->rule_transcripts);
    }

    /**
     * @param array $rule_transcripts
     * @return self
     */
    public function setRuleTranscripts($rule_transcripts)
    {
        $this->rule_transcripts = $rule_transcripts === null ? [] : serialize($rule_transcripts);
        return $this;
    }

    /**
     * @return array
     */
    public function getConditionTranscripts()
    {
        return $this->condition_transcripts === null ? [] : unserialize($this->condition_transcripts);
    }

    /**
     * @param array $condition_transcripts
     * @return self
     */
    public function setConditionTranscripts($condition_transcripts)
    {
        $this->condition_transcripts = $condition_transcripts === null ? [] : serialize($condition_transcripts);
        return $this;
    }

    /**
     * @return array
     */
    public function getApplyTranscripts()
    {
        return $this->apply_transcripts === null ? [] : unserialize($this->apply_transcripts);
    }

    /**
     * @param array $apply_transcripts
     * @return self
     */
    public function setApplyTranscripts($apply_transcripts)
    {
        $this->apply_transcripts = $apply_transcripts === null ? [] : serialize($apply_transcripts);
        return $this;
    }

    /**
     * @return int
     */
    public function getGarbageCollected()
    {
        return $this->garbage_collected;
    }

    /**
     * @param int $garbage_collected
     * @return self
     */
    public function setGarbageCollected($garbage_collected)
    {
        $this->garbage_collected = $garbage_collected;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getXHeraldRulesHeader()
    {
        $ids = array();
        foreach ($this->getApplyTranscripts() as $xscript) {
            if ($xscript->getApplied()) {
                if ($xscript->getRuleID()) {
                    $ids[] = $xscript->getRuleID();
                }
            }
        }
        if (!$ids) {
            return 'none';
        }

        // A rule may have multiple effects, which will cause it to be listed
        // multiple times.
        $ids = array_unique($ids);

        foreach ($ids as $k => $id) {
            $ids[$k] = '<' . $id . '>';
        }

        return implode(', ', $ids);
    }

    /**
     * @param $phid
     * @param $header
     * @return string
     * @throws InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    public static function saveXHeraldRulesHeader($phid, $header)
    {

        // Combine any existing header with the new header, listing all rules
        // which have ever triggered for this object.
        $header = self::combineXHeraldRulesHeaders(
            self::loadXHeraldRulesHeader($phid),
            $header);

        $arr = [
            'phid' => $phid,
            'header' => $header,
        ];
        Yii::$app->getDb()->createCommand()->upsert(HeraldSavedheader::tableName(), $arr, [
            'header' => new Expression('VALUES(header)'),
        ])->execute();

        return $header;
    }

    /**
     * @param $u
     * @param $v
     * @return string
     * @author 陈妙威
     */
    private static function combineXHeraldRulesHeaders($u, $v)
    {
        $u = preg_split('/[, ]+/', $u);
        $v = preg_split('/[, ]+/', $v);

        $combined = array_unique(array_filter(array_merge($u, $v)));
        return implode(', ', $combined);
    }

    /**
     * @param $phid
     * @return object|null
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    public static function loadXHeraldRulesHeader($phid)
    {
        $header = HeraldSavedheader::find()->andWhere(['phid' => $phid])->one();
        if ($header) {
            return idx($header, 'header');
        }
        return null;
    }

    /**
     * @param HeraldApplyTranscript $transcript
     * @return $this
     * @author 陈妙威
     */
    public function addApplyTranscript(HeraldApplyTranscript $transcript)
    {
        $applyTranscripts = $this->getApplyTranscripts();
        $applyTranscripts[] = $transcript;
        $this->setApplyTranscripts($applyTranscripts);
        return $this;
    }


    /**
     * @param HeraldRuleTranscript $transcript
     * @return $this
     * @author 陈妙威
     */
    public function addRuleTranscript(HeraldRuleTranscript $transcript)
    {
        $heraldObjectTranscript = $this->getRuleTranscripts();
        $heraldObjectTranscript[$transcript->getRuleID()] = $transcript;
        $this->setRuleTranscripts($heraldObjectTranscript);
        return $this;
    }

    /**
     * @author 陈妙威
     */
    public function discardDetails()
    {
        $this->setApplyTranscripts(null);
        $this->setRuleTranscripts(null);
        $this->setObjectTranscript(null);
        $this->setConditionTranscripts(null);
    }

    /**
     * @param HeraldConditionTranscript $transcript
     * @return $this
     * @author 陈妙威
     */
    public function addConditionTranscript(
        HeraldConditionTranscript $transcript)
    {
        $rule_id = $transcript->getRuleID();
        $cond_id = $transcript->getConditionID();


        $conditionTranscripts = $this->getConditionTranscripts();
        $conditionTranscripts[$rule_id][$cond_id] = $transcript;
        $this->setConditionTranscripts($conditionTranscripts);
        return $this;
    }

    /**
     * @param $rule_id
     * @return object
     * @author 陈妙威
     */
    public function getConditionTranscriptsForRule($rule_id)
    {
        return idx($this->getConditionTranscripts(), $rule_id, array());
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getMetadataMap()
    {
        return array(
            pht('Run At Epoch') => date('F jS, g:i:s A', $this->time),
            pht('Run On Host') => $this->host,
            pht('Run Duration') => (int)(1000 * $this->duration) . ' ms',
        );
    }


    /**
     * {@inheritdoc}
     * @return HeraldTranscriptQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new HeraldTranscriptQuery(get_called_class());
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return HeraldTranscriptPHIDType::class;
    }

    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */
    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
        );
    }

    /**
     * @param $capability
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        return PhabricatorPolicies::POLICY_PUBLIC;
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return true;
    }

}

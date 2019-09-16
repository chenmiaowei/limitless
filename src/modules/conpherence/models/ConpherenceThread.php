<?php

namespace orangins\modules\conpherence\models;

use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\config\phid\PhabricatorConfigPHIDType;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\models\PhabricatorPolicy;
use orangins\modules\policy\models\PhabricatorPolicyQuery;
use Yii;

/**
 * This is the model class for table "conpherence_thread".
 *
 * @property int $id
 * @property string $phid
 * @property string $title
 * @property int $message_count
 * @property string $view_policy
 * @property string $edit_policy
 * @property string $join_policy
 * @property string $mail_key
 * @property string $topic
 * @property string $profileImage_phid
 * @property int $created_at
 * @property int $updated_at
 */
class ConpherenceThread extends ActiveRecordPHID
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conpherence_thread';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['message_count', 'view_policy', 'edit_policy', 'join_policy', 'mail_key', 'topic'], 'required'],
            [['message_count', 'created_at', 'updated_at'], 'integer'],
            [['phid', 'view_policy', 'edit_policy', 'join_policy', 'profileImage_phid'], 'string', 'max' => 64],
            [['title', 'topic'], 'string', 'max' => 255],
            [['mail_key'], 'string', 'max' => 20],
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
            'phid' => Yii::t('app', 'PHID'),
            'title' => Yii::t('app', 'Title'),
            'message_count' => Yii::t('app', 'Message Count'),
            'view_policy' => Yii::t('app', 'View Policy'),
            'edit_policy' => Yii::t('app', 'Edit Policy'),
            'join_policy' => Yii::t('app', 'Join Policy'),
            'mail_key' => Yii::t('app', 'Mail Key'),
            'topic' => Yii::t('app', 'Topic'),
            'profileImage_phid' => Yii::t('app', 'Profile Image PHID'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return ConpherenceThreadQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ConpherenceThreadQuery(get_called_class());
    }

    /**
     * @param PhabricatorUser $viewer
     * @param array $conpherences
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public static function loadViewPolicyObjects(
        PhabricatorUser $viewer,
        array $conpherences)
    {

        assert_instances_of($conpherences, __CLASS__);

        $policies = array();
        foreach ($conpherences as $room) {
            $policies[$room->getViewPolicy()] = 1;
        }
        $policy_objects = array();
        if ($policies) {
            $policy_objects = PhabricatorPolicy::find()
                ->setViewer($viewer)
                ->withPHIDs(array_keys($policies))
                ->execute();
        }

        return $policy_objects;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return int
     */
    public function getMessageCount()
    {
        return $this->message_count;
    }

    /**
     * @param int $message_count
     * @return self
     */
    public function setMessageCount($message_count)
    {
        $this->message_count = $message_count;
        return $this;
    }

    /**
     * @return string
     */
    public function getMailKey()
    {
        return $this->mail_key;
    }

    /**
     * @param string $mail_key
     * @return self
     */
    public function setMailKey($mail_key)
    {
        $this->mail_key = $mail_key;
        return $this;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @param string $topic
     * @return self
     */
    public function setTopic($topic)
    {
        $this->topic = $topic;
        return $this;
    }

    /**
     * @return string
     */
    public function getProfileImagePHID()
    {
        return $this->profileImage_phid;
    }

    /**
     * @param string $profileImage_phid
     * @return self
     */
    public function setProfileImagePHID($profileImage_phid)
    {
        $this->profileImage_phid = $profileImage_phid;
        return $this;
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorConfigPHIDType::className();
    }
}

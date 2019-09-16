<?php

namespace orangins\modules\auth\models;

use orangins\lib\db\ActiveRecord;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\auth\query\PhabricatorAuthSessionQuery;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Yii;
use Exception;

/**
 * This is the model class for table "session".
 *
 * @property int $id
 * @property string $user_phid
 * @property string $type
 * @property string $session_key
 * @property int $session_start
 * @property int $session_expires
 * @property int $high_security_until
 * @property int $is_partial
 * @property int $signed_legalpad_documents
 * @property int $created_at
 * @property int $updated_at
 */
class PhabricatorAuthSession extends ActiveRecord
    implements PhabricatorPolicyInterface
{
    /**
     *
     */
    const TYPE_WEB      = 'web';
    /**
     *
     */
    const TYPE_CONDUIT  = 'conduit';

    /**
     * @var string
     */
    private $identityObject = self::ATTACHABLE;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'session';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_phid', 'type', 'session_key', 'session_start', 'session_expires'], 'required'],
            [['session_start', 'session_expires', 'high_security_until', 'is_partial', 'signed_legalpad_documents', 'created_at', 'updated_at'], 'integer'],
            [['user_phid'], 'string', 'max' => 64],
            [['type'], 'string', 'max' => 32],
            [['session_key'], 'string', 'max' => 40],
            [['session_key'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_phid' => Yii::t('app', 'User PHID'),
            'type' => Yii::t('app', 'Type'),
            'session_key' => Yii::t('app', 'Session Key'),
            'session_start' => Yii::t('app', 'Session Start'),
            'session_expires' => Yii::t('app', 'Session Expires'),
            'high_security_until' => Yii::t('app', 'High Security Until'),
            'is_partial' => Yii::t('app', 'Is Partial'),
            'signed_legalpad_documents' => Yii::t('app', 'Signed Legalpad Documents'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param $identity_object
     * @return $this
     * @author 陈妙威
     */
    public function attachIdentityObject($identity_object)
    {
        $this->identityObject = $identity_object;
        return $this;
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getIdentityObject()
    {
        return $this->assertAttached($this->identityObject);
    }

    /**
     * @param $session_type
     * @return int
     * @throws Exception
     * @author 陈妙威
     */
    public static function getSessionTypeTTL($session_type)
    {
        switch ($session_type) {
            case self::TYPE_WEB:
                return phutil_units('30 days in seconds');
            case self::TYPE_CONDUIT:
                return phutil_units('24 hours in seconds');
            default:
                throw new Exception(\Yii::t("app",'Unknown session type "%s".', $session_type));
        }
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isHighSecuritySession()
    {
        $until = $this->getHighSecurityUntil();

        if (!$until) {
            return false;
        }

        $now = PhabricatorTime::getNow();
        if ($until < $now) {
            return false;
        }

        return true;
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
        );
    }

    /**
     * @param $capability
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        return PhabricatorPolicies::POLICY_NOONE;
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        if (!$viewer->getPHID()) {
            return false;
        }

        $object = $this->getIdentityObject();
        if ($object instanceof PhabricatorUser) {
            return ($object->getPHID() == $viewer->getPHID());
        } else if ($object instanceof PhabricatorExternalAccount) {
            return ($object->getUserPHID() == $viewer->getPHID());
        }

        return false;
    }

    /**
     * @param $capability
     * @return string
     * @author 陈妙威
     */
    public function describeAutomaticCapability($capability)
    {
        return \Yii::t("app",'A session is visible only to its owner.');
    }


    /**
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|PhabricatorAuthSessionQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorAuthSessionQuery(get_called_class());
    }
    /**
     * @return string
     */
    public function getUserPHID()
    {
        return $this->user_phid;
    }

    /**
     * @param string $user_phid
     * @return self
     */
    public function setUserPHID($user_phid)
    {
        $this->user_phid = $user_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionKey()
    {
        return $this->session_key;
    }

    /**
     * @param string $session_key
     * @return self
     */
    public function setSessionKey($session_key)
    {
        $this->session_key = $session_key;
        return $this;
    }

    /**
     * @return int
     */
    public function getSessionStart()
    {
        return $this->session_start;
    }

    /**
     * @param int $session_start
     * @return self
     */
    public function setSessionStart($session_start)
    {
        $this->session_start = $session_start;
        return $this;
    }

    /**
     * @return int
     */
    public function getSessionExpires()
    {
        return $this->session_expires;
    }

    /**
     * @param int $session_expires
     * @return self
     */
    public function setSessionExpires($session_expires)
    {
        $this->session_expires = $session_expires;
        return $this;
    }

    /**
     * @return int
     */
    public function getHighSecurityUntil()
    {
        return $this->high_security_until;
    }

    /**
     * @param int $high_security_until
     * @return self
     */
    public function setHighSecurityUntil($high_security_until)
    {
        $this->high_security_until = $high_security_until;
        return $this;
    }

    /**
     * @return int
     */
    public function getisPartial()
    {
        return $this->is_partial;
    }

    /**
     * @param int $is_partial
     * @return self
     */
    public function setIsPartial($is_partial)
    {
        $this->is_partial = $is_partial;
        return $this;
    }

    /**
     * @return int
     */
    public function getSignedLegalpadDocuments()
    {
        return $this->signed_legalpad_documents;
    }

    /**
     * @param int $signed_legalpad_documents
     * @return self
     */
    public function setSignedLegalpadDocuments($signed_legalpad_documents)
    {
        $this->signed_legalpad_documents = $signed_legalpad_documents;
        return $this;
    }
}

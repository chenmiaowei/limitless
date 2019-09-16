<?php

namespace orangins\modules\auth\models;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\request\AphrontRequest;
use orangins\modules\auth\editor\PhabricatorAuthPasswordEditor;
use orangins\modules\auth\password\PhabricatorAuthPasswordHashInterface;
use orangins\modules\auth\phid\PhabricatorAuthPasswordPHIDType;
use orangins\modules\auth\query\PhabricatorAuthPasswordQuery;
use orangins\lib\infrastructure\util\password\PhabricatorPasswordHasher;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\system\interfaces\PhabricatorDestructibleInterface;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use PhutilOpaqueEnvelope;
use orangins\modules\file\helpers\FileSystemHelper;
use Yii;
use Exception;

/**
 * This is the model class for table "auth_password".
 *
 * @property int $id
 * @property string $phid
 * @property string $object_phid
 * @property string $password_type
 * @property string $password_hash
 * @property int $is_revoked
 * @property string $password_salt
 * @property string $legacy_digest_format
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorAuthPassword extends ActiveRecordPHID
    implements PhabricatorPolicyInterface,
    PhabricatorDestructibleInterface,
    PhabricatorApplicationTransactionInterface
{

    /**
     *
     */
    const PASSWORD_TYPE_ACCOUNT = 'account';
    /**
     *
     */
    const PASSWORD_TYPE_VCS = 'vcs';
    /**
     *
     */
    const PASSWORD_TYPE_TEST = 'test';

    /**
     * @var string
     */
    private $object = self::ATTACHABLE;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_password';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['object_phid', 'password_type', 'password_hash', 'is_revoked', 'password_salt'], 'required'],
            [['is_revoked'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'object_phid', 'password_type', 'password_salt'], 'string', 'max' => 64],
            [['password_hash'], 'string', 'max' => 128],
            [['legacy_digest_format'], 'string', 'max' => 32],
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
            'object_phid' => Yii::t('app', 'Object PHID'),
            'password_type' => Yii::t('app', 'Password Type'),
            'password_hash' => Yii::t('app', 'Password Hash'),
            'is_revoked' => Yii::t('app', 'Is Revoked'),
            'password_salt' => Yii::t('app', 'Password Salt'),
            'legacy_digest_format' => Yii::t('app', 'Legacy Digest Format'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getObject()
    {
        return $this->assertAttached($this->object);
    }

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function attachObject($object)
    {
        $this->object = $object;
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
     * @return string
     */
    public function getPasswordType()
    {
        return $this->password_type;
    }

    /**
     * @param string $password_type
     * @return self
     */
    public function setPasswordType($password_type)
    {
        $this->password_type = $password_type;
        return $this;
    }

    /**
     * @return string
     */
    public function getPasswordHash()
    {
        return $this->password_hash;
    }

    /**
     * @param string $password_hash
     * @return self
     */
    public function setPasswordHash($password_hash)
    {
        $this->password_hash = $password_hash;
        return $this;
    }

    /**
     * @return int
     */
    public function getisRevoked()
    {
        return $this->is_revoked;
    }

    /**
     * @param int $is_revoked
     * @return self
     */
    public function setIsRevoked($is_revoked)
    {
        $this->is_revoked = $is_revoked;
        return $this;
    }

    /**
     * @return string
     */
    public function getPasswordSalt()
    {
        return $this->password_salt;
    }

    /**
     * @param string $password_salt
     * @return self
     */
    public function setPasswordSalt($password_salt)
    {
        $this->password_salt = $password_salt;
        return $this;
    }

    /**
     * @return string
     */
    public function getLegacyDigestFormat()
    {
        return $this->legacy_digest_format;
    }

    /**
     * @param string $legacy_digest_format
     * @return self
     */
    public function setLegacyDigestFormat($legacy_digest_format)
    {
        $this->legacy_digest_format = $legacy_digest_format;
        return $this;
    }

    /**
     * @param PhutilOpaqueEnvelope $password
     * @param PhabricatorAuthPasswordHashInterface $object
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function setPassword(
        PhutilOpaqueEnvelope $password,
        PhabricatorAuthPasswordHashInterface $object)
    {

        $hasher = PhabricatorPasswordHasher::getBestHasher();
        return $this->setPasswordWithHasher($password, $object, $hasher);
    }


    /**
     * @param PhutilOpaqueEnvelope $password
     * @param PhabricatorAuthPasswordHashInterface $object
     * @param PhabricatorPasswordHasher $hasher
     * @return PhabricatorAuthPassword
     * @throws Exception
     * @author 陈妙威
     */
    public function setPasswordWithHasher(
        PhutilOpaqueEnvelope $password,
        PhabricatorAuthPasswordHashInterface $object,
        PhabricatorPasswordHasher $hasher)
    {

        if (!strlen($password->openEnvelope())) {
            throw new Exception(
                \Yii::t("app", 'Attempting to set an empty password!'));
        }

        // Generate (or regenerate) the salt first.
        $new_salt = FileSystemHelper::readRandomCharacters(64);
        $this->setPasswordSalt($new_salt);

        // Clear any legacy digest format to force a modern digest.
        $this->setLegacyDigestFormat(null);

        $digest = $this->digestPassword($password, $object);
        $hash = $hasher->getPasswordHashForStorage($digest);
        $raw_hash = $hash->openEnvelope();

        return $this->setPasswordHash($raw_hash);
    }

    /**
     * @param PhutilOpaqueEnvelope $password
     * @param PhabricatorAuthPasswordHashInterface|ActiveRecordPHID $object
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    private function digestPassword(
        PhutilOpaqueEnvelope $password,
        PhabricatorAuthPasswordHashInterface $object)
    {
        $object_phid = $object->getPHID();
        if ($this->getObjectPHID() !== $object->getPHID()) {
            throw new Exception(
                \Yii::t("app",
                    'This password is associated with an object PHID ("%s") for ' .
                    'a different object than the provided one ("%s").',
                    $this->getObjectPHID(),
                    $object->getPHID()));
        }

        $digest = $object->newPasswordDigest($password, $this);

        if (!($digest instanceof PhutilOpaqueEnvelope)) {
            throw new Exception(
                \Yii::t("app",
                    'Failed to digest password: object ("%s") did not return an ' .
                    'opaque envelope with a password digest.',
                    $object->getPHID()));
        }

        return $digest;
    }


    /**
     * @param PhabricatorAuthPasswordHashInterface|ActiveRecordPHID $object
     * @param $type
     * @return PhabricatorAuthPassword
     * @author 陈妙威
     */
    public static function initializeNewPassword(
        PhabricatorAuthPasswordHashInterface $object,
        $type)
    {

        return (new self())
            ->setObjectPHID($object->getPHID())
            ->attachObject($object)
            ->setPasswordType($type)
            ->setIsRevoked(0);
    }

    /**
     * @return PhabricatorAuthPasswordQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorAuthPasswordQuery(get_called_class());
    }

    /**
     * @return PhutilOpaqueEnvelope
     * @author 陈妙威
     */
    public function newPasswordEnvelope()
    {
        return new PhutilOpaqueEnvelope($this->getPasswordHash());
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorAuthPasswordPHIDType::class;
    }


    /**
     * @return bool
     * @throws Exception
     * @throws \orangins\lib\infrastructure\util\password\PhabricatorPasswordHasherUnavailableException
     * @author 陈妙威
     */
    public function canUpgrade() {
        // If this password uses a legacy digest format, we can upgrade it to the
        // new digest format even if a better hasher isn't available.
        if ($this->getLegacyDigestFormat() !== null) {
            return true;
        }

        $hash = $this->newPasswordEnvelope();
        return PhabricatorPasswordHasher::canUpgradeHash($hash);
    }

    /**
     * @param PhutilOpaqueEnvelope $password
     * @param PhabricatorAuthPasswordHashInterface $object
     * @return bool
     * @throws Exception
     * @throws \orangins\lib\infrastructure\util\password\PhabricatorPasswordHasherUnavailableException
     * @author 陈妙威
     */
    public function comparePassword(
        PhutilOpaqueEnvelope $password,
        PhabricatorAuthPasswordHashInterface $object) {

        $digest = $this->digestPassword($password, $object);
        $hash = $this->newPasswordEnvelope();

        return PhabricatorPasswordHasher::comparePassword($digest, $hash);
    }

    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    /**
     * @return array
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
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        return PhabricatorPolicies::getMostOpenPolicy();
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return false;
    }


    /* -(  PhabricatorExtendedPolicyInterface  )--------------------------------- */


    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return array
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getExtendedPolicy($capability, PhabricatorUser $viewer)
    {
        return array(
            array($this->getObject(), $capability),
        );
    }


    /* -(  PhabricatorDestructibleInterface  )----------------------------------- */


    /**
     * @param PhabricatorDestructionEngine $engine
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function destroyObjectPermanently(
        PhabricatorDestructionEngine $engine)
    {
        $this->delete();
    }


    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorAuthPasswordEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorAuthPasswordEditor();
    }

    /**
     * @return $this
     * @author 陈妙威
     */
    public function getApplicationTransactionObject()
    {
        return $this;
    }

    /**
     * @return PhabricatorAuthPasswordTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorAuthPasswordTransaction();
    }

    /**
     * @param PhabricatorApplicationTransactionView $timeline
     * @param AphrontRequest $request
     * @return PhabricatorApplicationTransactionView
     * @author 陈妙威
     */
    public function willRenderTimeline(
        PhabricatorApplicationTransactionView $timeline,
        AphrontRequest $request)
    {

        return $timeline;
    }
}

<?php

namespace orangins\modules\conduit\models;

use AphrontWriteGuard;
use Filesystem;
use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\time\PhabricatorTime;
use orangins\modules\conduit\phid\ConduitTokenPHIDType;
use orangins\modules\conduit\query\PhabricatorConduitTokenQuery;
use orangins\modules\config\editor\PhabricatorConfigEditor;
use orangins\modules\config\models\PhabricatorConfigTransaction;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use Yii;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "conduit_token".
 *
 * @property int $id
 * @property string $object_phid
 * @property string $token_type
 * @property string $token
 * @property int $expires
 * @property int $created_at
 * @property int $updated_at
 * @property string $parameters
 */
class PhabricatorConduitToken extends ActiveRecordPHID
    implements PhabricatorPolicyInterface,
    PhabricatorApplicationTransactionInterface
{

    private $object = self::ATTACHABLE;

    const TYPE_STANDARD = 'api';
    const TYPE_COMMANDLINE = 'cli';
    const TYPE_CLUSTER = 'clr';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'conduit_token';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['token_type', 'token'], 'required'],
            [['expires', 'created_at', 'updated_at'], 'integer'],
            [['object_phid', 'phid'], 'string', 'max' => 64],
            [['token_type', 'token'], 'string', 'max' => 32],
            [['parameters'], 'string'],
            [['token'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'object_phid' => Yii::t('app', 'Object PHID'),
            'token_type' => Yii::t('app', 'Token Type'),
            'token' => Yii::t('app', 'Token'),
            'expires' => Yii::t('app', 'Expires'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorConduitTokenQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PhabricatorConduitTokenQuery(get_called_class());
    }

    /**
     * @param PhabricatorUser $user
     * @return null|PhabricatorConduitToken
     * @throws Exception
     * @throws \AphrontQueryException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public static function loadClusterTokenForUser(PhabricatorUser $user)
    {
        if (!$user->isLoggedIn()) {
            return null;
        }

        if ($user->hasConduitClusterToken()) {
            return $user->getConduitClusterToken();
        }

        $tokens = PhabricatorConduitToken::find()
            ->setViewer($user)
            ->withObjectPHIDs(array($user->getPHID()))
            ->withTokenTypes(array(self::TYPE_CLUSTER))
            ->withExpired(false)
            ->execute();

        // Only return a token if it has at least 5 minutes left before
        // expiration. Cluster tokens cycle regularly, so we don't want to use
        // one that's going to expire momentarily.
        $now = PhabricatorTime::getNow();
        $must_expire_after = $now + phutil_units('5 minutes in seconds');

        $valid_token = null;
        foreach ($tokens as $token) {
            if ($token->getExpires() > $must_expire_after) {
                $valid_token = $token;
                break;
            }
        }

        // We didn't find any existing tokens (or the existing tokens are all about
        // to expire) so generate a new token.
        if (!$valid_token) {
            $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
            $valid_token = self::initializeNewToken(
                $user->getPHID(),
                self::TYPE_CLUSTER);
            $valid_token->save();
            unset($unguarded);
        }

        $user->attachConduitClusterToken($valid_token);

        return $valid_token;
    }

    /**
     * @param $object_phid
     * @param $token_type
     * @return PhabricatorConduitToken
     * @author 陈妙威
     * @throws Exception
     */
    public static function initializeNewToken($object_phid, $token_type)
    {
        $token = new PhabricatorConduitToken();
        $token->object_phid = $object_phid;
        $token->token_type = $token_type;
        $token->expires = $token->getTokenExpires($token_type);

        $secret = $token_type . '-' . Filesystem::readRandomCharacters(32);
        $secret = substr($secret, 0, 32);
        $token->token = $secret;

        return $token;
    }

    /**
     * @param $type
     * @return object
     * @author 陈妙威
     */
    public static function getTokenTypeName($type)
    {
        $map = array(
            self::TYPE_STANDARD => \Yii::t("app", 'Standard API Token'),
            self::TYPE_COMMANDLINE => \Yii::t("app", 'Command Line API Token'),
            self::TYPE_CLUSTER => \Yii::t("app", 'Cluster API Token'),
        );

        return ArrayHelper::getValue($map, $type, $type);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getAllTokenTypes()
    {
        return array(
            self::TYPE_STANDARD,
            self::TYPE_COMMANDLINE,
            self::TYPE_CLUSTER,
        );
    }

    /**
     * @param $token_type
     * @return int|null
     * @author 陈妙威
     * @throws Exception
     */
    private function getTokenExpires($token_type)
    {
        $now = PhabricatorTime::getNow();
        switch ($token_type) {
            case self::TYPE_STANDARD:
                return null;
            case self::TYPE_COMMANDLINE:
                return $now + phutil_units('1 hour in seconds');
            case self::TYPE_CLUSTER:
                return $now + phutil_units('30 minutes in seconds');
            default:
                throw new Exception(
                    \Yii::t("app", 'Unknown Conduit token type "%s"!', $token_type));
        }
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPublicTokenName()
    {
        switch ($this->getTokenType()) {
            case self::TYPE_CLUSTER:
                return \Yii::t("app", 'Cluster API Token');
            default:
                return substr($this->getToken(), 0, 8) . '...';
        }
    }


    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    public function getParameter($key, $default)
    {
        return ArrayHelper::getValue($this->getParameters(), $key, $default);
    }


    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters === null ? [] : phutil_json_decode($this->parameters);
    }

    /**
     * @param $key
     * @param $value
     * @return self
     * @throws \Exception
     */
    public function setParameter($key, $value)
    {
        $parameters = $this->getParameters();
        $parameters[$key] = $value;
        $this->parameters = phutil_json_encode($parameters);
        return $this;
    }


    /**
     * @return mixed
     * @author 陈妙威
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     */
    public function getObject()
    {
        return $this->assertAttached($this->object);
    }

    /**
     * @param PhabricatorUser $object
     * @return $this
     * @author 陈妙威
     */
    public function attachObject(PhabricatorUser $object)
    {
        $this->object = $object;
        return $this;
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
     * @return mixed
     * @author 陈妙威
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     */
    public function getPolicy($capability)
    {
        return $this->getObject()->getPolicy($capability);
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return mixed
     * @author 陈妙威
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return $this->getObject()->hasAutomaticCapability($capability, $viewer);
    }

    /**
     * @param $capability
     * @return string
     * @author 陈妙威
     */
    public function describeAutomaticCapability($capability)
    {
        return \Yii::t("app",
            'Conduit tokens inherit the policies of the user they authenticate.');
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
    public function getTokenType()
    {
        return $this->token_type;
    }

    /**
     * @param string $token_type
     * @return self
     */
    public function setTokenType($token_type)
    {
        $this->token_type = $token_type;
        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return self
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * @param int $expires
     * @return self
     */
    public function setExpires($expires)
    {
        $this->expires = $expires;
        return $this;
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return ConduitTokenPHIDType::className();
    }

    /**
     * Return a @{class:PhabricatorApplicationTransactionEditor} which can be
     * used to apply transactions to this object.
     *
     * @return PhabricatorApplicationTransactionEditor Editor for this object.
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorConfigEditor();
    }

    /**
     * Return the object to apply transactions to. Normally this is the current
     * object (that is, `$this`), but in some cases transactions may apply to
     * a different object: for example, @{class:DifferentialDiff} applies
     * transactions to the associated @{class:DifferentialRevision}.
     *
     * @return ActiveRecord Object to apply transactions to.
     */
    public function getApplicationTransactionObject()
    {
        return $this;
    }

    /**
     * Return a template transaction for this object.
     *
     * @return PhabricatorApplicationTransaction
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorConduitTokenTransaction();
    }
}

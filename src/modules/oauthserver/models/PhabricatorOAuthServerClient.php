<?php

namespace orangins\modules\oauthserver\models;

use Filesystem;
use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\oauthserver\editor\PhabricatorOAuthServerEditor;
use orangins\modules\oauthserver\phid\PhabricatorOAuthServerClientPHIDType;
use orangins\modules\oauthserver\query\PhabricatorOAuthServerClientQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use Yii;
use yii\helpers\Url;

/**
 * This is the model class for table "oauth_server_oauthserverclient".
 *
 * @property int $id
 * @property string $phid
 * @property string $name
 * @property string $secret
 * @property string $redirect_uri
 * @property string $creator_phid
 * @property int $is_trusted
 * @property string $view_policy
 * @property string $edit_policy
 * @property int $is_disabled
 * @property int $created_at
 * @property int $is_system
 * @property int $updated_at
 */
class PhabricatorOAuthServerClient extends ActiveRecordPHID
    implements PhabricatorPolicyInterface,
    PhabricatorApplicationTransactionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oauth_server_oauthserverclient';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'secret', 'is_trusted', 'view_policy', 'edit_policy', 'is_disabled'], 'required'],
            [['creator_phid'], 'default', 'value' => ''],
            [['redirect_uri'], 'default', 'value' => ''],
            [['is_trusted', 'is_disabled', 'is_system', 'created_at', 'updated_at'], 'integer'],
            [['phid', 'creator_phid', 'view_policy', 'edit_policy'], 'string', 'max' => 64],
            [['name', 'redirect_uri'], 'string', 'max' => 255],
            [['secret'], 'string', 'max' => 32],
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
            'name' => Yii::t('app', 'Name'),
            'secret' => Yii::t('app', 'Secret'),
            'redirect_uri' => Yii::t('app', 'Redirect URI'),
            'creator_phid' => Yii::t('app', 'Creator PHID'),
            'is_trusted' => Yii::t('app', 'Is Trusted'),
            'view_policy' => Yii::t('app', 'View Policy'),
            'edit_policy' => Yii::t('app', 'Edit Policy'),
            'is_disabled' => Yii::t('app', 'Is Disabled'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     * @return self
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
        return $this;
    }

    /**
     * @return string
     */
    public function getRedirectURI()
    {
        return $this->redirect_uri;
    }

    /**
     * @param string $redirect_uri
     * @return self
     */
    public function setRedirectURI($redirect_uri)
    {
        $this->redirect_uri = $redirect_uri;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreatorPHID()
    {
        return $this->creator_phid;
    }

    /**
     * @param string $creator_phid
     * @return self
     */
    public function setCreatorPHID($creator_phid)
    {
        $this->creator_phid = $creator_phid;
        return $this;
    }

    /**
     * @return int
     */
    public function getisTrusted()
    {
        return $this->is_trusted;
    }

    /**
     * @param int $is_trusted
     * @return self
     */
    public function setIsTrusted($is_trusted)
    {
        $this->is_trusted = $is_trusted;
        return $this;
    }

    /**
     * @return string
     */
    public function getViewPolicy()
    {
        return $this->view_policy;
    }

    /**
     * @param string $view_policy
     * @return self
     */
    public function setViewPolicy($view_policy)
    {
        $this->view_policy = $view_policy;
        return $this;
    }

    /**
     * @return string
     */
    public function getEditPolicy()
    {
        return $this->edit_policy;
    }

    /**
     * @param string $edit_policy
     * @return self
     */
    public function setEditPolicy($edit_policy)
    {
        $this->edit_policy = $edit_policy;
        return $this;
    }

    /**
     * @return int
     */
    public function getisDisabled()
    {
        return $this->is_disabled;
    }

    /**
     * @param int $is_disabled
     * @return self
     */
    public function setIsDisabled($is_disabled)
    {
        $this->is_disabled = $is_disabled;
        return $this;
    }

    /**
     * @return int
     */
    public function getisSystem()
    {
        return $this->is_system;
    }

    /**
     * @param int $is_system
     * @return self
     */
    public function setIsSystem($is_system)
    {
        $this->is_system = $is_system;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditURI()
    {
        $id = $this->getID();
        return "/oauthserver/edit/{$id}/";
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getViewURI()
    {
        $id = $this->getID();
        return Url::to(["/oauthserver/client/view", "id" => $id]);
    }

    /**
     * @param PhabricatorUser $actor
     * @return mixed
     * @author 陈妙威
     */
    public static function initializeNewClient(PhabricatorUser $actor)
    {
        return (new  PhabricatorOAuthServerClient())
            ->setCreatorPHID($actor->getPHID())
            ->setSecret(Filesystem::readRandomCharacters(32))
            ->setViewPolicy(PhabricatorPolicies::POLICY_USER)
            ->setEditPolicy($actor->getPHID())
            ->setIsDisabled(0)
            ->setIsTrusted(0);
    }

    /**
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    public function generatePHID()
    {
        return PhabricatorPHID::generateNewPHID(
            PhabricatorOAuthServerClientPHIDType::TYPECONST);
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
     * @return mixed
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                return $this->getViewPolicy();
            case PhabricatorPolicyCapability::CAN_EDIT:
                return $this->getEditPolicy();
        }
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return false;
    }


    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorOAuthServerEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorOAuthServerEditor();
    }

    /**
     * @return PhabricatorOAuthServerTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorOAuthServerTransaction();
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


    /* -(  PhabricatorDestructibleInterface  )----------------------------------- */


    /**
     * @param PhabricatorDestructionEngine $engine
     * @throws \Throwable
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     * @author 陈妙威
     */
    public function destroyObjectPermanently(
        PhabricatorDestructionEngine $engine)
    {

        $this->openTransaction();
        $this->delete();
        PhabricatorOAuthClientAuthorization::deleteAll([
            'client_phid' => $this->getPHID()
        ]);

        PhabricatorOAuthServerAccessToken::deleteAll([
            'client_phid' => $this->getPHID(),
        ]);

        PhabricatorOAuthServerAuthorizationCode::deleteAll([
            'client_phid' => $this->getPHID(),
        ]);
        $this->saveTransaction();

    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorOAuthServerClientPHIDType::className();
    }

    /**
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|PhabricatorOAuthServerClientQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorOAuthServerClientQuery(get_called_class());
    }
}



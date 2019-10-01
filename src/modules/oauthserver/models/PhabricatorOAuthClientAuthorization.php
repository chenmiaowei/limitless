<?php

namespace orangins\modules\oauthserver\models;

use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\oauthserver\phid\PhabricatorOAuthServerClientAuthorizationPHIDType;
use orangins\modules\oauthserver\query\PhabricatorOAuthClientAuthorizationQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use Yii;

/**
 * This is the model class for table "oauth_server_oauthclientauthorization".
 *
 * @property int $id
 * @property string $phid
 * @property string $user_phid
 * @property string $client_phid
 * @property string $scope
 * @property int $created_at
 * @property int $updated_at
 */
class PhabricatorOAuthClientAuthorization extends ActiveRecordPHID
    implements PhabricatorPolicyInterface
{
    /**
     * @var string
     */
    private $client = self::ATTACHABLE;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oauth_server_oauthclientauthorization';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_phid', 'client_phid', 'scope'], 'required'],
            [['scope'], 'string'],
            [['created_at', 'updated_at'], 'integer'],
            [['phid', 'user_phid', 'client_phid'], 'string', 'max' => 64],
            [['phid'], 'unique'],
            [['user_phid', 'client_phid'], 'unique', 'targetAttribute' => ['user_phid', 'client_phid']],
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
            'user_phid' => Yii::t('app', 'User PHID'),
            'client_phid' => Yii::t('app', 'Client PHID'),
            'scope' => Yii::t('app', 'Scope'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
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
    public function getClientPHID()
    {
        return $this->client_phid;
    }

    /**
     * @param string $client_phid
     * @return self
     */
    public function setClientPHID($client_phid)
    {
        $this->client_phid = $client_phid;
        return $this;
    }

    /**
     * @return array
     */
    public function getScope()
    {
        return $this->scope === null ? [] : phutil_json_decode($this->scope);
    }

    /**
     * @param string $scope
     * @return self
     * @throws \Exception
     */
    public function setScope($scope)
    {
        $this->scope = phutil_json_encode($scope);
        return $this;
    }
    

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getClient()
    {
        return $this->assertAttached($this->client);
    }

    /**
     * @param PhabricatorOAuthServerClient $client
     * @return $this
     * @author 陈妙威
     */
    public function attachClient(PhabricatorOAuthServerClient $client)
    {
        $this->client = $client;
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
        );
    }

    /**
     * @param $capability
     * @return string
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                return PhabricatorPolicies::POLICY_NOONE;
        }
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return ($viewer->getPHID() == $this->getUserPHID());
    }

    /**
     * @param $capability
     * @return string
     * @author 陈妙威
     */
    public function describeAutomaticCapability($capability)
    {
        return pht('Authorizations can only be viewed by the authorizing user.');
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorOAuthServerClientAuthorizationPHIDType::className();
    }

    /**
     * @return PhabricatorOAuthClientAuthorizationQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorOAuthClientAuthorizationQuery(get_called_class());
    }
}

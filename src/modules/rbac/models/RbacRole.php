<?php

namespace orangins\modules\rbac\models;

use Exception;
use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\request\AphrontRequest;
use orangins\modules\cache\PhabricatorCaches;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\rbac\editors\PhabricatorRBACRoleEditor;
use orangins\modules\rbac\phid\PhabricatorRBACRolePHIDType;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\interfaces\PhabricatorEditableInterface;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use Yii;
use yii\helpers\Url;

/**
 * This is the model class for table "rbac_role".
 *
 * @property int $id
 * @property string $phid
 * @property string $name 权限名称
 * @property string $description 权限解释
 * @property string $rule_name 规则
 * @property string $parameters
 * @property string $status 状态
 * @property int $created_at
 * @property int $updated_at
 */
class RbacRole extends ActiveRecordPHID
    implements PhabricatorApplicationTransactionInterface,
    PhabricatorPolicyInterface,
    PhabricatorEditableInterface

{
    /**
     *
     */
    const STATUS_ACTIVE = "ACTIVE";
    /**
     *
     */
    const STATUS_ARCHIVED = "ARCHIVED";
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rbac_role';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'description'], 'required'],
            [['parameters'], 'string'],
            [['created_at', 'updated_at'], 'integer'],
            [['phid', 'name', 'rule_name', 'status'], 'string', 'max' => 64],
            [['description'], 'string', 'max' => 255],
            [['name'], 'unique'],
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
            'name' => Yii::t('app', '权限名称'),
            'description' => Yii::t('app', '权限解释'),
            'rule_name' => Yii::t('app', '规则'),
            'parameters' => Yii::t('app', 'Parameters'),
            'status' => Yii::t('app', '状态'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorRBACRolePHIDType::className();
    }

    /**
     * @return \orangins\lib\infrastructure\query\PhabricatorQuery|PhabricatorRBACRoleQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorRBACRoleQuery(get_called_class());
    }

    /**
     * @author 陈妙威
     */
    public function isArchived()
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * @param $getViewer
     * @return RbacRole
     * @author 陈妙威
     */
    public static function initializeNewRole($getViewer)
    {
        return new static();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getDisplayName()
    {
        return $this->name . "（{$this->description}）";
    }

    /**
     * Return a @{class:PhabricatorApplicationTransactionEditor} which can be
     * used to apply transactions to this object.
     *
     * @return PhabricatorRBACRoleEditor
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorRBACRoleEditor();
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
        return new PhabricatorRBACRoleTransaction();
    }

    /**
     * @param PhabricatorApplicationTransactionView $timeline
     * @param AphrontRequest $request
     * @return PhabricatorApplicationTransactionView
     * @author 陈妙威
     */
    public function willRenderTimeline(
        PhabricatorApplicationTransactionView $timeline,
        AphrontRequest $request) {

        return $timeline;
    }

    /**
     * @return string[]
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
        return PhabricatorPolicies::POLICY_ADMIN;
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return false;
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getURI()
    {
        return Url::to(['/rbac/role/view', 'id' => $this->id]);
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMonogram()
    {
        return "R{$this->id}";
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getInfoURI()
    {
        return $this->getURI();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public static function getCapabilityCacheKey()
    {
        return 'rbac.capability.v1';
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public static function loadGlobalCapabilities()
    {
        $cache_key = RbacRole::getCapabilityCacheKey();
        $cache = PhabricatorCaches::getMutableStructureCache();

        $settings = $cache->getKey($cache_key);
        if (!$settings) {
            /** @var RbacRoleCapability[] $capabilities */
            $capabilities = RbacRoleCapability::find()->all();

            $settings =  [];
            foreach ($capabilities as $capability) {
                if(!isset($settings[$capability->object_phid])) $settings[$capability->object_phid] = [];
                $settings[$capability->object_phid][] = $capability->capability;
            }
            $cache->setKey($cache_key, $settings);
        }
        return $settings;
    }
}

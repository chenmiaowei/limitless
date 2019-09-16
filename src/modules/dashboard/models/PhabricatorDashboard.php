<?php

namespace orangins\modules\dashboard\models;

use orangins\lib\db\ActiveRecord;
use orangins\lib\infrastructure\edges\interfaces\PhabricatorEdgeInterface;
use orangins\modules\dashboard\editors\PhabricatorDashboardTransactionEditor;
use orangins\modules\dashboard\interfaces\PhabricatorDashboardPanelContainerInterface;
use orangins\modules\dashboard\layoutconfig\PhabricatorDashboardPanelRefList;
use orangins\modules\dashboard\phid\PhabricatorDashboardDashboardPHIDType;
use orangins\modules\dashboard\query\PhabricatorDashboardQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\system\engine\PhabricatorDestructionEngine;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use Yii;
use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\people\db\ActiveRecordAuthorTrait;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * This is the model class for table "dashboard".
 *
 * @property int $id
 * @property string $phid
 * @property string $name 名称
 * @property string $icon 名称
 * @property string $layout_config 配置
 * @property string $author_phid 作者
 * @property string $view_policy 显示权限
 * @property string $edit_policy 编辑权限
 * @property string $created_at
 * @property string $status
 * @property string $updated_at
 */
class PhabricatorDashboard extends ActiveRecordPHID
    implements
    PhabricatorPolicyInterface
    , PhabricatorEdgeInterface
    , PhabricatorApplicationTransactionInterface
    ,PhabricatorDashboardPanelContainerInterface
{
    use ActiveRecordAuthorTrait;

    /**
     *
     */
    const STATUS_ACTIVE = 'active';
    /**
     *
     */
    const STATUS_ARCHIVED = 'archived';

    /**
     * @var
     */
    private $panelRefList;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dashboard';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'icon', 'author_phid', 'view_policy', 'edit_policy'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'name', 'icon', 'author_phid', 'view_policy', 'edit_policy'], 'string', 'max' => 64],
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
            'name' => Yii::t('app', '名称'),
            'icon' => Yii::t('app', '图标'),
            'layout_config' => Yii::t('app', '配置'),
            'author_phid' => Yii::t('app', '作者'),
            'view_policy' => Yii::t('app', '显示权限'),
            'edit_policy' => Yii::t('app', '编辑权限'),
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
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     * @return self
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorPHID()
    {
        return $this->author_phid;
    }

    /**
     * @param string $author_phid
     * @return self
     */
    public function setAuthorPHID($author_phid)
    {
        $this->author_phid = $author_phid;
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
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getLayoutConfig()
    {
        return $this->layout_config === null ? null : phutil_json_decode($this->layout_config);
    }

    /**
     * @param string $layout_config
     * @return self
     * @throws \Exception
     */
    public function setLayoutConfig($layout_config)
    {
        $this->layout_config =  phutil_json_encode($layout_config);
        return $this;
    }


    /**
     * @param PhabricatorUser $actor
     * @return mixed
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function initializeNewDashboard(PhabricatorUser $actor)
    {
        return (new PhabricatorDashboard())
            ->setName('')
            ->setIcon('fa-dashboard')
            ->setViewPolicy(PhabricatorPolicies::getMostOpenPolicy())
            ->setEditPolicy($actor->getPHID())
            ->setStatus(self::STATUS_ACTIVE)
            ->setAuthorPHID($actor->getPHID());
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getStatusNameMap()
    {
        return array(
            self::STATUS_ACTIVE => \Yii::t("app",'Active'),
            self::STATUS_ARCHIVED => \Yii::t("app",'Archived'),
        );
    }



    /**
     * @return string
     * @author 陈妙威
     */
    public function getPHIDType()
    {
        return PhabricatorDashboardDashboardPHIDType::TYPECONST;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getRawLayoutMode()
    {
        $config = $this->getRawLayoutConfig();
        return ArrayHelper::getValue($config, 'layoutMode');
    }

    /**
     * @param $mode
     * @return mixed
     * @author 陈妙威
     */
    public function setRawLayoutMode($mode)
    {
        $config = $this->getRawLayoutConfig();
        $config['layoutMode'] = $mode;
        return $this->setRawLayoutConfig($config);
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getRawPanels()
    {
        $config = $this->getRawLayoutConfig();
        return ArrayHelper::getValue($config, 'panels');
    }

    /**
     * @param array $panels
     * @return mixed
     * @author 陈妙威
     */
    public function setRawPanels(array $panels)
    {
        $config = $this->getRawLayoutConfig();
        $config['panels'] = $panels;
        return $this->setRawLayoutConfig($config);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    private function getRawLayoutConfig()
    {
        $config = $this->getLayoutConfig();

        if (!is_array($config)) {
            $config = array();
        }

        return $config;
    }

    /**
     * @param array $config
     * @return mixed
     * @author 陈妙威
     */
    private function setRawLayoutConfig(array $config)
    {
        // If a cached panel ref list exists, clear it.
        $this->panelRefList = null;

        return $this->setLayoutConfig($config);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isArchived()
    {
        return ($this->getStatus() == self::STATUS_ARCHIVED);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getURI()
    {
        return Url::to([
            '/dashboard/index/view',
            'id' => $this->getID()
        ]);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getObjectName()
    {
        return \Yii::t("app",'Dashboard {0}', [$this->getID()]);
    }

    /**
     * @return PhabricatorDashboardPanelRefList
     * @author 陈妙威
     */
    public function getPanelRefList()
    {
        if (!$this->panelRefList) {
            $this->panelRefList = $this->newPanelRefList();
        }
        return $this->panelRefList;
    }

    /**
     * @return PhabricatorDashboardPanelRefList
     * @author 陈妙威
     */
    private function newPanelRefList()
    {
        $raw_config = $this->getLayoutConfig();
        return PhabricatorDashboardPanelRefList::newFromDictionary($raw_config);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getPanelPHIDs()
    {
        $ref_list = $this->getPanelRefList();
        $phids = mpull($ref_list->getPanelRefs(), 'getPanelPHID');
        return array_unique($phids);
    }

    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorDashboardTransactionEditor|\orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorDashboardTransactionEditor();
    }

    /**
     * @return PhabricatorDashboardTransaction|\orangins\modules\transactions\models\PhabricatorApplicationTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorDashboardTransaction();
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

    /* -(  PhabricatorDashboardPanelContainerInterface  )------------------------ */

    /**
     * @return array
     * @author 陈妙威
     */
    public function getDashboardPanelContainerPanelPHIDs()
    {
        return $this->getPanelPHIDs();
    }

    /* -(  PhabricatorFulltextInterface  )--------------------------------------- */

    /**
     * @return PhabricatorDashboardFulltextEngine
     * @author 陈妙威
     */
    public function newFulltextEngine()
    {
        return new PhabricatorDashboardFulltextEngine();
    }

    /* -(  PhabricatorFerretInterface  )----------------------------------------- */

    /**
     * @return PhabricatorDashboardFerretEngine
     * @author 陈妙威
     */
    public function newFerretEngine()
    {
        return new PhabricatorDashboardFerretEngine();
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorDashboardDashboardPHIDType::class;
    }

    /**
     * @return PhabricatorDashboardQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorDashboardQuery(get_called_class());
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
     * @return string
     * @author 陈妙威
     */
    public function edgeBaseTableName()
    {
        return 'dashboard';
    }
}

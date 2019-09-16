<?php

namespace orangins\modules\dashboard\models;

use orangins\lib\helpers\OranginsUtil;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\edges\interfaces\PhabricatorEdgeInterface;
use orangins\lib\request\AphrontRequest;
use orangins\modules\dashboard\editors\PhabricatorDashboardPanelTransactionEditor;
use orangins\modules\dashboard\interfaces\PhabricatorDashboardPanelContainerInterface;
use orangins\modules\dashboard\paneltype\PhabricatorDashboardPanelType;
use orangins\modules\dashboard\phid\PhabricatorDashboardPanelPHIDType;
use orangins\modules\dashboard\query\PhabricatorDashboardPanelQuery;
use orangins\modules\people\db\ActiveRecordAuthorTrait;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use Yii;
use Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * This is the model class for table "dashboard_panels".
 *
 * @property int $id
 * @property string $phid
 * @property string $name 名称
 * @property string $panel_type 名称
 * @property int $is_archived 归档
 * @property string $properties 配置
 * @property string $author_phid 作者
 * @property string $view_policy 显示权限
 * @property string $edit_policy 编辑权限
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorDashboardPanel extends ActiveRecordPHID
    implements PhabricatorPolicyInterface
    , PhabricatorApplicationTransactionInterface
    , PhabricatorEdgeInterface
    , PhabricatorDashboardPanelContainerInterface
{
    use ActiveRecordAuthorTrait;

    /**
     * @var string
     */
    private $customFields = self::ATTACHABLE;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dashboard_panels';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'panel_type', 'author_phid', 'view_policy', 'edit_policy'], 'required'],
            [['is_archived'], 'integer'],
            [['properties'], 'string'],
            [['properties'], 'default', 'value' => '[]'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'name', 'panel_type', 'author_phid', 'view_policy', 'edit_policy'], 'string', 'max' => 64],
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
            'name' => Yii::t('app', '名称'),
            'panel_type' => Yii::t('app', '类型'),
            'is_archived' => Yii::t('app', '归档'),
            'properties' => Yii::t('app', '配置'),
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
    public function getPanelType()
    {
        return $this->panel_type;
    }

    /**
     * @param string $panel_type
     * @return self
     */
    public function setPanelType($panel_type)
    {
        $this->panel_type = $panel_type;
        return $this;
    }

    /**
     * @return int
     */
    public function getisArchived()
    {
        return $this->is_archived;
    }

    /**
     * @param int $is_archived
     * @return self
     */
    public function setIsArchived($is_archived)
    {
        $this->is_archived = $is_archived;
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
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorDashboardPanelPHIDType::class;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTransactionEditor()
    {
        return null;
    }

    /**
     * @return array
     * @throws \PhutilJSONParserException
     */
    public function getProperties()
    {
        return OranginsUtil::phutil_json_decode($this->properties);
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getURI()
    {
        return Url::to(['/dashboard/panel/view', 'id' => $this->id]);
    }


    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @author 陈妙威
     */
    public function getProperty($key = null, $default = null)
    {
        $phutil_json_decode = $this->properties === null ? [] : phutil_json_decode($this->properties);
        if ($key === null) {
            return $phutil_json_decode;
        } else {
            return ArrayHelper::getValue($phutil_json_decode, $key, $default);
        }
    }

    /**
     * @param $key
     * @param $value
     * @return PhabricatorDashboardPanel
     * @throws \Exception
     * @author 陈妙威
     */
    public function setProperty($key, $value)
    {
        $parameter = $this->getProperty();
        $parameter[$key] = $value;
        $this->properties = phutil_json_encode($parameter);
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getStatuses()
    {
        $statuses =
            array(
                '' => \Yii::t("app", '(All Panels)'),
                'active' => \Yii::t("app", 'Active Panels'),
                'archived' => \Yii::t("app", 'Archived Panels'),
            );
        return $statuses;
    }

    /**
     * @return PhabricatorDashboardPanelType
     * @author 陈妙威
     */
    public function getImplementation()
    {
        return ArrayHelper::getValue(PhabricatorDashboardPanelType::getAllPanelTypes(), $this->getPanelType());
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMonogram()
    {
        return 'W' . $this->getID();
    }

    /**
     * @return PhabricatorDashboardPanelType
     * @throws Exception
     * @author 陈妙威
     */
    public function requireImplementation()
    {
        $impl = $this->getImplementation();
        if (!$impl) {
            throw new Exception(
                \Yii::t("app",
                    'Attempting to use a panel in a way that requires an ' .
                    'implementation, but the panel implementation ("{0}") is unknown to ' .
                    'Phabricator.', [
                        $this->getPanelType()
                    ]));
        }
        return $impl;
    }


    /**
     * @return PhabricatorDashboardPanelQuery
     * @author 陈妙威
     */
    public static function find()
    {
        return new PhabricatorDashboardPanelQuery(get_called_class());
    }


    /**
     * @param PhabricatorUser $actor
     * @return mixed
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public static function initializeNewPanel(PhabricatorUser $actor)
    {
        return (new PhabricatorDashboardPanel())
            ->setName('')
            ->setAuthorPHID($actor->getPHID())
            ->setViewPolicy(PhabricatorPolicies::getMostOpenPolicy())
            ->setEditPolicy($actor->getPHID());
    }


    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getEditEngineFields()
    {
        return $this->requireImplementation()->getEditEngineFields($this);
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
     * @return bool
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return false;
    }



    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorDashboardPanelTransactionEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorDashboardPanelTransactionEditor();
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
     * @return PhabricatorDashboardPanelTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorDashboardPanelTransaction();
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

    /**
     * @return string
     * @author 陈妙威
     */
    public function edgeBaseTableName()
    {
        return "dashboard";
    }

    /* -(  PhabricatorDashboardPanelContainerInterface  )------------------------ */

    /**
     * @return array
     * @throws Exception
     * @author 陈妙威
     */
    public function getDashboardPanelContainerPanelPHIDs()
    {
        return $this->requireImplementation()->getSubpanelPHIDs($this);
    }
}

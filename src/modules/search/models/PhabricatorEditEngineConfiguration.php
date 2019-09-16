<?php

namespace orangins\modules\search\models;

use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\request\AphrontRequest;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\policy\phid\PhabricatorPolicyPHIDTypePolicy;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorFileEditField;
use orangins\modules\transactions\editfield\PhabricatorInstructionsEditField;
use orangins\modules\transactions\editors\PhabricatorEditEngineConfigurationEditor;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use orangins\modules\transactions\models\PhabricatorEditEngineConfigurationTransaction;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * This is the model class for table "search_editengineconfiguration".
 *
 * @property int $id
 * @property string $phid
 * @property string $engine_key
 * @property string $builtin_key
 * @property string $name
 * @property string $view_policy
 * @property string $properties
 * @property int $is_disabled
 * @property int $is_default
 * @property int $is_edit
 * @property int $create_order
 * @property int $edit_order
 * @property string $subtype
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorEditEngineConfiguration extends ActiveRecordPHID
    implements
    PhabricatorPolicyInterface,
    PhabricatorApplicationTransactionInterface
{
    /**
     * @var PhabricatorEditEngine
     */
    public $engine;

    /**
     *
     */
    const LOCK_VISIBLE = 'visible';
    /**
     *
     */
    const LOCK_LOCKED = 'locked';
    /**
     *
     */
    const LOCK_HIDDEN = 'hidden';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'search_editengineconfiguration';
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['engine_key', 'name', 'view_policy', 'properties', 'is_disabled', 'is_default', 'is_edit', 'create_order', 'edit_order', 'subtype'], 'required'],
            [['properties'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'engine_key', 'builtin_key', 'view_policy', 'subtype'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 255],
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
            'engine_key' => Yii::t('app', 'Engine Key'),
            'builtin_key' => Yii::t('app', 'Builtin Key'),
            'name' => Yii::t('app', 'Name'),
            'view_policy' => Yii::t('app', 'View Policy'),
            'properties' => Yii::t('app', 'Properties'),
            'is_disabled' => Yii::t('app', 'Is Disabled'),
            'is_default' => Yii::t('app', 'Is Default'),
            'is_edit' => Yii::t('app', 'Is Edit'),
            'create_order' => Yii::t('app', 'Create Order'),
            'edit_order' => Yii::t('app', 'Edit Order'),
            'subtype' => Yii::t('app', 'Subtype'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorEditEngineConfigurationQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PhabricatorEditEngineConfigurationQuery(get_called_class());
    }

    /**
     * @param $getViewer
     * @param PhabricatorEditEngine $engine
     * @return PhabricatorEditEngineConfiguration
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public static function initializeNewConfiguration($getViewer, PhabricatorEditEngine $engine)
    {
        $searchEditengineconfiguration = new PhabricatorEditEngineConfiguration();
        $searchEditengineconfiguration->subtype = PhabricatorEditEngine::SUBTYPE_DEFAULT;
        $searchEditengineconfiguration->engine_key = $engine->getEngineKey();
        $searchEditengineconfiguration->view_policy = PhabricatorPolicies::getMostOpenPolicy();
        $searchEditengineconfiguration->is_disabled = 0;
        $searchEditengineconfiguration->is_default = 0;
        $searchEditengineconfiguration->is_edit = 0;
        $searchEditengineconfiguration->create_order = 0;
        $searchEditengineconfiguration->edit_order = 0;
        $searchEditengineconfiguration->attachEngine($engine);
        return $searchEditengineconfiguration;
    }

    /**
     * @param PhabricatorEditEngine $engine
     * @return $this
     * @author 陈妙威
     */
    public function attachEngine(PhabricatorEditEngine $engine)
    {
        $this->engine = $engine;
        return $this;
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getCreateSortKey()
    {
        return $this->getSortKey($this->create_order);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditSortKey()
    {
        return $this->getSortKey($this->edit_order);
    }

    /**
     * @param $order
     * @return string
     * @author 陈妙威
     */
    private function getSortKey($order)
    {
        // Put objects at the bottom by default if they haven't previously been
        // reordered. When they're explicitly reordered, the smallest sort key we
        // assign is 1, so if the object has a value of 0 it means it hasn't been
        // ordered yet.
        if ($order != 0) {
            $group = 'A';
        } else {
            $group = 'B';
        }

        return sprintf(
            "%s%012d%s\0%012d",
            $group,
            $order,
            $this->name,
            $this->getID());
    }

    /**
     * @return mixed
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
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        switch ($capability) {
            case PhabricatorPolicyCapability::CAN_VIEW:
                return $this->view_policy;
            case PhabricatorPolicyCapability::CAN_EDIT:
                return $this->engine
                    ->getApplication()
                    ->getPolicy($capability);
        }
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        // TODO: Implement hasAutomaticCapability() method.
    }

    /**
     * @param PhabricatorEditEngine $engine
     * @param ActiveRecord $object
     * @param array $fields
     * @return array
     * @author 陈妙威
     * @throws \Exception
     */
    public function applyConfigurationToFields(
        PhabricatorEditEngine $engine,
        ActiveRecord $object,
        array $fields)
    {
        /** @var PhabricatorFileEditField[] $fields */
        $fields = mpull($fields, null, 'getKey');

        $is_new = !$object->getID();

        $values = $this->getProperty('defaults', array());
        foreach ($fields as $key => $field) {
            if (!$field->getIsFormField()) {
                continue;
            }

            if (!$field->getIsDefaultable()) {
                continue;
            }

            if ($is_new) {
                if (array_key_exists($key, $values)) {
                    $field->readDefaultValueFromConfiguration($values[$key]);
                }
            }
        }

        $locks = $this->getFieldLocks();
        foreach ($fields as $field) {
            $key = $field->getKey();
            switch (ArrayHelper::getValue($locks, $key)) {
                case self::LOCK_LOCKED:
                    $field->setIsHidden(false);
                    if ($field->getIsLockable()) {
                        $field->setIsLocked(true);
                    }
                    break;
                case self::LOCK_HIDDEN:
                    $field->setIsHidden(true);
                    if ($field->getIsLockable()) {
                        $field->setIsLocked(false);
                    }
                    break;
                case self::LOCK_VISIBLE:
                    $field->setIsHidden(false);
                    if ($field->getIsLockable()) {
                        $field->setIsLocked(false);
                    }
                    break;
                default:
                    // If we don't have an explicit value, don't make any adjustments.
                    break;
            }
        }

        $fields = $this->reorderFields($fields);

        $preamble = $this->getPreamble();
        if (strlen($preamble)) {
            $fields = array(
                    'config.preamble' => (new PhabricatorInstructionsEditField())
                        ->setKey('config.preamble')
                        ->setIsReorderable(false)
                        ->setIsDefaultable(false)
                        ->setIsLockable(false)
                        ->setValue($preamble),
                ) + $fields;
        }

        return $fields;
    }

    /**
     * @param array $fields
     * @return array
     * @author 陈妙威
     * @throws \Exception
     */
    private function reorderFields(array $fields)
    {
        // Fields which can not be reordered are fixed in order at the top of the
        // form. These are used to show instructions or contextual information.

        $fixed = array();
        foreach ($fields as $key => $field) {
            if (!$field->getIsReorderable()) {
                $fixed[$key] = $field;
            }
        }

        $keys = $this->getFieldOrder();

        $fields = $fixed + array_select_keys($fields, $keys) + $fields;

        return $fields;
    }

    /**
     * @return PhabricatorEditEngine
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getEngine()
    {
        return $this->assertAttached($this->engine);
    }


    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getIdentifier()
    {
        $key = $this->getID();
        if (!$key) {
            $key = $this->getBuiltinKey();
        }
        return $key;
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getDisplayName()
    {
        $name = $this->getName();
        if (strlen($name)) {
            return $name;
        }

        $builtin = $this->getBuiltinKey();
        if ($builtin !== null) {
            return \Yii::t("app",'Builtin Form "%s"', $builtin);
        }

        return \Yii::t("app",'Untitled Form');
    }


    /**
     * @return string
     * @author 陈妙威
     */
    public function getURI()
    {
        $engine_key = $this->getEngineKey();
        $key = $this->getIdentifier();

        return Url::to([
            "/transactions/editengine/view",
            "engineKey" => $engine_key,
            "key" => $key
        ]);
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function getPreamble()
    {
        return $this->getProperty('preamble');
    }

    /**
     * @param $preamble
     * @author 陈妙威
     * @return PhabricatorEditEngineConfiguration
     * @throws \Exception
     */
    public function setPreamble($preamble)
    {
        return $this->setProperty('preamble', $preamble);
    }

    /**
     * @param array $field_order
     * @author 陈妙威
     * @return PhabricatorEditEngineConfiguration
     * @throws \Exception
     */
    public function setFieldOrder(array $field_order)
    {
        return $this->setProperty('order', $field_order);
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function getFieldOrder()
    {
        return $this->getProperty('order', array());
    }

    /**
     * @param array $field_locks
     * @author 陈妙威
     * @return PhabricatorEditEngineConfiguration
     * @throws \Exception
     */
    public function setFieldLocks(array $field_locks)
    {
        return $this->setProperty('locks', $field_locks);
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function getFieldLocks()
    {
        return $this->getProperty('locks', array());
    }

    /**
     * @param $key
     * @return object
     * @throws \Exception
     * @author 陈妙威
     */
    public function getFieldDefault($key)
    {
        $defaults = $this->getProperty('defaults', array());
        return ArrayHelper::getValue($defaults, $key);
    }

    /**
     * @param $key
     * @param $value
     * @return PhabricatorEditEngineConfiguration
     * @throws \Exception
     * @author 陈妙威
     */
    public function setFieldDefault($key, $value)
    {
        $defaults = $this->getProperty('defaults', array());
        $defaults[$key] = $value;
        return $this->setProperty('defaults', $defaults);
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function getIcon()
    {
        return $this->getEngine()->getIcon();
    }


    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorEditEngineConfigurationEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorEditEngineConfigurationEditor();
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
     * @return PhabricatorEditEngineConfigurationTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorEditEngineConfigurationTransaction();
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
     */
    public function getBuiltinKey()
    {
        return $this->builtin_key;
    }

    /**
     * @param string $builtin_key
     * @return self
     */
    public function setBuiltinKey($builtin_key)
    {
        $this->builtin_key = $builtin_key;
        return $this;
    }


    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorPolicyPHIDTypePolicy::class;
    }

    /**
     * @param $string
     * @param $default
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    private function getProperty($string, $default = null)
    {
        $array = $this->properties === null ? [] : phutil_json_decode($this->properties);
        return ArrayHelper::getValue($array, $string, $default);
    }

    /**
     * @author 陈妙威
     * @param $key
     * @param $value
     * @return PhabricatorEditEngineConfiguration
     * @throws \Exception
     */
    private function setProperty($key, $value) {
        $array = $this->properties === null ? [] : phutil_json_decode($this->properties);
        $array[$key] = $value;
        $this->properties = phutil_json_encode($array);
        return $this;
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
    public function getEngineKey()
    {
        return $this->engine_key;
    }

    /**
     * @param string $engine_key
     * @return self
     */
    public function setEngineKey($engine_key)
    {
        $this->engine_key = $engine_key;
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
        $this->is_disabled = $is_disabled ? 1 : 0;
        return $this;
    }

    /**
     * @return int
     */
    public function getisDefault()
    {
        return $this->is_default;
    }

    /**
     * @param int $is_default
     * @return self
     */
    public function setIsDefault($is_default)
    {
        $this->is_default = $is_default ? 1 : 0;
        return $this;
    }

    /**
     * @return int
     */
    public function getisEdit()
    {
        return $this->is_edit;
    }

    /**
     * @param int $is_edit
     * @return self
     */
    public function setIsEdit($is_edit)
    {
        $this->is_edit = $is_edit ? 1 : 0;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreateOrder()
    {
        return $this->create_order;
    }

    /**
     * @param int $create_order
     * @return self
     */
    public function setCreateOrder($create_order)
    {
        $this->create_order = $create_order ? 1 : 0;
        return $this;
    }

    /**
     * @return int
     */
    public function getEditOrder()
    {
        return $this->edit_order;
    }

    /**
     * @param int $edit_order
     * @return self
     */
    public function setEditOrder($edit_order)
    {
        $this->edit_order = $edit_order ? 1 : 0;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * @param string $subtype
     * @return self
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;
        return $this;
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getCreateURI() {
        $form_key = $this->getIdentifier();
        $engine = $this->getEngine();
        return $engine->getCreateURI($form_key);
    }
}

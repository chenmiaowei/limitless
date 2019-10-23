<?php

namespace orangins\modules\search\models;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\favorites\application\PhabricatorFavoritesApplication;
use PhutilSortVector;
use orangins\modules\home\application\PhabricatorHomeApplication;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\search\editors\PhabricatorProfileMenuEditor;
use orangins\modules\search\menuitems\PhabricatorApplicationProfileMenuItem;
use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\search\phidtype\PhabricatorProfileMenuItemPHIDType;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "search_profilepanelconfiguration".
 *
 * @property int $id
 * @property string $phid
 * @property string $profile_phid
 * @property string $menu_item_key
 * @property string $builtin_key
 * @property int $menu_item_order
 * @property string $visibility
 * @property string $menu_item_properties
 * @property string $custom_phid
 * @property string $created_at
 * @property string $updated_at
 */
class PhabricatorProfileMenuItemConfiguration extends ActiveRecordPHID
    implements
    PhabricatorPolicyInterface,
    PhabricatorApplicationTransactionInterface
{
    /**
     * @var string
     */
    private $profileObject = self::ATTACHABLE;
    /**
     * @var string
     */
    private $menuItem = self::ATTACHABLE;

    /**
     * @var bool
     */
    private $isHeadItem = false;
    /**
     * @var bool
     */
    private $isTailItem = false;
    /**
     *
     */
    const VISIBILITY_DEFAULT = 'default';
    /**
     *
     */
    const VISIBILITY_VISIBLE = 'visible';
    /**
     *
     */
    const VISIBILITY_DISABLED = 'disabled';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'search_profilepanelconfiguration';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['profile_phid', 'menu_item_key', 'visibility'], 'required'],
            [['menu_item_order'], 'integer'],
            [['menu_item_properties'], 'string'],
            [['menu_item_properties'], 'default', 'value' => '[]'],
            [['created_at', 'updated_at'], 'safe'],
            [['phid', 'profile_phid', 'menu_item_key', 'builtin_key', 'custom_phid'], 'string', 'max' => 64],
            [['visibility'], 'string', 'max' => 32],
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
            'profile_phid' => Yii::t('app', 'Profile Phid'),
            'menu_item_key' => Yii::t('app', 'Menu Item Key'),
            'builtin_key' => Yii::t('app', 'Builtin Key'),
            'menu_item_order' => Yii::t('app', 'Menu Item Order'),
            'visibility' => Yii::t('app', 'Visibility'),
            'menu_item_properties' => Yii::t('app', 'Menu Item Properties'),
            'custom_phid' => Yii::t('app', 'Custom Phid'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return PhabricatorProfileMenuItemConfigurationQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PhabricatorProfileMenuItemConfigurationQuery(get_called_class());
    }


    /**
     * @return PhabricatorProfileMenuItemConfiguration
     * @author 陈妙威
     */
    public static function initializeNewBuiltin()
    {
        return (new self())
            ->setVisibility(self::VISIBILITY_VISIBLE);
    }

    /**
     * @param PhabricatorHomeApplication $profile_object
     * @param PhabricatorProfileMenuItem $item
     * @param $custom_phid
     * @return PhabricatorProfileMenuItemConfiguration
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public static function initializeNewItem(
        $profile_object,
        PhabricatorProfileMenuItem $item,
        $custom_phid)
    {

        return self::initializeNewBuiltin()
            ->setProfilePHID($profile_object->getPHID())
            ->setMenuItemKey($item->getMenuItemKey())
            ->attachMenuItem($item)
            ->attachProfileObject($profile_object)
            ->setCustomPHID($custom_phid);
    }

    /**
     * @return string
     */
    public function getProfilePhid()
    {
        return $this->profile_phid;
    }

    /**
     * @param string $profile_phid
     * @return self
     */
    public function setProfilePhid($profile_phid)
    {
        $this->profile_phid = $profile_phid;
        return $this;
    }

    /**
     * @return string
     */
    public function getMenuItemKey()
    {
        return $this->menu_item_key;
    }

    /**
     * @param string $menu_item_key
     * @return self
     */
    public function setMenuItemKey($menu_item_key)
    {
        $this->menu_item_key = $menu_item_key;
        return $this;
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
     * @return int
     */
    public function getMenuItemOrder()
    {
        return $this->menu_item_order;
    }

    /**
     * @param int $menu_item_order
     * @return self
     */
    public function setMenuItemOrder($menu_item_order)
    {
        $this->menu_item_order = $menu_item_order;
        return $this;
    }

    /**
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param string $visibility
     * @return self
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
        return $this;
    }

    /**
     * @return string
     */
    public function getMenuItemProperties()
    {
        $phutil_json_decode = $this->menu_item_properties === null ? [] : phutil_json_decode($this->menu_item_properties);
        return $phutil_json_decode;
    }

    /**
     * @param array $menu_item_properties
     * @return self
     * @throws \Exception
     */
    public function setMenuItemProperties($menu_item_properties)
    {
        $this->menu_item_properties = phutil_json_encode($menu_item_properties);
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     * @throws \Exception
     * @author 陈妙威
     */
    public function setMenuItemProperty($key, $value)
    {
        $parameter = $this->getMenuItemProperty();
        $parameter[$key] = $value;
        $this->menu_item_properties = OranginsUtil::phutil_json_encode($parameter);
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return array|mixed
     * @author 陈妙威
     */
    public function getMenuItemProperty($key = null, $default = null)
    {
        $phutil_json_decode = $this->menu_item_properties === null ? [] : phutil_json_decode($this->menu_item_properties);
        if ($key === null) {
            return $phutil_json_decode;
        } else {
            return ArrayHelper::getValue($phutil_json_decode, $key, $default);
        }
    }

    /**
     * @return bool
     */
    public function isHeadItem()
    {
        return $this->isHeadItem;
    }

    /**
     * @param bool $isHeadItem
     * @return self
     */
    public function setIsHeadItem($isHeadItem)
    {
        $this->isHeadItem = $isHeadItem;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTailItem()
    {
        return $this->isTailItem;
    }

    /**
     * @param bool $isTailItem
     * @return self
     */
    public function setIsTailItem($isTailItem)
    {
        $this->isTailItem = $isTailItem;
        return $this;
    }

    /**
     * @param $identifier
     * @return bool
     * @author 陈妙威
     */
    public function matchesIdentifier($identifier) {
        if (!strlen($identifier)) {
            return false;
        }

        if (ctype_digit($identifier)) {
            if ((int)$this->getID() === (int)$identifier) {
                return true;
            }
        }

        if ((string)$this->getBuiltinKey() === (string)$identifier) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getCustomPHID()
    {
        return $this->custom_phid;
    }

    /**
     * @param string $custom_phid
     * @return self
     */
    public function setCustomPHID($custom_phid)
    {
        $this->custom_phid = $custom_phid;
        return $this;
    }


    /**
     * @param PhabricatorProfileMenuItem $item
     * @return $this
     * @author 陈妙威
     */
    public function attachMenuItem(PhabricatorProfileMenuItem $item)
    {
        $this->menuItem = $item;
        return $this;
    }

    /**
     * @return PhabricatorApplicationProfileMenuItem
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getMenuItem()
    {
        return $this->assertAttached($this->menuItem);
    }

    /**
     * @param $profile_object
     * @return $this
     * @author 陈妙威
     */
    public function attachProfileObject($profile_object)
    {
        $this->profileObject = $profile_object;
        return $this;
    }

    /**
     * @return PhabricatorUser
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getProfileObject()
    {
        return $this->assertAttached($this->profileObject);
    }

    /**
     * @return array
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getAffectedObjectPHIDs() {
        return $this->getMenuItem()->getAffectedObjectPHIDs($this);
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function buildNavigationMenuItems()
    {
        return $this->getMenuItem()->buildNavigationMenuItems($this);
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getMenuItemTypeName()
    {
        return $this->getMenuItem()->getMenuItemTypeName();
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function getDisplayName()
    {
        return $this->getMenuItem()->getDisplayName($this);
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function canMakeDefault()
    {
        return $this->getMenuItem()->canMakeDefault($this);
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function canHideMenuItem()
    {
        return $this->getMenuItem()->canHideMenuItem($this);
    }

    /**
     * @param $object
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function shouldEnableForObject($object)
    {
        return $this->getMenuItem()->shouldEnableForObject($object);
    }

    /**
     * @param array $items
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function willGetMenuItemViewList(array $items) {
        return $this->getMenuItem()->willGetMenuItemViewList($items);
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \Exception
     * @author 陈妙威
     */
    public function getMenuItemViewList() {
        return $this->getMenuItem()->getMenuItemViewList($this);
    }


    /**
     * @param array $map
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function validateTransactions(array $map)
    {
        $item = $this->getMenuItem();

        $fields = $item->buildEditEngineFields($this);
        $errors = array();
        foreach ($fields as $field) {
            $field_key = $field->getKey();

            $xactions = ArrayHelper::getValue($map, $field_key, array());
            $value = $this->getMenuItemProperty($field_key);

            $field_errors = $item->validateTransactions(
                $this,
                $field_key,
                $value,
                $xactions);
            foreach ($field_errors as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * @return PhutilSortVector
     * @author 陈妙威
     */
    public function getSortVector()
    {
        // Sort custom items above global items.
        if ($this->getCustomPHID()) {
            $is_global = 0;
        } else {
            $is_global = 1;
        }

        // Sort items with an explicit order above items without an explicit order,
        // so any newly created builtins go to the bottom.
        $order = $this->getMenuItemOrder();
        if ($order !== null) {
            $has_order = 0;
        } else {
            $has_order = 1;
        }

        return (new PhutilSortVector())
            ->addInt($is_global)
            ->addInt($has_order)
            ->addInt((int)$order)
            ->addInt((int)$this->getID());
    }


    /**
     * @return bool
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function isDisabled()
    {
        if (!$this->canHideMenuItem()) {
            return false;
        }
        return ($this->getVisibility() === self::VISIBILITY_DISABLED);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isDefault()
    {
        return ($this->getVisibility() === self::VISIBILITY_DEFAULT);
    }

    /**
     * @return int|string
     * @author 陈妙威
     */
    public function getItemIdentifier()
    {
        $id = $this->getID();

        if ($id) {
            return (int)$id;
        }

        return $this->getBuiltinKey();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getDefaultMenuItemKey()
    {
        if ($this->getBuiltinKey()) {
            return $this->getBuiltinKey();
        }

        return $this->getPHID();
    }

    /**
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function newPageContent()
    {
        return $this->getMenuItem()->newPageContent($this);
    }


    /**
     * @return string
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getProfileMenuTypeDescription()
    {
        $profile_phid = $this->getProfilePHID();

        $home_phid = (new PhabricatorHomeApplication())->getPHID();
        if ($profile_phid === $home_phid) {
            return \Yii::t("app", 'Home Menu');
        }

        $favorites_phid = (new PhabricatorFavoritesApplication())->getPHID();
        if ($profile_phid === $favorites_phid) {
            return \Yii::t("app", 'Favorites Menu');
        }

//        switch (PhabricatorPHID::phid_get_type($profile_phid)) {
//            case PhabricatorDashboardPortalPHIDType::TYPECONST:
//                return \Yii::t("app", 'Portal Menu');
//        }

        return \Yii::t("app", 'Profile Menu');
    }

    /**
     * @return PhutilSortVector
     * @author 陈妙威
     */
    public function newUsageSortVector()
    {
        // Used to sort items in contexts where we're showing the usage of an
        // object in menus, like "Dashboard Used By" on Dashboard pages.

        // Sort usage as a custom item after usage as a global item.
        if ($this->getCustomPHID()) {
            $is_personal = 1;
        } else {
            $is_personal = 0;
        }

        return (new PhutilSortVector())
            ->addInt($is_personal)
            ->addInt($this->getID());
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
     * @return mixed|string
     * @throws \Exception
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        return PhabricatorPolicies::getMostOpenPolicy();
    }


    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return mixed
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \Exception
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        return $this->getProfileObject()->hasAutomaticCapability($capability, $viewer);
    }

    /**
     * PHIDType class name
     * @return string
     * @author 陈妙威
     */
    public function getPHIDTypeClassName()
    {
        return PhabricatorProfileMenuItemPHIDType::class;
    }




    /* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


    /**
     * @return PhabricatorProfileMenuEditor|\orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor
     * @author 陈妙威
     */
    public function getApplicationTransactionEditor()
    {
        return new PhabricatorProfileMenuEditor();
    }

    /**
     * @return $this|\orangins\lib\db\ActiveRecord
     * @author 陈妙威
     */
    public function getApplicationTransactionObject()
    {
        return $this;
    }

    /**
     * @return PhabricatorProfileMenuItemConfigurationTransaction|\orangins\modules\transactions\models\PhabricatorApplicationTransaction
     * @author 陈妙威
     */
    public function getApplicationTransactionTemplate()
    {
        return new PhabricatorProfileMenuItemConfigurationTransaction();
    }
}

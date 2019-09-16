<?php

namespace orangins\modules\search\menuitems;

use Exception;
use orangins\modules\search\engine\PhabricatorProfileMenuItemView;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfigurationTransaction;
use PhutilClassMapQuery;
use orangins\modules\home\engine\PhabricatorHomeProfileMenuEngine;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\search\engine\PhabricatorProfileMenuEngine;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\transactions\error\PhabricatorApplicationTransactionValidationError;
use Yii;
use orangins\lib\OranginsObject;

/**
 * Class PhabricatorProfileMenuItem
 * @package orangins\modules\search\menuitems
 * @author 陈妙威
 */
abstract class PhabricatorProfileMenuItem extends OranginsObject
{

    /**
     * @var PhabricatorUser
     */
    private $viewer;
    /**
     * @var PhabricatorHomeProfileMenuEngine
     */
    private $engine;

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return mixed
     * @author 陈妙威
     */
    final public function buildNavigationMenuItems(PhabricatorProfileMenuItemConfiguration $config)
    {
        return $this->newMenuItemViewList($config);
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newMenuItemViewList(PhabricatorProfileMenuItemConfiguration $config);


    /**
     * @return null
     * @author 陈妙威
     */
    public function getMenuItemTypeIcon()
    {
        return null;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getMenuItemTypeName();

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getDisplayName(PhabricatorProfileMenuItemConfiguration $config);

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return array
     * @author 陈妙威
     */
    public function buildEditEngineFields(PhabricatorProfileMenuItemConfiguration $config)
    {
        return array();
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isPinnedByDefault()
    {
        return false;
    }

      /**
     * @return bool
     * @author 陈妙威
     */
    public function isFavoriteByDefault()
    {
        return false;
    }


    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function canAddToObject($object)
    {
        return false;
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function shouldEnableForObject($object)
    {
        return true;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return bool
     * @author 陈妙威
     */
    public function canHideMenuItem(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return true;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return bool
     * @author 陈妙威
     */
    public function canMakeDefault(PhabricatorProfileMenuItemConfiguration $config)
    {
        return false;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return PhabricatorUser
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorProfileMenuEngine $engine
     * @return $this
     * @author 陈妙威
     */
    public function setEngine(PhabricatorProfileMenuEngine $engine)
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * @return PhabricatorHomeProfileMenuEngine
     * @author 陈妙威
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return mixed
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getMenuItemKey()
    {
        return $this->getPhobjectClassConstant('MENUITEMKEY');
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getMenuItemOrder()
    {
        return 1000;
    }

    /**
     * @return PhabricatorApplicationProfileMenuItem[]
     * @author 陈妙威
     */
    final public static function getAllMenuItems()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorProfileMenuItem::class)
            ->setUniqueMethod('getMenuItemKey')
            ->setSortMethod('getMenuItemOrder')
            ->execute();
    }

    /**
     * @param array $items
     * @author 陈妙威
     */
    public function willGetMenuItemViewList(array $items) {}

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public function getMenuItemViewList(
        PhabricatorProfileMenuItemConfiguration $config) {
        $list = $this->newMenuItemViewList($config);

        if (!is_array($list)) {
            throw new Exception(
                pht(
                    'Expected "newMenuItemViewList()" to return a list (in class "%s"), '.
                    'but it returned something else ("%s").',
                    get_class($this),
                    phutil_describe_type($list)));
        }

        assert_instances_of($list, PhabricatorProfileMenuItemView::className());

        foreach ($list as $view) {
            $view->setMenuItemConfiguration($config);
        }

        return $list;
    }

    /**
     * @return PhabricatorProfileMenuItemView
     * @author 陈妙威
     */
    protected function newItemView()
    {
        return new PhabricatorProfileMenuItemView();
    }


    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return null
     * @author 陈妙威
     */
    public function newPageContent(
        PhabricatorProfileMenuItemConfiguration $config)
    {
        return null;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @return mixed
     * @author 陈妙威
     */
    public function getItemViewURI(
        PhabricatorProfileMenuItemConfiguration $config)
    {

        $engine = $this->getEngine();
        $key = $config->getItemIdentifier();

        return $engine->getItemURI([
            'itemAction' => 'view',
            'itemID' => $key
        ]);
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $config
     * @param $field_key
     * @param $value
     * @param array $xactions
     * @return array
     * @author 陈妙威
     */
    public function validateTransactions(
        PhabricatorProfileMenuItemConfiguration $config,
        $field_key,
        $value,
        array $xactions)
    {
        return array();
    }

    /**
     * @param $value
     * @param array $xactions
     * @return bool
     * @author 陈妙威
     */
    final protected function isEmptyTransaction($value, array $xactions)
    {
        $result = $value;
        foreach ($xactions as $xaction) {
            $result = $xaction['new'];
        }

        return !strlen($result);
    }

    /**
     * @param $title
     * @param $message
     * @param null $xaction
     * @return PhabricatorApplicationTransactionValidationError
     * @author 陈妙威
     */
    final protected function newError($title, $message, $xaction = null)
    {
        return new PhabricatorApplicationTransactionValidationError(
            PhabricatorProfileMenuItemConfigurationTransaction::TYPE_PROPERTY,
            $title,
            $message,
            $xaction);
    }

    /**
     * @param $message
     * @param $type
     * @return mixed
     * @throws \PhutilJSONParserException
     * @author 陈妙威
     */
    final protected function newRequiredError($message, $type)
    {
        $xaction = (new PhabricatorProfileMenuItemConfigurationTransaction())
            ->setMetadataValue('property.key', $type);

        return $this->newError(Yii::t("app", 'Required'), $message, $xaction)
            ->setIsMissingFieldError(true);
    }

    /**
     * @param $message
     * @param null $xaction
     * @return PhabricatorApplicationTransactionValidationError
     * @author 陈妙威
     */
    final protected function newInvalidError($message, $xaction = null)
    {
        return $this->newError(Yii::t("app", 'Invalid'), $message, $xaction);
    }

}

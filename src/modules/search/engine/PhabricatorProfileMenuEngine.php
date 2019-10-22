<?php

namespace orangins\modules\search\engine;

use AphrontDuplicateKeyQueryException;
use orangins\lib\actions\PhabricatorAction;
use orangins\lib\db\PhabricatorDataNotAttachedException;
use orangins\lib\OranginsObject;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\response\AphrontResponseProducerInterface;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\layout\PhabricatorActionListView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\layout\PHUICurtainPanelView;
use orangins\lib\view\layout\PHUICurtainView;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\search\assets\JavelinReorderProfileMenuAsset;
use orangins\modules\search\editors\PhabricatorProfileMenuEditEngine;
use orangins\modules\search\editors\PhabricatorProfileMenuEditor;
use orangins\modules\search\menuitems\PhabricatorDividerProfileMenuItem;
use orangins\modules\search\menuitems\PhabricatorManageProfileMenuItem;
use orangins\modules\search\menuitems\PhabricatorProfileMenuItem;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfigurationTransaction;
use Exception;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use PhutilMethodNotImplementedException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\StaleObjectException;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorProfileMenuEngine
 * @package orangins\modules\search\engine
 * @author 陈妙威
 */
abstract class PhabricatorProfileMenuEngine extends OranginsObject
{

    /**
     * @var PhabricatorUser
     */
    private $viewer;
    /**
     * @var PhabricatorUser
     */
    private $profileObject;
    /**
     * @var
     */
    private $customPHID;
    /**
     * @var
     */
    private $items;
    /**
     * @var
     */
    private $defaultItem;
    /**
     * @var PhabricatorAction
     */
    private $action;
    /**
     * @var
     */
    private $navigation;
    /**
     * @var bool
     */
    private $showNavigation = true;
    /**
     * @var
     */
    private $editMode;
    /**
     * @var array
     */
    private $pageClasses = array();
    /**
     * @var bool
     */
    private $showContentCrumbs = true;

    /**
     *
     */
    const ITEM_CUSTOM_DIVIDER = 'engine.divider';
    /**
     *
     */
    const ITEM_MANAGE = 'item.configure';

    /**
     *
     */
    const MODE_COMBINED = 'combined';
    /**
     *
     */
    const MODE_GLOBAL = 'global';
    /**
     *
     */
    const MODE_CUSTOM = 'custom';

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
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param $profile_object
     * @return $this
     * @author 陈妙威
     */
    public function setProfileObject($profile_object)
    {
        $this->profileObject = $profile_object;
        return $this;
    }

    /**
     * @return PhabricatorUser
     * @author 陈妙威
     */
    public function getProfileObject()
    {
        return $this->profileObject;
    }

    /**
     * @param $custom_phid
     * @return $this
     * @author 陈妙威
     */
    public function setCustomPHID($custom_phid)
    {
        $this->customPHID = $custom_phid;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCustomPHID()
    {
        return $this->customPHID;
    }

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    private function getEditModeCustomPHID()
    {
        $mode = $this->getEditMode();

        $custom_phid = null;
        switch ($mode) {
            case self::MODE_CUSTOM:
                $custom_phid = $this->getCustomPHID();
                break;
            case self::MODE_GLOBAL:
                $custom_phid = null;
                break;
        }
        return $custom_phid;
    }

    /**
     * @return PhabricatorAction
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param PhabricatorAction $action
     * @return self
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $default_item
     * @return $this
     * @author 陈妙威
     */
    private function setDefaultItem(PhabricatorProfileMenuItemConfiguration $default_item)
    {
        $this->defaultItem = $default_item;
        return $this;
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getDefaultItem()
    {
        $this->getItems();
        return $this->defaultItem;
    }

    /**
     * @param $show
     * @return $this
     * @author 陈妙威
     */
    public function setShowNavigation($show)
    {
        $this->showNavigation = $show;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getShowNavigation()
    {
        return $this->showNavigation;
    }

    /**
     * @param $class
     * @return $this
     * @author 陈妙威
     */
    public function addContentPageClass($class)
    {
        $this->pageClasses[] = $class;
        return $this;
    }

    /**
     * @param $show_content_crumbs
     * @return $this
     * @author 陈妙威
     */
    public function setShowContentCrumbs($show_content_crumbs)
    {
        $this->showContentCrumbs = $show_content_crumbs;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getShowContentCrumbs()
    {
        return $this->showContentCrumbs;
    }

    /**
     * @param $params
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getItemURI($params);

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function isMenuEngineConfigurable();

    /**
     * @param $object
     * @return PhabricatorProfileMenuItemConfiguration[]
     * @author 陈妙威
     */
    abstract protected function getBuiltinProfileItems($object);

    /**
     * @param $object
     * @param $custom_phid
     * @return PhabricatorProfileMenuItemConfiguration[]
     * @author 陈妙威
     */
    protected function getBuiltinCustomProfileItems($object, $custom_phid)
    {
        return array();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getEditMode()
    {
        return $this->editMode;
    }

    /**
     * @return Aphront404Response|AphrontRedirectResponse|PhabricatorStandardPageView
     * @throws Exception
     * @throws ReflectionException
     * @throws PhabricatorDataNotAttachedException
     * @throws StaleObjectException
     * @throws Throwable
     * @author 陈妙威
     */
    public function buildResponse()
    {
        $controller = $this->getAction();
        $viewer = $controller->getViewer();
        $this->setViewer($viewer);

        $request = $controller->getRequest();

        $item_action = $request->getURIData('itemAction');
        if (!$item_action) {
            $item_action = 'view';
        }

        $is_view = ($item_action == 'view');

        // If the engine is not configurable, don't respond to any of the editing
        // or configuration routes.
        if (!$this->isMenuEngineConfigurable()) {
            if (!$is_view) {
                return new Aphront404Response();
            }
        }

        $item_id = $request->getURIData('itemID');

        // If we miss on the MenuEngine route, try the EditEngine route. This will
        // be populated while editing items.
        if (!$item_id) {
            $item_id = $request->getURIData('id');
        }

        $view_list = $this->newProfileMenuItemViewList();

        if ($is_view) {
            $selected_item = $this->selectViewItem($view_list, $item_id);
        } else {
            if (!strlen($item_id)) {
                $item_id = self::ITEM_MANAGE;
            }
            $selected_item = $this->selectEditItem($view_list, $item_id);
        }

        switch ($item_action) {
            case 'view':
                // If we were not able to select an item, we're still going to render
                // a page state. For example, this happens when you create a new
                // portal for the first time.
                break;
            case 'info':
            case 'hide':
            case 'default':
            case 'builtin':
                if (!$selected_item) {
                    return new Aphront404Response();
                }
                break;
            case 'edit':
                if (!$request->getURIData('id')) {
                    // If we continue along the "edit" pathway without an ID, we hit an
                    // unrelated exception because we can not build a new menu item out
                    // of thin air. For menus, new items are created via the "new"
                    // action. Just catch this case and 404 early since there's currently
                    // no clean way to make EditEngine aware of this.
                    return new Aphront404Response();
                }
                break;
        }

        $navigation = $view_list->newNavigationView();
        $crumbs = $controller->buildApplicationCrumbsForEditEngine();

        if (!$is_view) {
            $edit_mode = null;

            if ($selected_item) {
                if ($selected_item->getBuiltinKey() !== self::ITEM_MANAGE) {
                    if ($selected_item->getCustomPHID()) {
                        $edit_mode = 'custom';
                    } else {
                        $edit_mode = 'global';
                    }
                }
            }

            if ($edit_mode === null) {
                $edit_mode = $request->getURIData('itemEditMode');
            }

            $available_modes = $this->getViewerEditModes();
            if ($available_modes) {
                $available_modes = array_fuse($available_modes);
                if (isset($available_modes[$edit_mode])) {
                    $this->editMode = $edit_mode;
                } else {
                    if ($item_action != 'configure') {
                        return new Aphront404Response();
                    }
                }
            }
            $page_title = Yii::t("app", 'Configure Menu');
        } else {
            if ($selected_item) {
                $page_title = $selected_item->getDisplayName();
            } else {
                $page_title = pht('Empty');
            }
        }

        switch ($item_action) {
            case 'view':
                if ($selected_item) {
                    try {
                        $content = $this->buildItemViewContent($selected_item);
                    } catch (Exception $ex) {
                        $content = (new PHUIInfoView())
                            ->setTitle(pht('Unable to Render Dashboard'))
                            ->setErrors(array($ex->getMessage()));
                    }

                    $crumbs->addTextCrumb($selected_item->getDisplayName());
                } else {
                    $content = $this->newNoContentView($this->getItems());
                }

                if (!$content) {
                    $content = $this->newEmptyView(
                        pht('Empty'),
                        pht('There is nothing here.'));
                }
                break;
            case 'configure':
                $mode = $this->getEditMode();
                if (!$mode) {
                    $crumbs->addTextCrumb(Yii::t("app", 'Configure Menu'));
                    $content = $this->buildMenuEditModeContent();
                } else {
                    if (count($available_modes) > 1) {
                        $crumbs->addTextCrumb(
                            Yii::t("app", 'Configure Menu'),
                        $this->getItemURI([
                            'itemAction' => 'configure'
                        ]));

                        switch ($mode) {
                            case self::MODE_CUSTOM:
                                $crumbs->addTextCrumb(pht('Personal'));
                                break;
                            case self::MODE_GLOBAL:
                                $crumbs->addTextCrumb(pht('Global'));
                                break;
                        }
                    } else {
                        $crumbs->addTextCrumb(Yii::t("app", 'Configure Menu'));
                    }
                    $edit_list = $this->loadItems($mode);
                    $content = $this->buildItemConfigureContent($edit_list);
                }
                break;
            case 'reorder':
                $mode = $this->getEditMode();
                $edit_list = $this->loadItems($mode);
                $content = $this->buildItemReorderContent($edit_list);
                break;
            case 'new':
                $item_key = $request->getURIData('itemKey');
                $mode = $this->getEditMode();
                $content = $this->buildItemNewContent($item_key, $mode);
                break;
            case 'builtin':
                $content = $this->buildItemBuiltinContent($selected_item);
                break;
            case 'hide':
                $content = $this->buildItemHideContent($selected_item);
                break;
            case 'default':
                if (!$this->isMenuEnginePinnable()) {
                    return new Aphront404Response();
                }
                $content = $this->buildItemDefaultContent($selected_item);
                break;
            case 'edit':
                $content = $this->buildItemEditContent();
                break;
            default:
                throw new Exception(
                    pht(
                        'Unsupported item action "%s".',
                        $item_action));
        }

        if ($content instanceof AphrontResponse) {
            return $content;
        }

        if ($content instanceof AphrontResponseProducerInterface) {
            return $content;
        }

        $crumbs->setBorder(true);

        $page = $controller->newPage()
            ->setTitle($page_title)
            ->appendChild($content);

        if (!$is_view || $this->getShowContentCrumbs()) {
            $page->setCrumbs($crumbs);
        }

        $page->setNavigation($navigation);

        if ($is_view) {
            foreach ($this->pageClasses as $class) {
                $page->addClass($class);
            }
        }

        return $page;
    }


    /**
     * @return PhabricatorProfileMenuItemConfiguration[]
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function getItems()
    {
        if ($this->items === null) {
            $this->items = $this->loadItems(self::MODE_COMBINED);
        }
        return $this->items;
    }

    /**
     * @param $mode
     * @return array
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    private function loadItems($mode)
    {
        $viewer = $this->getViewer();
        $object = $this->getProfileObject();

        $items = $this->loadBuiltinProfileItems($mode);

        $query = PhabricatorProfileMenuItemConfiguration::find()
            ->setViewer($this->getViewer())
            ->withProfilePHIDs(array($object->getPHID()));

        switch ($mode) {
            case self::MODE_GLOBAL:
                $query->withCustomPHIDs(array(), true);
                break;
            case self::MODE_CUSTOM:
                $query->withCustomPHIDs(array($this->getCustomPHID()), false);
                break;
            case self::MODE_COMBINED:
                $query->withCustomPHIDs(array($this->getCustomPHID()), true);
                break;
        }

        /** @var PhabricatorProfileMenuItemConfiguration[] $stored_items */
        $stored_items = $query->execute();
        foreach ($stored_items as $stored_item) {
            $impl = $stored_item->getMenuItem();
            $impl->setViewer($viewer);
            $impl->setEngine($this);
        }

        // Merge the stored items into the builtin items. If a builtin item has
        // a stored version, replace the defaults with the stored changes.
        foreach ($stored_items as $stored_item) {
            if (!$stored_item->shouldEnableForObject($object)) {
                continue;
            }

            $builtin_key = $stored_item->getBuiltinKey();
            if ($builtin_key !== null) {
                // If this builtin actually exists, replace the builtin with the
                // stored configuration. Otherwise, we're just going to drop the
                // stored config: it corresponds to an out-of-date or uninstalled
                // item.
                if (isset($items[$builtin_key])) {
                    $items[$builtin_key] = $stored_item;
                } else {
                    continue;
                }
            } else {
                $items[] = $stored_item;
            }
        }

        $items = $this->arrangeItems($items, $mode);

        // Make sure exactly one valid item is marked as default.
        $default = null;
        $first = null;
        foreach ($items as $item) {
            if (!$item->canMakeDefault() || $item->isDisabled()) {
                continue;
            }

            // If this engine doesn't support pinning items, don't respect any
            // setting which might be present in the database.
            if ($this->isMenuEnginePinnable()) {
                if ($item->isDefault()) {
                    $default = $item;
                    break;
                }
            }

            if ($first === null) {
                $first = $item;
            }
        }

        if (!$default) {
            $default = $first;
        }

        if ($default) {
            $this->setDefaultItem($default);
        }

        return $items;
    }

    /**
     * @param $mode
     * @return array
     * @throws PhabricatorDataNotAttachedException
     * @throws Exception
     * @author 陈妙威
     */
    private function loadBuiltinProfileItems($mode)
    {
        $object = $this->getProfileObject();
        $builtins = [];
        switch ($mode) {
            case self::MODE_GLOBAL:
                $builtins = $this->getBuiltinProfileItems($object);
                break;
            case self::MODE_CUSTOM:
                $builtins = $this->getBuiltinCustomProfileItems(
                    $object,
                    $this->getCustomPHID());
                break;
            case self::MODE_COMBINED:
                $builtins = array();
                $builtins[] = $this->getBuiltinCustomProfileItems(
                    $object,
                    $this->getCustomPHID());
                $builtins[] = $this->getBuiltinProfileItems($object);
                $builtins = array_mergev($builtins);
                break;
        }

        $items = PhabricatorProfileMenuItem::getAllMenuItems();
        $viewer = $this->getViewer();

        $order = 1;
        $map = array();
        foreach ($builtins as $builtin) {
            $builtin_key = $builtin->getBuiltinKey();

            if (!$builtin_key) {
                throw new Exception(
                    Yii::t("app",
                        'Object produced a builtin item with no builtin item key! ' .
                        'Builtin items must have a unique key.'));
            }

            if (isset($map[$builtin_key])) {
                throw new Exception(
                    Yii::t("app",
                        'Object produced two items with the same builtin key ("{0}"). ' .
                        'Each item must have a unique builtin key.', [
                            $builtin_key
                        ]));
            }

            $item_key = $builtin->getMenuItemKey();

            $item = ArrayHelper::getValue($items, $item_key);
            if (!$item) {
                throw new Exception(
                    Yii::t("app",
                        'Builtin item ("{0}") specifies a bad item key ("{1}"); there ' .
                        'is no corresponding item implementation available.',
                        [
                            $builtin_key,
                            $item_key
                        ]));
            }

            $item = clone $item;
            $item->setViewer($viewer);
            $item->setEngine($this);

            $builtin
                ->setProfilePHID($object->getPHID())
                ->attachMenuItem($item)
                ->attachProfileObject($object)
                ->setMenuItemOrder($order);

            if (!$builtin->shouldEnableForObject($object)) {
                continue;
            }

            $map[$builtin_key] = $builtin;

            $order++;
        }

        return $map;
    }

    /**
     * @param $item
     * @author 陈妙威
     * @throws Exception
     */
    private function validateNavigationMenuItem($item)
    {
        if (!($item instanceof PHUIListItemView)) {
            throw new Exception(
                Yii::t("app",
                    'Expected buildNavigationMenuItems() to return a list of ' .
                    'PHUIListItemView objects, but got a surprise.'));
        }
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getConfigureURI()
    {
        $mode = $this->getEditMode();

        switch ($mode) {
            case self::MODE_CUSTOM:
                return $this->getItemURI([
                    'itemAction' => 'configure',
                    'itemEditMode' => 'custom'
                ]);
            case self::MODE_GLOBAL:
                return $this->getItemURI([
                    'itemAction' => 'configure',
                    'itemEditMode' => 'global'
                ]);
        }

        return $this->getItemURI([
            'itemAction' => 'configure',
        ]);
    }

    /**
     * @param array $items
     * @return AphrontRedirectResponse
     * @throws Exception
     * @throws PhutilInvalidStateException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionValidationException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    private function buildItemReorderContent(array $items)
    {
        $viewer = $this->getViewer();
        $object = $this->getProfileObject();

        // If you're reordering global items, you need to be able to edit the
        // object the menu appears on. If you're reordering custom items, you only
        // need to be able to edit the custom object. Currently, the custom object
        // is always the viewing user's own user object.
        $custom_phid = $this->getEditModeCustomPHID();

        if (!$custom_phid) {
            PhabricatorPolicyFilter::requireCapability(
                $viewer,
                $object,
                PhabricatorPolicyCapability::CAN_EDIT);
        } else {
            $policy_object = (new PhabricatorObjectQuery())
                ->setViewer($viewer)
                ->withPHIDs(array($custom_phid))
                ->executeOne();

            if (!$policy_object) {
                throw new Exception(
                    Yii::t("app",
                        'Failed to load custom PHID "%s"!',
                        $custom_phid));
            }

            PhabricatorPolicyFilter::requireCapability(
                $viewer,
                $policy_object,
                PhabricatorPolicyCapability::CAN_EDIT);
        }

        $action = $this->getAction();
        $request = $action->getRequest();

        $order = $request->getStrList('order');

        $by_builtin = array();
        $by_id = array();

        foreach ($items as $key => $item) {
            $id = $item->getID();
            if ($id) {
                $by_id[$id] = $key;
                continue;
            }

            $builtin_key = $item->getBuiltinKey();
            if ($builtin_key) {
                $by_builtin[$builtin_key] = $key;
                continue;
            }
        }

        $key_order = array();
        foreach ($order as $order_item) {
            if (isset($by_id[$order_item])) {
                $key_order[] = $by_id[$order_item];
                continue;
            }
            if (isset($by_builtin[$order_item])) {
                $key_order[] = $by_builtin[$order_item];
                continue;
            }
        }

        $items = array_select_keys($items, $key_order) + $items;

        $type_order =
            PhabricatorProfileMenuItemConfigurationTransaction::TYPE_ORDER;

        $order = 1;
        foreach ($items as $item) {
            $xactions = array();

            $xactions[] = (new PhabricatorProfileMenuItemConfigurationTransaction())
                ->setTransactionType($type_order)
                ->setNewValue($order);

            (new PhabricatorProfileMenuEditor())
                ->setContentSourceFromRequest($request)
                ->setActor($viewer)
                ->setContinueOnMissingFields(true)
                ->setContinueOnNoEffect(true)
                ->applyTransactions($item, $xactions);

            $order++;
        }

        return (new AphrontRedirectResponse())->setURI($this->getConfigureURI());
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $item
     * @return mixed
     * @author 陈妙威
     * @throws PhabricatorDataNotAttachedException
     */
    protected function buildItemViewContent(PhabricatorProfileMenuItemConfiguration $item)
    {
        return $item->newPageContent();
    }

    /**
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    private function getViewerEditModes()
    {
        $modes = array();

        $viewer = $this->getViewer();

        if ($viewer->isLoggedIn() && $this->isMenuEnginePersonalizable()) {
            $modes[] = self::MODE_CUSTOM;
        }

        $object = $this->getProfileObject();
        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $object,
            PhabricatorPolicyCapability::CAN_EDIT);

        if ($can_edit) {
            $modes[] = self::MODE_GLOBAL;
        }

        return $modes;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function isMenuEnginePersonalizable()
    {
        return true;
    }

    /**
     * Does this engine support pinning items?
     *
     * Personalizable menus disable pinning by default since it creates a number
     * of weird edge cases without providing many benefits for current menus.
     *
     * @return bool True if items may be pinned as default items.
     */
    public function isMenuEnginePinnable()
    {
        return !$this->isMenuEnginePersonalizable();
    }

    /**
     * @return Aphront404Response|AphrontRedirectResponse|PHUITwoColumnView
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    private function buildMenuEditModeContent()
    {
        $viewer = $this->getViewer();

        $modes = $this->getViewerEditModes();
        if (!$modes) {
            return new Aphront404Response();
        }

        if (count($modes) == 1) {
            $mode = head($modes);
            return (new AphrontRedirectResponse())
                ->setURI($this->getItemURI([
                    'itemAction' => 'configure',
                    'itemEditMode' => $mode
                ]));
        }

        $menu = (new PHUIObjectItemListView())->setViewer($viewer);

        $modes = array_fuse($modes);

        if (isset($modes['custom'])) {
            $menu->addItem(
                (new PHUIObjectItemView())
                    ->setHeader(Yii::t("app", 'Personal Menu Items'))
                    ->setHref($this->getItemURI([
                        'itemAction' => 'configure',
                        'itemEditMode' => 'custom'
                    ]))
                    ->setImageURI($viewer->getProfileImageURI())
                    ->addAttribute(Yii::t("app", 'Edit the menu for your personal account.')));
        }

        if (isset($modes['global'])) {
            $icon = (new PHUIIconView())
                ->setIcon('fa-globe')
                ->setStyle("font-size: 40px; width: 40px; height: 40px;")
                ->addClass("text-center mr-2")
                ->setBackground('bg-blue');

            $menu->addItem(
                (new PHUIObjectItemView())
                    ->setHeader(Yii::t("app", 'Global Menu Items'))
                    ->setHref($this->getItemURI([
                        'itemAction' => 'configure',
                        'itemEditMode' => 'global'
                    ]))
                    ->setImageIcon($icon)
                    ->addAttribute(Yii::t("app", 'Edit the global default menu for all users.')));
        }

        $box = (new PHUIObjectBoxView())
            ->addBodyClass(PHUI::PADDING_NONE)
            ->setObjectList($menu);

        return (new PHUITwoColumnView())
            ->setFooter($box);
    }

    /**
     * @author 陈妙威
     */
    private function buildMenuEditModeHeader()
    {
        $header = (new PHUIPageHeaderView())
            ->setHeader(Yii::t("app", 'Manage Menu'))
            ->setHeaderIcon('fa-list');
        return $header;
    }

    /**
     * @return PHUIPageHeaderView
     * @author 陈妙威
     */
    public function buildItemConfigureHeader()
    {
        $header = (new PHUIPageHeaderView())
            ->setHeader(Yii::t("app", 'Menu Items'))
            ->setHeaderIcon('fa-list');
        return $header;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration[] $items
     * @return mixed
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws PhabricatorDataNotAttachedException
     * @throws Exception
     * @author 陈妙威
     */
    private function buildItemConfigureContent(array $items)
    {
        $viewer = $this->getViewer();
        $object = $this->getProfileObject();

        $filtered_groups = mgroup($items, 'getMenuItemKey');
        foreach ($filtered_groups as $group) {
            /** @var PhabricatorProfileMenuItemConfiguration $first_item */
            $first_item = head($group);
            $first_item->willGetMenuItemViewList($group);
        }

        // Users only need to be able to edit the object which this menu appears
        // on if they're editing global menu items. For example, users do not need
        // to be able to edit the Favorites application to add new items to the
        // Favorites menu.
        if (!$this->getCustomPHID()) {
            PhabricatorPolicyFilter::requireCapability(
                $viewer,
                $object,
                PhabricatorPolicyCapability::CAN_EDIT);
        }

        $mode = $this->getEditMode();
        $list = (new PHUIObjectItemListView())
            ->setViewer($this->getViewer())
            ->setNoDataString(Yii::t("app", 'This menu currently has no items.'));

        JavelinHtml::initBehavior(
            new JavelinReorderProfileMenuAsset(),
            array(
                'listID' => $list->getID(),
                'orderURI' => $this->getItemURI([
                    'itemAction' => 'reorder',
                    'itemEditMode' => $mode
                ]),
            ));

        foreach ($items as $item) {
            $id = $item->getID();
            $builtin_key = $item->getBuiltinKey();

            $can_edit = PhabricatorPolicyFilter::hasCapability(
                $viewer,
                $item,
                PhabricatorPolicyCapability::CAN_EDIT);

            $view = (new PHUIObjectItemView())
                ->addClass("cursor-move");


            $name = $item->getDisplayName();
            $type = $item->getMenuItemTypeName();
            if (!strlen(trim($name))) {
                $name = Yii::t("app", 'Untitled "%s" Item', $type);
            }

            $view->setHeader($name);
            $view->addAttribute($type);

            if ($can_edit) {
                $view
                    ->setGrippable(true)
                    ->addSigil('profile-menu-item')
                    ->setMetadata(
                        array(
                            'key' => nonempty($id, $builtin_key),
                        ));

                if ($id) {
                    $default_uri = $this->getItemURI([
                        'itemAction' => 'default',
                        'id' => $id
                    ]);
                } else {
                    $default_uri = $this->getItemURI([
                        'itemAction' => 'default',
                        'itemID' => $builtin_key
                    ]);
                }

                $default_text = null;
                $default_icon = null;

                if ($this->isMenuEnginePinnable()) {
                    if ($item->isDefault()) {
                        $default_icon = 'fa-thumb-tack green';
                        $default_text = Yii::t("app", 'Current Default');
                    } else if ($item->canMakeDefault()) {
                        $default_icon = 'fa-thumb-tack';
                        $default_text = Yii::t("app", 'Make Default');
                    }
                }

                if ($default_text !== null) {
                    $view->addAction(
                        (new PHUIListItemView())
                            ->setHref($default_uri)
                            ->setWorkflow(true)
                            ->setName($default_text)
                            ->setIcon($default_icon));
                }

                if ($id) {
                    $view->setHref($this->getItemURI([
                        'itemAction' => 'edit',
                        'id' => $id
                    ]));
                    $hide_uri = $this->getItemURI([
                        'itemAction' => 'hide',
                        'id' => $id
                    ]);
                } else {
                    $view->setHref($this->getItemURI([
                        'itemAction' => 'builtin',
                        'itemID' => $builtin_key
                    ]));
                    $hide_uri = $this->getItemURI([
                        'itemAction' => 'hide',
                        'itemID' => $builtin_key
                    ]);
                }

                if ($item->isDisabled()) {
                    $hide_icon = 'fa-plus';
                    $hide_text = Yii::t("app", 'Enable');
                } else if ($item->getBuiltinKey() !== null) {
                    $hide_icon = 'fa-times';
                    $hide_text = Yii::t("app", 'Disable');
                } else {
                    $hide_icon = 'fa-times';
                    $hide_text = Yii::t("app", 'Delete');
                }

                $can_disable = $item->canHideMenuItem();

                $view->addAction((new PHUIListItemView())
                    ->setHref($hide_uri)
                    ->setWorkflow(true)
                    ->setDisabled(!$can_disable)
                    ->setName($hide_text)
                    ->setIcon($hide_icon));
            }

            if ($item->isDisabled()) {
                $view->setDisabled(true);
            }

            $list->addItem($view);
        }

        $action_view = (new PhabricatorActionListView())
            ->setUser($viewer);

        $item_types = PhabricatorProfileMenuItem::getAllMenuItems();
        $object = $this->getProfileObject();

        $action_list = (new PhabricatorActionListView())
            ->setViewer($viewer);

        $action_list->addAction(
            (new PhabricatorActionView())
                ->setLabel(true)
                ->addClass("pt-3 pl-3")
                ->setName(Yii::t("app", 'Add New Menu Item...')));

        foreach ($item_types as $item_type) {
            if (!$item_type->canAddToObject($object)) {
                continue;
            }

            $item_key = $item_type->getMenuItemKey();
            $edit_mode = $this->getEditMode();

            $action_list->addAction(
                (new PhabricatorActionView())
                    ->setIcon($item_type->getMenuItemTypeIcon())
                    ->setName($item_type->getMenuItemTypeName())
                    ->setHref($this->getItemURI([
                        'itemAction' => "new",
                        "itemEditMode" => $edit_mode,
                        "itemKey" => $item_key
                    ]))
                    ->setWorkflow(true));
        }

//        $action_list->addAction(
//            (new PhabricatorActionView())
//                ->setLabel(true)
//                ->addClass("pt-3 pl-3")
//                ->setName(\Yii::t("app", 'Documentation')));
//
//        $doc_link = PhabricatorEnv::getDoclink('Profile Menu User Guide');
//        $doc_name = \Yii::t("app", 'Profile Menu User Guide');
//
//        $action_list->addAction(
//            (new PhabricatorActionView())
//                ->setIcon('fa-book')
//                ->setHref($doc_link)
//                ->setName($doc_name));


        $box = (new PHUIObjectBoxView())
            ->setHeaderText(Yii::t("app", 'Current Menu Items'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->addBodyClass(PHUI::PADDING_NONE)
            ->setObjectList($list);

        $panel = (new PHUICurtainPanelView())
            ->appendChild($action_view);

        $curtain = (new PHUICurtainView())
            ->setViewer($viewer)
            ->setActionList($action_list);

        $view = (new PHUITwoColumnView())
            ->setCurtain($curtain)
            ->setMainColumn(
                array(
                    $box,
                ));
        return $view;
    }

    /**
     * @param $item_key
     * @param $mode
     * @return Aphront404Response
     * @throws AphrontDuplicateKeyQueryException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    private function buildItemNewContent($item_key, $mode)
    {
        $item_types = PhabricatorProfileMenuItem::getAllMenuItems();
        $item_type = ArrayHelper::getValue($item_types, $item_key);
        if (!$item_type) {
            return new Aphront404Response();
        }

        $object = $this->getProfileObject();
        if (!$item_type->canAddToObject($object)) {
            return new Aphront404Response();
        }

        $custom_phid = $this->getEditModeCustomPHID();

        $configuration = PhabricatorProfileMenuItemConfiguration::initializeNewItem(
            $object,
            $item_type,
            $custom_phid);

        $viewer = $this->getViewer();

        PhabricatorPolicyFilter::requireCapability($viewer, $configuration, PhabricatorPolicyCapability::CAN_EDIT);

        return (new PhabricatorProfileMenuEditEngine())
            ->setMenuEngine($this)
            ->setProfileObject($object)
            ->setNewMenuItemConfiguration($configuration)
            ->setCustomPHID($custom_phid)
            ->setAction($this->getAction())
            ->buildResponse();
    }

    /**
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    private function buildItemEditContent()
    {
        $object = $this->getProfileObject();
        $custom_phid = $this->getEditModeCustomPHID();

        return (new PhabricatorProfileMenuEditEngine())
            ->setMenuEngine($this)
            ->setProfileObject($object)
            ->setAction($this->getAction())
            ->setCustomPHID($custom_phid)
            ->buildResponse();
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $configuration
     * @return mixed
     * @throws AphrontDuplicateKeyQueryException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    private function buildItemBuiltinContent(
        PhabricatorProfileMenuItemConfiguration $configuration)
    {

        // If this builtin item has already been persisted, redirect to the
        // edit page.
        $id = $configuration->getID();
        if ($id) {
            return (new AphrontRedirectResponse())
                ->setURI($this->getItemURI([
                    'itemAction' => 'edit',
                    'id' => $id
                ]));
        }

        // Otherwise, act like we're creating a new item, we're just starting
        // with the builtin template.
        $viewer = $this->getViewer();

        PhabricatorPolicyFilter::requireCapability(
            $viewer,
            $configuration,
            PhabricatorPolicyCapability::CAN_EDIT);

        $object = $this->getProfileObject();
        $custom_phid = $this->getEditModeCustomPHID();

        return (new PhabricatorProfileMenuEditEngine())
            ->setIsBuiltin(true)
            ->setMenuEngine($this)
            ->setProfileObject($object)
            ->setNewMenuItemConfiguration($configuration)
            ->setAction($this->getAction())
            ->setCustomPHID($custom_phid)
            ->buildResponse();
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $configuration
     * @return mixed
     * @throws InvalidConfigException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws Throwable
     * @throws PhabricatorDataNotAttachedException
     * @throws PhabricatorApplicationTransactionValidationException
     * @throws StaleObjectException
     * @author 陈妙威
     */
    private function buildItemHideContent(PhabricatorProfileMenuItemConfiguration $configuration)
    {

        $action = $this->getAction();
        $request = $action->getRequest();
        $viewer = $this->getViewer();

        PhabricatorPolicyFilter::requireCapability(
            $viewer,
            $configuration,
            PhabricatorPolicyCapability::CAN_EDIT);

        if (!$configuration->canHideMenuItem()) {
            return $action->newDialog()
                ->setTitle(Yii::t("app", 'Mandatory Item'))
                ->appendParagraph(
                    Yii::t("app", 'This menu item is very important, and can not be disabled.'))
                ->addCancelButton($this->getConfigureURI());
        }

        if ($configuration->getBuiltinKey() === null) {
            $new_value = null;

            $title = Yii::t("app", 'Delete Menu Item');
            $body = Yii::t("app", 'Delete this menu item?');
            $button = Yii::t("app", 'Delete Menu Item');
        } else if ($configuration->isDisabled()) {
            $new_value = PhabricatorProfileMenuItemConfiguration::VISIBILITY_VISIBLE;

            $title = Yii::t("app", 'Enable Menu Item');
            $body = Yii::t("app",
                'Enable this menu item? It will appear in the menu again.');
            $button = Yii::t("app", 'Enable Menu Item');
        } else {
            $new_value = PhabricatorProfileMenuItemConfiguration::VISIBILITY_DISABLED;

            $title = Yii::t("app", 'Disable Menu Item');
            $body = Yii::t("app",
                'Disable this menu item? It will no longer appear in the menu, but ' .
                'you can re-enable it later.');
            $button = Yii::t("app", 'Disable Menu Item');
        }

        $v_visibility = $configuration->getVisibility();
        if ($request->isFormPost()) {
            if ($new_value === null) {
                $configuration->delete();
            } else {
                $type_visibility =
                    PhabricatorProfileMenuItemConfigurationTransaction::TYPE_VISIBILITY;

                $xactions = array();

                $xactions[] = (new PhabricatorProfileMenuItemConfigurationTransaction())
                    ->setTransactionType($type_visibility)
                    ->setNewValue($new_value);

                (new PhabricatorProfileMenuEditor())
                    ->setContentSourceFromRequest($request)
                    ->setActor($viewer)
                    ->setContinueOnMissingFields(true)
                    ->setContinueOnNoEffect(true)
                    ->applyTransactions($configuration, $xactions);
            }

            return (new AphrontRedirectResponse())
                ->setURI($this->getConfigureURI());
        }

        return $action->newDialog()
            ->addClass('wmin-600')
            ->setTitle($title)
            ->appendParagraph($body)
            ->addCancelButton($this->getConfigureURI())
            ->addSubmitButton($button);
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration $configuration
     * @param array $items
     * @return AphrontRedirectResponse|AphrontDialogView
     * @throws PhutilInvalidStateException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorDataNotAttachedException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionValidationException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    private function buildItemDefaultContent(
        PhabricatorProfileMenuItemConfiguration $configuration,
        array $items)
    {

        $action = $this->getAction();
        $request = $action->getRequest();
        $viewer = $this->getViewer();

        PhabricatorPolicyFilter::requireCapability(
            $viewer,
            $configuration,
            PhabricatorPolicyCapability::CAN_EDIT);

        $done_uri = $this->getConfigureURI();

        if (!$configuration->canMakeDefault()) {
            return $action->newDialog()
                ->setTitle(Yii::t("app", 'Not Defaultable'))
                ->appendParagraph(
                    Yii::t("app",
                        'This item can not be set as the default item. This is usually ' .
                        'because the item has no page of its own, or links to an ' .
                        'external page.'))
                ->addCancelButton($done_uri);
        }

        if ($configuration->isDefault()) {
            return $action->newDialog()
                ->setTitle(Yii::t("app", 'Already Default'))
                ->appendParagraph(
                    Yii::t("app",
                        'This item is already set as the default item for this menu.'))
                ->addCancelButton($done_uri);
        }

        if ($request->isFormPost()) {
            $key = $configuration->getID();
            if (!$key) {
                $key = $configuration->getBuiltinKey();
            }

            $this->adjustDefault($key);

            return (new AphrontRedirectResponse())
                ->setURI($done_uri);
        }

        return $action->newDialog()
            ->setTitle(Yii::t("app", 'Make Default'))
            ->appendParagraph(
                Yii::t("app",
                    'Set this item as the default for this menu? Users arriving on ' .
                    'this page will be shown the content of this item by default.'))
            ->addCancelButton($done_uri)
            ->addSubmitButton(Yii::t("app", 'Make Default'));
    }

    /**
     * @return PhabricatorProfileMenuItemConfiguration
     * @author 陈妙威
     */
    protected function newItem()
    {
        return PhabricatorProfileMenuItemConfiguration::initializeNewBuiltin();
    }

    /**
     * @return PhabricatorProfileMenuItemConfiguration
     * @author 陈妙威
     */
    protected function newManageItem()
    {
        return $this->newItem()
            ->setBuiltinKey(self::ITEM_MANAGE)
            ->setMenuItemKey(PhabricatorManageProfileMenuItem::MENUITEMKEY);
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     */
    protected function newDividerItem($key)
    {
        return $this->newItem()
            ->setBuiltinKey($key)
            ->setMenuItemKey(PhabricatorDividerProfileMenuItem::MENUITEMKEY)
            ->setIsTailItem(true);
    }

    /**
     * @param $key
     * @return $this
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionValidationException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws InvalidConfigException
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function adjustDefault($key)
    {
        $action = $this->getAction();
        $request = $action->getRequest();
        $viewer = $request->getViewer();

        $items = $this->loadItems(self::MODE_COMBINED);

        // To adjust the default item, we first change any existing items that
        // are marked as defaults to "visible", then make the new default item
        // the default.

        $default = array();
        $visible = array();

        foreach ($items as $item) {
            $builtin_key = $item->getBuiltinKey();
            $id = $item->getID();

            $is_target =
                (($builtin_key !== null) && ($builtin_key === $key)) ||
                (($id !== null) && ((int)$id === (int)$key));

            if ($is_target) {
                if (!$item->isDefault()) {
                    $default[] = $item;
                }
            } else {
                if ($item->isDefault()) {
                    $visible[] = $item;
                }
            }
        }

        $type_visibility =
            PhabricatorProfileMenuItemConfigurationTransaction::TYPE_VISIBILITY;

        $v_visible = PhabricatorProfileMenuItemConfiguration::VISIBILITY_VISIBLE;
        $v_default = PhabricatorProfileMenuItemConfiguration::VISIBILITY_DEFAULT;

        $apply = array(
            array($v_visible, $visible),
            array($v_default, $default),
        );

        foreach ($apply as $group) {
            list($value, $items) = $group;
            foreach ($items as $item) {
                $xactions = array();

                $xactions[] =
                    (new PhabricatorProfileMenuItemConfigurationTransaction())
                        ->setTransactionType($type_visibility)
                        ->setNewValue($value);

                (new PhabricatorProfileMenuEditor())
                    ->setContentSourceFromRequest($request)
                    ->setActor($viewer)
                    ->setContinueOnMissingFields(true)
                    ->setContinueOnNoEffect(true)
                    ->applyTransactions($item, $xactions);
            }
        }

        return $this;
    }

    /**
     * @param PhabricatorProfileMenuItemConfiguration[] $items
     * @param $mode
     * @return array
     * @author 陈妙威
     * @throws Exception
     */
    private function arrangeItems(array $items, $mode)
    {
        // Sort the items.
        /** @var PhabricatorProfileMenuItemConfiguration[] $items */
        $items = msortv($items, 'getSortVector');

        $object = $this->getProfileObject();

        // If we have some global items and some custom items and are in "combined"
        // mode, put a hard-coded divider item between them.
        if ($mode == self::MODE_COMBINED) {
            $list = array();
            $seen_custom = false;
            $seen_global = false;
            foreach ($items as $item) {
                if ($item->getCustomPHID()) {
                    $seen_custom = true;
                } else {
                    if ($seen_custom && !$seen_global) {
                        $list[] = $this->newItem()
                            ->setBuiltinKey(self::ITEM_CUSTOM_DIVIDER)
                            ->setMenuItemKey(PhabricatorDividerProfileMenuItem::MENUITEMKEY)
                            ->attachProfileObject($object)
                            ->attachMenuItem(
                                new PhabricatorDividerProfileMenuItem());
                    }
                    $seen_global = true;
                }
                $list[] = $item;
            }
            $items = $list;
        }

        // Normalize keys since callers shouldn't rely on this array being
        // partially keyed.
        $items = array_values($items);

        return $items;
    }


    /**
     * @param $title
     * @param $message
     * @return PHUIInfoView
     * @author 陈妙威
     */
    final protected function newEmptyView($title, $message)
    {
        return (new PHUIInfoView())
            ->setTitle($title)
            ->setSeverity(PHUIInfoView::SEVERITY_NODATA)
            ->setErrors(
                array(
                    $message,
                ));
    }

    /**
     * @param array $items
     * @return PHUIInfoView
     * @author 陈妙威
     */
    protected function newNoContentView(array $items)
    {
        return $this->newEmptyView(
            pht('No Content'),
            pht('No visible menu items can render content.'));
    }


    /**
     * @return PhabricatorProfileMenuItemViewList
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    final public function newProfileMenuItemViewList()
    {
        $items = $this->getItems();

        // Throw away disabled items: they are not allowed to build any views for
        // the menu.
        foreach ($items as $key => $item) {
            if ($item->isDisabled()) {
                unset($items[$key]);
                continue;
            }
        }

        // Give each item group a callback so it can load data it needs to render
        // views.
        $groups = mgroup($items, 'getMenuItemKey');
        foreach ($groups as $group) {
            /** @var PhabricatorProfileMenuItem $item */
            $item = head($group);
            $item->willGetMenuItemViewList($group);
        }

        $view_list = (new PhabricatorProfileMenuItemViewList())
            ->setProfileMenuEngine($this);

        foreach ($items as $item) {
            $views = $item->getMenuItemViewList();
            foreach ($views as $view) {
                $view_list->addItemView($view);
            }
        }

        return $view_list;
    }

    /**
     * @param PhabricatorProfileMenuItemViewList $view_list
     * @param $item_id
     * @return null
     * @throws Exception
     * @author 陈妙威
     */
    private function selectViewItem(
        PhabricatorProfileMenuItemViewList $view_list,
        $item_id)
    {

        // Figure out which view's content we're going to render. In most cases,
        // the URI tells us. If we don't have an identifier in the URI, we'll
        // render the default view instead.

        /** @var PhabricatorProfileMenuItemView $selected_view */
        $selected_view = null;
        if (strlen($item_id)) {
            $item_views = $view_list->getViewsWithItemIdentifier($item_id);
            if ($item_views) {
                $selected_view = head($item_views);
            }
        } else {
            $default_views = $view_list->getDefaultViews();
            if ($default_views) {
                $selected_view = head($default_views);
            }
        }

        if ($selected_view) {
            $view_list->setSelectedView($selected_view);
            $selected_item = $selected_view->getMenuItemConfiguration();
        } else {
            $selected_item = null;
        }

        return $selected_item;
    }

    /**
     * @param PhabricatorProfileMenuItemViewList $view_list
     * @param $item_id
     * @return null|PhabricatorProfileMenuItemConfiguration
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    private function selectEditItem(
        PhabricatorProfileMenuItemViewList $view_list,
        $item_id)
    {

        // First, try to select a visible item using the normal view selection
        // pathway. If this works, it also highlights the menu properly.

        if ($item_id) {
            $selected_item = $this->selectViewItem($view_list, $item_id);
            if ($selected_item) {
                return $selected_item;
            }
        }

        // If we didn't find an item in the view list, we may be enabling an item
        // which is currently disabled or editing an item which is not generating
        // any actual items in the menu.

        foreach ($this->getItems() as $item) {
            if ($item->matchesIdentifier($item_id)) {
                return $item;
            }
        }

        return null;
    }

}

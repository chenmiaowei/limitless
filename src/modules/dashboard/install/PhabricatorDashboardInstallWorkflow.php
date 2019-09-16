<?php

namespace orangins\modules\dashboard\install;

use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\dashboard\models\PhabricatorDashboard;
use orangins\modules\home\engine\PhabricatorHomeProfileMenuEngine;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\search\editors\PhabricatorProfileMenuEditor;
use orangins\modules\search\menuitems\PhabricatorDashboardProfileMenuItem;
use orangins\modules\search\models\PhabricatorProfileMenuItemConfiguration;
use PhutilClassMapQuery;
use yii\helpers\Url;

/**
 * Class PhabricatorDashboardInstallWorkflow
 * @package orangins\modules\dashboard\install
 * @author 陈妙威
 */
abstract class PhabricatorDashboardInstallWorkflow extends OranginsObject
{

    /**
     * @var
     */
    private $request;
    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $dashboard;
    /**
     * @var
     */
    private $mode;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorDashboard $dashboard
     * @return $this
     * @author 陈妙威
     */
    final public function setDashboard(PhabricatorDashboard $dashboard)
    {
        $this->dashboard = $dashboard;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getDashboard()
    {
        return $this->dashboard;
    }

    /**
     * @param $mode
     * @return $this
     * @author 陈妙威
     */
    final public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param AphrontRequest $request
     * @return $this
     * @author 陈妙威
     */
    final public function setRequest(AphrontRequest $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    final public function getWorkflowKey()
    {
        return $this->getPhobjectClassConstant('WORKFLOWKEY', 32);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public static function getAllWorkflows()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getWorkflowKey')
            ->setSortMethod('getOrder')
            ->execute();
    }

    /**
     * @return PHUIObjectItemView
     * @author 陈妙威
     */
    final public function getWorkflowMenuItem()
    {
        return $this->newWorkflowMenuItem();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getOrder();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newWorkflowMenuItem();

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function newMenuItem()
    {
        return (new PHUIObjectItemView())
            ->setClickable(true);
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @author 陈妙威
     */
    abstract public function handleRequest(AphrontRequest $request);

    /**
     * @return AphrontDialogView
     * @author 陈妙威
     */
    final protected function newDialog()
    {
        $dashboard = $this->getDashboard();

        return (new AphrontDialogView())
            ->setViewer($this->getViewer())
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->addCancelButton($dashboard->getURI());
    }

    /**
     * @param array $map
     * @return mixed
     * @author 陈妙威
     * @throws \Exception
     */
    final protected function newMenuFromItemMap(array $map)
    {
        $viewer = $this->getViewer();
        $dashboard = $this->getDashboard();

        $menu = (new PHUIObjectItemListView())
            ->setViewer($viewer)
            ->setFlush(true)
            ->setBig(true);

        foreach ($map as $key => $item) {
            $item->setHref(Url::to([
                '/dashboard/index/install',
                'id' => $dashboard->getID(),
                'workflowKey' => $this->getWorkflowKey(),
                'modeKey' => $key
            ]));
            $menu->addItem($item);
        }
        return $menu;
    }

    /**
     * @return PhabricatorHomeProfileMenuEngine
     * @author 陈妙威
     */
    abstract protected function newProfileEngine();

    /**
     * @param $profile_object
     * @param $custom_phid
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    final protected function installDashboard($profile_object, $custom_phid)
    {
        $dashboard = $this->getDashboard();
        $engine = $this->newProfileEngine()
            ->setProfileObject($profile_object);

        $request = $this->getRequest();
        $viewer = $this->getViewer();

        $config = PhabricatorProfileMenuItemConfiguration::initializeNewItem(
            $profile_object,
            new PhabricatorDashboardProfileMenuItem(),
            $custom_phid);

        $config->setMenuItemProperty('dashboardPHID', $dashboard->getPHID());

        $xactions = array();

        $editor = (new PhabricatorProfileMenuEditor())
            ->setActor($viewer)
            ->setContinueOnNoEffect(true)
            ->setContinueOnMissingFields(true)
            ->setContentSourceFromRequest($request);

        $editor->applyTransactions($config, $xactions);


        $done_uri = $engine->getItemURI([
            'id' => $config->getID()
        ]);

        return (new AphrontRedirectResponse())
            ->setURI($done_uri);
    }

    /**
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    final protected function getDashboardDisplayName()
    {
        $dashboard = $this->getDashboard();
        return phutil_tag('strong', array(), $dashboard->getName());
    }

}

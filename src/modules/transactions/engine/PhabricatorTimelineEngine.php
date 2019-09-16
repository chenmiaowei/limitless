<?php

namespace orangins\modules\transactions\engine;

use Exception;
use orangins\lib\OranginsObject;
use orangins\lib\request\AphrontRequest;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\interfaces\PhabricatorTimelineInterface;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionView;

/**
 * Class PhabricatorTimelineEngine
 * @package orangins\modules\transactions\edittype
 * @author 陈妙威
 */
abstract class PhabricatorTimelineEngine
    extends OranginsObject
{
    /**
     * @var AphrontRequest
     */
    public $request;

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $object;
    /**
     * @var
     */
    private $xactions;
    /**
     * @var
     */
    private $viewData;

    /**
     * @param $object
     * @return PhabricatorStandardTimelineEngine
     * @author 陈妙威
     */
    final public static function newForObject($object)
    {
        if ($object instanceof PhabricatorTimelineInterface) {
            $engine = $object->newTimelineEngine();
        } else {
            $engine = new PhabricatorStandardTimelineEngine();
        }

        $engine->setObject($object);

        return $engine;
    }

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
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    final public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getObject()
    {
        return $this->object;
    }

    /**
     * @param array $xactions
     * @return $this
     * @author 陈妙威
     */
    final public function setTransactions(array $xactions)
    {
        assert_instances_of($xactions, PhabricatorApplicationTransaction::className());
        $this->xactions = $xactions;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getTransactions()
    {
        return $this->xactions;
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
     * @param array $view_data
     * @return $this
     * @author 陈妙威
     */
    final public function setViewData(array $view_data)
    {
        $this->viewData = $view_data;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewData()
    {
        return $this->viewData;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    final public function buildTimelineView()
    {
        $view = $this->newTimelineView();

        if (!($view instanceof PhabricatorApplicationTransactionView)) {
            throw new Exception(
                \Yii::t("app",
                    'Expected "newTimelineView()" to return an object of class "%s" ' .
                    '(in engine "%s").',
                    'PhabricatorApplicationTransactionView',
                    get_class($this)));
        }

        $viewer = $this->getViewer();
        $object = $this->getObject();
        $xactions = $this->getTransactions();

        return $view
            ->setViewer($viewer)
            ->setObjectPHID($object->getPHID())
            ->setTransactions($xactions);
    }

    /**
     * @return PhabricatorApplicationTransactionView
     * @author 陈妙威
     */
    protected function newTimelineView()
    {
        return new PhabricatorApplicationTransactionView();
    }

}

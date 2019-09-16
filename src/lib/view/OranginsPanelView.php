<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/3
 * Time: 1:20 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\view;

use orangins\lib\controllers\PhabricatorController;

use orangins\lib\response\AphrontResponse;
use orangins\modules\people\models\PhabricatorUser;
use orangins\lib\view\AphrontView;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\web\Request;

/**
 * Class OranginsPanel
 * @package orangins\lib\view
 * @author 陈妙威
 */
class OranginsPanelView extends AphrontView
{


    /**
     * @var string[]|AphrontResponse[]
     */
    public $boxes = [];

    /**
     * @var Request
     */
    public $request;

    /**
     * @var PhabricatorController
     */
    public $controller;

    /**
     * @var PhabricatorUser
     */
    public $viewer;

    /**
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    public function init()
    {
        parent::init();
    }


    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     * @return static
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return PhabricatorController
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param PhabricatorController $controller
     * @return static
     */
    public function setController($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return string[]|AphrontResponse[]
     */
    public function getBoxes()
    {
        return $this->boxes;
    }

    /**
     * @param string[]|AphrontResponse[] $boxes
     * @return static
     */
    public function setBoxes($boxes)
    {
        $this->boxes = $boxes;
        return $this;
    }

    /**
     * @param string|AphrontResponse $box
     * @return $this
     * @author 陈妙威
     */
    public function addBox($box)
    {
        $this->boxes[] = $box;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     * @throws \Exception
     */
    public function render()
    {
        if (empty($this->getRequest())) {
            throw new InvalidConfigException(\Yii::t("app", 'The "request" property of {0} must be configured as {1}.', [
                get_called_class(),
                Request::class
            ]));
        }
        if (empty($this->getViewer())) {
            throw new InvalidConfigException(\Yii::t("app", 'The "viewer" property of {0} must be configured as {1}.', [
                get_called_class(),
                PhabricatorUser::class
            ]));
        }
        return Html::tag("div", Html::tag("div", implode("\n", $this->getBoxes()), ['class' => 'col-lg-12']), ['class' => 'row']);
    }
}
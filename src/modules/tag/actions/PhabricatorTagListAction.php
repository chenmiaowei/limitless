<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/1
 * Time: 4:49 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\tag\actions;


use orangins\lib\actions\PhabricatorAction;
use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\home\application\PhabricatorHomeApplication;
use orangins\modules\home\engine\PhabricatorHomeProfileMenuEngine;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\search\actions\PhabricatorApplicationSearchAction;
use orangins\modules\tag\query\PhabricatorTagSearchEngine;

/**
 * Class PhabricatorTagListAction
 * @package orangins\modules\tag\actions
 * @author 陈妙威
 */
class PhabricatorTagListAction extends PhabricatorAction
{
    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $viewer = $this->getViewer();
        $request = $this->getRequest();
        $querykey = $request->getURIData('queryKey');

        $action = (new PhabricatorApplicationSearchAction('search', $this->controller))
            ->setQueryKey($querykey)
            ->setSearchEngine((new PhabricatorTagSearchEngine()));
        ;

        $delegateToAction = $this->delegateToAction($action);
        return $delegateToAction;
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();
        $crumbs->addAction(
            (new PHUIListItemView())
                ->setName(\Yii::t("app", 'Create {0}', [\Yii::t("app", 'Tag')]))
                ->setIcon('fa-plus')
                ->setHref($this->getApplicationURI('index/create')));

        return $crumbs;
    }
}

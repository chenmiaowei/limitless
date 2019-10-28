<?php

namespace orangins\modules\herald\actions;

use orangins\lib\view\phui\PHUICrumbsView;
use orangins\modules\herald\editors\HeraldWebhookEditEngine;
use orangins\modules\herald\query\HeraldWebhookSearchEngine;
use PhutilInvalidStateException;
use PhutilMethodNotImplementedException;
use ReflectionException;
use yii\base\InvalidConfigException;

/**
 * Class HeraldWebhookListController
 * @package orangins\modules\herald\actions
 * @author 陈妙威
 */
final class HeraldWebhookListController
    extends HeraldWebhookController
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return mixed
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        return (new HeraldWebhookSearchEngine())
            ->setAction($this)
            ->buildResponse();
    }

    /**
     * @return PHUICrumbsView
     * @throws PhutilInvalidStateException
     * @throws PhutilMethodNotImplementedException
     * @throws ReflectionException
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        (new HeraldWebhookEditEngine())
            ->setViewer($this->getViewer())
            ->addActionToCrumbs($crumbs);

        return $crumbs;
    }

}

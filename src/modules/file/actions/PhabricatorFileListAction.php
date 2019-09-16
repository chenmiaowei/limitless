<?php

namespace orangins\modules\file\actions;

use orangins\lib\view\phui\PHUIListItemView;
use orangins\modules\file\query\PhabricatorFileSearchEngine;

/**
 * Class PhabricatorFileListAction
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileListAction extends PhabricatorFileAction
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
     * @return bool
     * @author 陈妙威
     */
    public function isGlobalDragAndDropUploadEnabled()
    {
        return true;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $request = $this->getRequest();
        return (new PhabricatorFileSearchEngine())
            ->setAction($this)
            ->buildResponse();
    }

    /**
     * @return \orangins\lib\view\phui\PHUICrumbsView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function buildApplicationCrumbs()
    {
        $crumbs = parent::buildApplicationCrumbs();

        $crumbs->addAction(
            (new PHUIListItemView())
                ->setName(\Yii::t("app",'Upload File'))
                ->setIcon('fa-upload')
                ->setHref($this->getApplicationURI('index/upload')));

        return $crumbs;
    }
}

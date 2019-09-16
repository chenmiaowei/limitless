<?php

namespace orangins\modules\feed\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontPlainTextResponse;
use orangins\modules\feed\builder\PhabricatorFeedBuilder;
use orangins\modules\feed\models\PhabricatorFeedStoryData;

/**
 * Class PhabricatorFeedDetailController
 * @package orangins\modules\feed\actions
 * @author 陈妙威
 */
final class PhabricatorFeedDetailController extends PhabricatorFeedController
{

    /**
     * @return Aphront404Response|AphrontPlainTextResponse|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        $story = PhabricatorFeedStoryData::find()
            ->setViewer($viewer)
            ->withChronologicalKeys(array($id))
            ->executeOne();
        if (!$story) {
            return new Aphront404Response();
        }

        if ($request->getStr('text')) {
            $text = $story->renderText();
            return (new AphrontPlainTextResponse())->setContent($text);
        }

        $feed = array($story);
        $builder = new PhabricatorFeedBuilder($feed);
        $builder->setUser($viewer);
        $feed_view = $builder->buildView();

        $title = \Yii::t("app",'Story');

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($title);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($feed_view);
    }

}

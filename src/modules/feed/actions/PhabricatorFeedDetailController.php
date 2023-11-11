<?php

namespace orangins\modules\feed\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontPlainTextResponse;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\modules\feed\builder\PhabricatorFeedBuilder;
use orangins\modules\feed\models\PhabricatorFeedStoryData;
use PhutilInvalidStateException;
use PhutilMethodNotImplementedException;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Class PhabricatorFeedDetailController
 * @package orangins\modules\feed\actions
 * @author 陈妙威
 */
final class PhabricatorFeedDetailController extends PhabricatorFeedController
{

    /**
     * @return Aphront404Response|AphrontPlainTextResponse|PhabricatorStandardPageView
     * @throws PhutilInvalidStateException
     * @throws PhutilMethodNotImplementedException
     * @throws InvalidConfigException
     * @throws \Exception
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


        $PHUIObjectBoxView = new PHUIObjectBoxView();
        $PHUIObjectBoxView->appendChild($feed_view);

        $title = Yii::t("app",'Story');

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($title);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($PHUIObjectBoxView);
    }

}

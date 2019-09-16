<?php

namespace orangins\modules\notification\actions;

use orangins\lib\response\AphrontAjaxResponse;
use orangins\modules\notification\builder\PhabricatorNotificationBuilder;
use orangins\modules\notification\model\PhabricatorFeedStoryNotification;

/**
 * Class PhabricatorNotificationIndividualAction
 * @package orangins\modules\notification\actions
 * @author 陈妙威
 */
final class PhabricatorNotificationIndividualAction
    extends PhabricatorNotificationController
{

    /**
     * @return AphrontAjaxResponse
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $stories = PhabricatorFeedStoryNotification::find()
            ->setViewer($viewer)
            ->withUserPHIDs(array($viewer->getPHID()))
            ->withKeys(array($request->getStr('key')))
            ->execute();

        if (!$stories) {
            return $this->buildEmptyResponse();
        }

        $story = head($stories);
        if ($story->getAuthorPHID() === $viewer->getPHID()) {
            // Don't show the user individual notifications about their own
            // actions. Primarily, this stops pages from showing notifications
            // immediately after you click "Submit" on a comment form if the
            // notification server returns faster than the web server.

            // TODO: It would be nice to retain the "page updated" bubble on copies
            // of the page that are open in other tabs, but there isn't an obvious
            // way to do this easily.

            return $this->buildEmptyResponse();
        }

        $builder = (new PhabricatorNotificationBuilder(array($story)))
            ->setUser($viewer)
            ->setShowTimestamps(false);

        $content = $builder->buildView()->render();
        $dict = $builder->buildDict();
        $data = $dict[0];

        $response = $data + array(
                'pertinent' => true,
                'primaryObjectPHID' => $story->getPrimaryObjectPHID(),
                'content' => hsprintf('%s', $content),
                'uniqueID' => 'story/' . $story->getChronologicalKey(),
            );

        return (new AphrontAjaxResponse())->setContent($response);
    }

    /**
     * @return AphrontAjaxResponse
     * @author 陈妙威
     */
    private function buildEmptyResponse()
    {
        return (new AphrontAjaxResponse())->setContent(
            array(
                'pertinent' => false,
            ));
    }

}

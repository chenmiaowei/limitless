<?php

namespace orangins\modules\feed\worker;

use HTTPSFuture;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException;
use orangins\lib\infrastructure\daemon\workers\storage\PhabricatorWorkerTask;

/**
 * Class FeedPublisherHTTPWorker
 * @package orangins\modules\feed\worker
 * @author 陈妙威
 */
final class FeedPublisherHTTPWorker extends FeedPushWorker
{

    /**
     * @throws PhabricatorWorkerPermanentFailureException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function doWork()
    {
        if (PhabricatorEnv::getEnvConfig('phabricator.silent')) {
            // Don't invoke hooks in silent mode.
            return;
        }

        $story = $this->loadFeedStory();
        $data = $story->getStoryData();

        $uri = idx($this->getTaskData(), 'uri');
        $valid_uris = PhabricatorEnv::getEnvConfig('feed.http-hooks');
        if (!in_array($uri, $valid_uris)) {
            throw new PhabricatorWorkerPermanentFailureException();
        }

        $post_data = array(
            'storyID' => $data->getID(),
            'storyType' => $data->getStoryType(),
            'storyData' => $data->getStoryData(),
            'storyAuthorPHID' => $data->getAuthorPHID(),
            'storyText' => $story->renderText(),
            'epoch' => $data->getEpoch(),
        );

        // NOTE: We're explicitly using "http_build_query()" here because the
        // "storyData" parameter may be a nested object with arbitrary nested
        // sub-objects.
        $post_data = http_build_query($post_data, '', '&');

        (new HTTPSFuture($uri, $post_data))
            ->setMethod('POST')
            ->setTimeout(30)
            ->resolvex();
    }

    /**
     * @param PhabricatorWorkerTask $task
     * @return float|int
     * @author 陈妙威
     */
    public function getWaitBeforeRetry(PhabricatorWorkerTask $task)
    {
        return max($task->getFailureCount(), 1) * 60;
    }

}

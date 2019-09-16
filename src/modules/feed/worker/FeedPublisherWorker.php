<?php

namespace orangins\modules\feed\worker;

use orangins\lib\env\PhabricatorEnv;

/**
 * Class FeedPublisherWorker
 * @package orangins\modules\feed\worker
 * @author 陈妙威
 */
final class FeedPublisherWorker extends FeedPushWorker
{

    /**
     * @return mixed|void
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function doWork()
    {
        $story = $this->loadFeedStory();

        $uris = PhabricatorEnv::getEnvConfig('feed.http-hooks');

        if ($uris) {
            foreach ($uris as $uri) {
                $this->queueTask(
                    'FeedPublisherHTTPWorker',
                    array(
                        'key' => $story->getChronologicalKey(),
                        'uri' => $uri,
                    ));
            }
        }

        $argv = array(
            array(),
        );

        // Find and schedule all the enabled Doorkeeper publishers.
        // TODO: Use PhutilClassMapQuery?
//        $doorkeeper_workers = (new PhutilSymbolLoader())
//            ->setAncestorClass('DoorkeeperFeedWorker')
//            ->loadObjects($argv);
//        foreach ($doorkeeper_workers as $worker) {
//            if (!$worker->isEnabled()) {
//                continue;
//            }
//            $this->queueTask(
//                get_class($worker),
//                array(
//                    'key' => $story->getChronologicalKey(),
//                ));
//        }
    }


}

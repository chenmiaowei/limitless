<?php

namespace orangins\modules\feed\worker;

use orangins\lib\infrastructure\daemon\workers\exception\PhabricatorWorkerPermanentFailureException;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\modules\feed\models\PhabricatorFeedStoryData;
use orangins\modules\feed\story\PhabricatorFeedStory;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class FeedPushWorker
 * @package orangins\modules\feed\worker
 * @author 陈妙威
 */
abstract class FeedPushWorker extends PhabricatorWorker
{

    /**
     * @return PhabricatorFeedStory
     * @throws PhabricatorWorkerPermanentFailureException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function loadFeedStory()
    {
        $task_data = $this->getTaskData();
        $key = $task_data['key'];

        $story = PhabricatorFeedStoryData::find()
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withChronologicalKeys(array($key))
            ->executeOne();

        if (!$story) {
            throw new PhabricatorWorkerPermanentFailureException(
                pht(
                    'Feed story (with key "%s") does not exist or could not be loaded.',
                    $key));
        }

        return $story;
    }
}

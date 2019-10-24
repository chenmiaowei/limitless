<?php

namespace orangins\modules\feed\management;

use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\modules\feed\models\PhabricatorFeedStoryData;
use PhutilArgumentParser;
use PhutilArgumentUsageException;
use PhutilConsole;

/**
 * Class PhabricatorFeedManagementRepublishWorkflow
 * @package orangins\modules\feed\management
 * @author 陈妙威
 */
final class PhabricatorFeedManagementRepublishWorkflow
    extends PhabricatorFeedManagementWorkflow
{

    /**
     * @return void|null
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('republish')
            ->setExamples('**republish** __story_key__')
            ->setSynopsis(
                pht(
                    'Republish a feed event to all consumers.'))
            ->setArguments(
                array(
                    array(
                        'name' => 'key',
                        'wildcard' => true,
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws PhutilArgumentUsageException
     * @throws \AphrontQueryException
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();
        $viewer = $this->getViewer();

        $key = $args->getArg('key');
        if (count($key) < 1) {
            throw new PhutilArgumentUsageException(
                pht('Specify a story key to republish.'));
        } else if (count($key) > 1) {
            throw new PhutilArgumentUsageException(
                pht('Specify exactly one story key to republish.'));
        }
        $key = head($key);

        $story = PhabricatorFeedStoryData::find()
            ->setViewer($viewer)
            ->withChronologicalKeys(array($key))
            ->executeOne();

        if (!$story) {
            throw new PhutilArgumentUsageException(
                pht('No story exists with key "%s"!', $key));
        }

        $console->writeOut("%s\n", pht('Republishing story...'));

        PhabricatorWorker::setRunAllTasksInProcess(true);

        PhabricatorWorker::scheduleTask(
            'FeedPublisherWorker',
            array(
                'key' => $key,
            ));

        $console->writeOut("%s\n", pht('Done.'));

        return 0;
    }

}

<?php

namespace orangins\modules\metamta\management;

use orangins\modules\metamta\models\PhabricatorMetaMTAReceivedMail;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use PhutilArgumentParser;
use PhutilConsole;
use PhutilConsoleTable;

/**
 * Class PhabricatorMailManagementListInboundWorkflow
 * @package orangins\modules\metamta\management
 * @author 陈妙威
 */
final class PhabricatorMailManagementListInboundWorkflow
    extends PhabricatorMailManagementWorkflow
{

    /**
     * @return void|null
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('list-inbound')
            ->setSynopsis(pht('List inbound messages received by Phabricator.'))
            ->setExamples(
                '**list-inbound**')
            ->setArguments(
                array(
                    array(
                        'name' => 'limit',
                        'param' => 'N',
                        'default' => 100,
                        'help' => pht(
                            'Show a specific number of messages (default 100).'),
                    ),
                ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @return int|void
     * @throws \PhutilArgumentSpecificationException
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();
        $viewer = $this->getViewer();

        $mails = PhabricatorMetaMTAReceivedMail::find()
            ->orderBy("id desc")
            ->limit($args->getArg('limit'))
            ->all();

        if (!$mails) {
            $console->writeErr("%s\n", pht('No received mail.'));
            return 0;
        }

        $phids = array_merge(
            mpull($mails, 'getRelatedPHID'),
            mpull($mails, 'getAuthorPHID'));
        $handles = (new PhabricatorHandleQuery())
            ->setViewer($viewer)
            ->withPHIDs($phids)
            ->execute();

        $table = (new PhutilConsoleTable())
            ->setShowHeader(false)
            ->addColumn('id', array('title' => pht('ID')))
            ->addColumn('author', array('title' => pht('Author')))
            ->addColumn('phid', array('title' => pht('Related PHID')))
            ->addColumn('subject', array('title' => pht('Subject')));

        /** @var PhabricatorMetaMTAReceivedMail[] $array_reverse */
        $array_reverse = array_reverse($mails);
        foreach ($array_reverse as $mail) {
            $table->addRow(array(
                'id' => $mail->getID(),
                'author' => $mail->getAuthorPHID()
                    ? $handles[$mail->getAuthorPHID()]->getName()
                    : '-',
                'phid' => $mail->getRelatedPHID()
                    ? $handles[$mail->getRelatedPHID()]->getName()
                    : '-',
                'subject' => $mail->getSubject()
                    ? $mail->getSubject()
                    : pht('(No subject.)'),
            ));
        }

        $table->draw();
        return 0;
    }

}

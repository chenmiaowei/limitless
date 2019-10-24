<?php

namespace orangins\modules\metamta\management;

use orangins\modules\metamta\constants\PhabricatorMailOutboundStatus;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\metamta\models\PhabricatorMetaMTAReceivedMail;
use PhutilArgumentParser;
use PhutilConsole;
use PhutilConsoleTable;

/**
 * Class PhabricatorMailManagementListOutboundWorkflow
 * @package orangins\modules\metamta\management
 * @author 陈妙威
 */
final class PhabricatorMailManagementListOutboundWorkflow
    extends PhabricatorMailManagementWorkflow
{

    /**
     * @return void|null
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this
            ->setName('list-outbound')
            ->setSynopsis(pht('List outbound messages sent by Phabricator.'))
            ->setExamples('**list-outbound**')
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
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $console = PhutilConsole::getConsole();
        $viewer = $this->getViewer();

//        $mails = id(new PhabricatorMetaMTAMail())->loadAllWhere(
//            '1 = 1 ORDER BY id DESC LIMIT %d',
//            $args->getArg('limit'));

        /** @var PhabricatorMetaMTAMail[] $mails */
        $mails = PhabricatorMetaMTAMail::find()
            ->orderBy("id desc")
            ->limit($args->getArg('limit'))
            ->all();

        if (!$mails) {
            $console->writeErr("%s\n", pht('No sent mail.'));
            return 0;
        }

        $table = (new PhutilConsoleTable())
            ->setShowHeader(false)
            ->addColumn('id', array('title' => pht('ID')))
            ->addColumn('encrypt', array('title' => pht('#')))
            ->addColumn('status', array('title' => pht('Status')))
            ->addColumn('type', array('title' => pht('Type')))
            ->addColumn('subject', array('title' => pht('Subject')));

        foreach (array_reverse($mails) as $mail) {
            $status = $mail->getStatus();

            $table->addRow(array(
                'id' => $mail->getID(),
                'encrypt' => ($mail->getMustEncrypt() ? '#' : ' '),
                'status' => PhabricatorMailOutboundStatus::getStatusName($status),
                'type' => $mail->getMessageType(),
                'subject' => $mail->getSubject(),
            ));
        }

        $table->draw();
        return 0;
    }

}

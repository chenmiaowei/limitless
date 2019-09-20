<?php

namespace orangins\modules\metamta\receiver;

use Exception;
use orangins\modules\metamta\constants\MetaMTAReceivedMailStatus;
use orangins\modules\metamta\models\exception\PhabricatorMetaMTAReceivedMailProcessingException;
use orangins\modules\metamta\models\PhabricatorMetaMTAApplicationEmail;
use orangins\modules\metamta\models\PhabricatorMetaMTAReceivedMail;
use orangins\modules\metamta\util\PhabricatorMailUtil;
use orangins\modules\people\models\PhabricatorUser;
use PhutilEmailAddress;

/**
 * Class PhabricatorApplicationMailReceiver
 * @package orangins\modules\metamta\receiver
 * @author 陈妙威
 */
abstract class PhabricatorApplicationMailReceiver
    extends PhabricatorMailReceiver
{

    /**
     * @var
     */
    private $applicationEmail;
    /**
     * @var PhabricatorMetaMTAApplicationEmail[]
     */
    private $emailList;
    /**
     * @var
     */
    private $author;

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newApplication();

    /**
     * @param PhabricatorMetaMTAApplicationEmail $email
     * @return $this
     * @author 陈妙威
     */
    final protected function setApplicationEmail(
        PhabricatorMetaMTAApplicationEmail $email)
    {
        $this->applicationEmail = $email;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function getApplicationEmail()
    {
        return $this->applicationEmail;
    }

    /**
     * @param PhabricatorUser $author
     * @return $this
     * @author 陈妙威
     */
    final protected function setAuthor(PhabricatorUser $author)
    {
        $this->author = $author;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final protected function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function isEnabled()
    {
        return $this->newApplication()->isInstalled();
    }

    /**
     * @param PhabricatorMetaMTAReceivedMail $mail
     * @param PhutilEmailAddress $target
     * @return bool
     * @throws PhabricatorMetaMTAReceivedMailProcessingException
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    final public function canAcceptMail(
        PhabricatorMetaMTAReceivedMail $mail,
        PhutilEmailAddress $target)
    {

        $viewer = $this->getViewer();
        $sender = $this->getSender();

        foreach ($this->loadApplicationEmailList() as $application_email) {
            $create_address = $application_email->newAddress();

            if (!PhabricatorMailUtil::matchAddresses($create_address, $target)) {
                continue;
            }

            if ($sender) {
                $author = $sender;
            } else {
                $author_phid = $application_email->getDefaultAuthorPHID();

                // If this mail isn't from a recognized sender and the target address
                // does not have a default author, we can't accept it, and it's an
                // error because you tried to send it here.

                // You either need to be sending from a real address or be sending to
                // an address which accepts mail from the public internet.

                if (!$author_phid) {
                    throw new PhabricatorMetaMTAReceivedMailProcessingException(
                        MetaMTAReceivedMailStatus::STATUS_UNKNOWN_SENDER,
                        pht(
                            'You are sending from an unrecognized email address to ' .
                            'an address which does not support public email ("%s").',
                            (string)$target));
                }

                $author =  PhabricatorUser::find()
                    ->setViewer($viewer)
                    ->withPHIDs(array($author_phid))
                    ->executeOne();
                if (!$author) {
                    throw new Exception(
                        pht(
                            'Application email ("%s") has an invalid default author ("%s").',
                            (string)$create_address,
                            $author_phid));
                }
            }

            $this
                ->setApplicationEmail($application_email)
                ->setAuthor($author);

            return true;
        }

        return false;
    }

    /**
     * @return PhabricatorMetaMTAApplicationEmail[]
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function loadApplicationEmailList()
    {
        if ($this->emailList === null) {
            $viewer = $this->getViewer();
            $application = $this->newApplication();

            $this->emailList = PhabricatorMetaMTAApplicationEmail::find()
                ->setViewer($viewer)
                ->withApplicationPHIDs(array($application->getPHID()))
                ->execute();
        }

        return $this->emailList;
    }

}

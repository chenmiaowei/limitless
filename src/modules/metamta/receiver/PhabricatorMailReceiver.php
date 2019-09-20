<?php

namespace orangins\modules\metamta\receiver;

use orangins\lib\OranginsObject;
use orangins\modules\metamta\models\PhabricatorMetaMTAReceivedMail;
use orangins\modules\people\models\PhabricatorUser;
use PhutilEmailAddress;

/**
 * Class PhabricatorMailReceiver
 * @package orangins\modules\metamta\receiver
 * @author 陈妙威
 */
abstract class PhabricatorMailReceiver extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $sender;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorUser $sender
     * @return $this
     * @author 陈妙威
     */
    final public function setSender(PhabricatorUser $sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getSender()
    {
        return $this->sender;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function isEnabled();

    /**
     * @param PhabricatorMetaMTAReceivedMail $mail
     * @param PhutilEmailAddress $target
     * @return mixed
     * @author 陈妙威
     */
    abstract public function canAcceptMail(
        PhabricatorMetaMTAReceivedMail $mail,
        PhutilEmailAddress $target);

    /**
     * @param PhabricatorMetaMTAReceivedMail $mail
     * @param PhutilEmailAddress $target
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function processReceivedMail(
        PhabricatorMetaMTAReceivedMail $mail,
        PhutilEmailAddress $target);

    /**
     * @param PhabricatorMetaMTAReceivedMail $mail
     * @param PhutilEmailAddress $target
     * @author 陈妙威
     */
    final public function receiveMail(
        PhabricatorMetaMTAReceivedMail $mail,
        PhutilEmailAddress $target)
    {
        $this->processReceivedMail($mail, $target);
    }

}

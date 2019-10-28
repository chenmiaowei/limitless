<?php

namespace orangins\modules\auth\mail;

use Exception;
use orangins\modules\auth\models\PhabricatorAuthSSHKey;
use orangins\modules\transactions\replyhandler\PhabricatorApplicationTransactionReplyHandler;

/**
 * Class PhabricatorAuthSSHKeyReplyHandler
 * @package orangins\modules\auth\mail
 * @author 陈妙威
 */
final class PhabricatorAuthSSHKeyReplyHandler
    extends PhabricatorApplicationTransactionReplyHandler
{

    /**
     * @param $mail_receiver
     * @throws Exception
     * @author 陈妙威
     */
    public function validateMailReceiver($mail_receiver)
    {
        if (!($mail_receiver instanceof PhabricatorAuthSSHKey)) {
            throw new Exception(
                pht('Mail receiver is not a %s!', 'PhabricatorAuthSSHKey'));
        }
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getObjectPrefix()
    {
        return 'SSHKEY';
    }

}

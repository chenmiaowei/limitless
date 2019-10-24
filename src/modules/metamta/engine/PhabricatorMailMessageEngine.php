<?php

namespace orangins\modules\metamta\engine;

use orangins\modules\metamta\adapters\PhabricatorMailAdapter;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\settings\models\PhabricatorUserPreferences;
use Phobject;

/**
 * Class PhabricatorMailMessageEngine
 * @package orangins\modules\metamta\engine
 * @author 陈妙威
 */
abstract class PhabricatorMailMessageEngine
    extends Phobject
{

    /**
     * @var
     */
    private $mailer;
    /**
     * @var
     */
    private $mail;
    /**
     * @var array
     */
    private $actors = array();
    /**
     * @var
     */
    private $preferences;

    /**
     * @param PhabricatorMailAdapter $mailer
     * @return $this
     * @author 陈妙威
     */
    final public function setMailer(PhabricatorMailAdapter $mailer)
    {

        $this->mailer = $mailer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * @param PhabricatorMetaMTAMail $mail
     * @return $this
     * @author 陈妙威
     */
    final public function setMail(PhabricatorMetaMTAMail $mail)
    {
        $this->mail = $mail;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getMail()
    {
        return $this->mail;
    }

    /**
     * @param array $actors
     * @return $this
     * @author 陈妙威
     */
    final public function setActors(array $actors)
    {
        assert_instances_of($actors, 'PhabricatorMetaMTAActor');
        $this->actors = $actors;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final public function getActors()
    {
        return $this->actors;
    }

    /**
     * @param $phid
     * @return object
     * @author 陈妙威
     */
    final public function getActor($phid)
    {
        return idx($this->actors, $phid);
    }

    /**
     * @param PhabricatorUserPreferences $preferences
     * @return $this
     * @author 陈妙威
     */
    final public function setPreferences(
        PhabricatorUserPreferences $preferences)
    {
        $this->preferences = $preferences;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getPreferences()
    {
        return $this->preferences;
    }

}

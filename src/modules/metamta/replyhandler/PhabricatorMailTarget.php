<?php

namespace orangins\modules\metamta\replyhandler;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\phid\handles\pool\PhabricatorHandleList;
use Phobject;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use PhutilSafeHTML;
use ReflectionException;
use yii\base\InvalidConfigException;

/**
 * Class PhabricatorMailTarget
 * @package orangins\modules\metamta\replyhandler
 * @author 陈妙威
 */
final class PhabricatorMailTarget extends Phobject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $replyTo;
    /**
     * @var array
     */
    private $toMap = array();
    /**
     * @var array
     */
    private $ccMap = array();
    /**
     * @var
     */
    private $rawToPHIDs;
    /**
     * @var
     */
    private $rawCCPHIDs;

    /**
     * @param array $to_phids
     * @return $this
     * @author 陈妙威
     */
    public function setRawToPHIDs(array $to_phids)
    {
        $this->rawToPHIDs = $to_phids;
        return $this;
    }

    /**
     * @param array $cc_phids
     * @return $this
     * @author 陈妙威
     */
    public function setRawCCPHIDs(array $cc_phids)
    {
        $this->rawCCPHIDs = $cc_phids;
        return $this;
    }

    /**
     * @param array $cc_map
     * @return $this
     * @author 陈妙威
     */
    public function setCCMap(array $cc_map)
    {
        $this->ccMap = $cc_map;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getCCMap()
    {
        return $this->ccMap;
    }

    /**
     * @param array $to_map
     * @return $this
     * @author 陈妙威
     */
    public function setToMap(array $to_map)
    {
        $this->toMap = $to_map;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getToMap()
    {
        return $this->toMap;
    }

    /**
     * @param $reply_to
     * @return $this
     * @author 陈妙威
     */
    public function setReplyTo($reply_to)
    {
        $this->replyTo = $reply_to;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * @param $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer($viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorMetaMTAMail $mail
     * @return PhabricatorMetaMTAMail
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @throws Exception
     * @author 陈妙威
     */
    public function willSendMail(PhabricatorMetaMTAMail $mail)
    {
        $viewer = $this->getViewer();

        $show_stamps = $mail->shouldRenderMailStampsInBody($viewer);

        $body = $mail->getBody();
        $html_body = $mail->getHTMLBody();
        $has_html = (strlen($html_body) > 0);

        if ($show_stamps) {
            $stamps = $mail->getMailStamps();
            if ($stamps) {
                $body .= "\n";
                $body .= pht('STAMPS');
                $body .= "\n";
                $body .= implode(' ', $stamps);
                $body .= "\n";

                if ($has_html) {
                    $html = array();
                    $html[] = phutil_tag('strong', array(), pht('STAMPS'));
                    $html[] = phutil_tag('br');
                    $html[] = phutil_tag(
                        'span',
                        array(
                            'style' => 'font-size: smaller; color: #92969D',
                        ),
                        phutil_implode_html(' ', $stamps));
                    $html[] = phutil_tag('br');
                    $html[] = phutil_tag('br');
                    $html = phutil_tag('div', array(), $html);
                    $html_body .= hsprintf('%s', $html);
                }
            }
        }

        $mail->addPHIDHeaders('X-Phabricator-To', $this->rawToPHIDs);
        $mail->addPHIDHeaders('X-Phabricator-Cc', $this->rawCCPHIDs);

        $to_handles = $viewer->loadHandles($this->rawToPHIDs);
        $cc_handles = $viewer->loadHandles($this->rawCCPHIDs);

        $body .= "\n";
        $body .= $this->getRecipientsSummary($to_handles, $cc_handles);

        if ($has_html) {
            $html_body .= hsprintf(
                '%s',
                $this->getRecipientsSummaryHTML($to_handles, $cc_handles));
        }

        $mail->setBody($body);
        $mail->setHTMLBody($html_body);

        $reply_to = $this->getReplyTo();
        if ($reply_to) {
            $mail->setReplyTo($reply_to);
        }

        $to = array_keys($this->getToMap());
        if ($to) {
            $mail->addTos($to);
        }

        $cc = array_keys($this->getCCMap());
        if ($cc) {
            $mail->addCCs($cc);
        }

        return $mail;
    }

    /**
     * @param PhabricatorHandleList $to_handles
     * @param PhabricatorHandleList $cc_handles
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    private function getRecipientsSummary(
        PhabricatorHandleList $to_handles,
        PhabricatorHandleList $cc_handles)
    {

        if (!PhabricatorEnv::getEnvConfig('metamta.recipients.show-hints')) {
            return '';
        }

        $to_handles = iterator_to_array($to_handles);
        $cc_handles = iterator_to_array($cc_handles);

        $body = '';

        if ($to_handles) {
            $to_names = mpull($to_handles, 'getCommandLineObjectName');
            $body .= "To: " . implode(', ', $to_names) . "\n";
        }

        if ($cc_handles) {
            $cc_names = mpull($cc_handles, 'getCommandLineObjectName');
            $body .= "Cc: " . implode(', ', $cc_names) . "\n";
        }

        return $body;
    }

    /**
     * @param PhabricatorHandleList $to_handles
     * @param PhabricatorHandleList $cc_handles
     * @return PhutilSafeHTML|string
     * @throws Exception
     * @author 陈妙威
     */
    private function getRecipientsSummaryHTML(
        PhabricatorHandleList $to_handles,
        PhabricatorHandleList $cc_handles)
    {

        if (!PhabricatorEnv::getEnvConfig('metamta.recipients.show-hints')) {
            return '';
        }

        $to_handles = iterator_to_array($to_handles);
        $cc_handles = iterator_to_array($cc_handles);

        $body = array();
        if ($to_handles) {
            $body[] = phutil_tag('strong', array(), 'To: ');
            $body[] = phutil_implode_html(', ', mpull($to_handles, 'getName'));
            $body[] = phutil_tag('br');
        }
        if ($cc_handles) {
            $body[] = phutil_tag('strong', array(), 'Cc: ');
            $body[] = phutil_implode_html(', ', mpull($cc_handles, 'getName'));
            $body[] = phutil_tag('br');
        }
        return phutil_tag('div', array(), $body);
    }


}

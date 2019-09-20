<?php

namespace orangins\modules\metamta\actions;

use AphrontWriteGuard;
use Exception;
use orangins\lib\response\Aphront400Response;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontWebpageResponse;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\metamta\models\PhabricatorMetaMTAReceivedMail;
use orangins\modules\policy\constants\PhabricatorPolicies;
use PhutilCIDRList;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorMetaMTAPostmarkReceiveAction
 * @package orangins\modules\metamta\actions
 * @author 陈妙威
 */
final class PhabricatorMetaMTAPostmarkReceiveAction
    extends PhabricatorMetaMTAAction
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireLogin()
    {
        return false;
    }

    /**
     * @phutil-external-symbol class PhabricatorStartup
     */
    public function run()
    {
        $request = $this->getRequest();
        // Don't process requests if we don't have a configured Postmark adapter.
        $mailers = PhabricatorMetaMTAMail::newMailers(
            array(
                'inbound' => true,
                'types' => array(
                    PhabricatorMailPostmarkAdapter::ADAPTERTYPE,
                ),
            ));
        if (!$mailers) {
            return new Aphront404Response();
        }

        $remote_address = $request->getRemoteAddress();
        $any_remote_match = false;
        foreach ($mailers as $mailer) {
            $inbound_addresses = $mailer->getOption('inbound-addresses');
            $cidr_list = PhutilCIDRList::newList($inbound_addresses);
            if ($cidr_list->containsAddress($remote_address)) {
                $any_remote_match = true;
                break;
            }
        }

        if (!$any_remote_match) {
            return new Aphront400Response();
        }

        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        $raw_input = $this->getRequest()->getRawBody();

        try {
            $data = phutil_json_decode($raw_input);
        } catch (Exception $ex) {
            return new Aphront400Response();
        }

        $raw_headers = array();
        $header_items = ArrayHelper::getValue($data, 'Headers', array());
        foreach ($header_items as $header_item) {
            $name = ArrayHelper::getValue($header_item, 'Name');
            $value = ArrayHelper::getValue($header_item, 'Value');
            $raw_headers[$name] = $value;
        }

        $headers = array(
                'to' => ArrayHelper::getValue($data, 'To'),
                'from' => ArrayHelper::getValue($data, 'From'),
                'cc' => ArrayHelper::getValue($data, 'Cc'),
                'subject' => ArrayHelper::getValue($data, 'Subject'),
            ) + $raw_headers;


        $received = (new PhabricatorMetaMTAReceivedMail())
            ->setHeaders($headers)
            ->setBodies(
                array(
                    'text' => ArrayHelper::getValue($data, 'TextBody'),
                    'html' => ArrayHelper::getValue($data, 'HtmlBody'),
                ));

        $file_phids = array();
        $attachments = ArrayHelper::getValue($data, 'Attachments', array());
        foreach ($attachments as $attachment) {
            $file_data = ArrayHelper::getValue($attachment, 'Content');
            $file_data = base64_decode($file_data);

            try {
                $file = PhabricatorFile::newFromFileData(
                    $file_data,
                    array(
                        'name' => ArrayHelper::getValue($attachment, 'Name'),
                        'viewPolicy' => PhabricatorPolicies::POLICY_NOONE,
                    ));
                $file_phids[] = $file->getPHID();
            } catch (Exception $ex) {
                phlog($ex);
            }
        }
        $received->setAttachments($file_phids);

        try {
            $received->save();
            $received->processReceivedMail();
        } catch (Exception $ex) {
            phlog($ex);
        }

        return (new AphrontWebpageResponse())
            ->setContent(pht("Got it! Thanks, Postmark!\n"));
    }

}

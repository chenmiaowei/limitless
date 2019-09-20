<?php

namespace orangins\modules\metamta\actions;

use AphrontWriteGuard;
use Exception;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontWebpageResponse;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\metamta\models\PhabricatorMetaMTAMail;
use orangins\modules\metamta\models\PhabricatorMetaMTAReceivedMail;
use orangins\modules\policy\constants\PhabricatorPolicies;

/**
 * Class PhabricatorMetaMTASendGridReceiveAction
 * @package orangins\modules\metamta\actions
 * @author 陈妙威
 */
final class PhabricatorMetaMTASendGridReceiveAction
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
     * @return Aphront404Response|AphrontWebpageResponse
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        // SendGrid doesn't sign payloads so we can't be sure that SendGrid
        // actually sent this request, but require a configured SendGrid mailer
        // before we activate this endpoint.
        $mailers = PhabricatorMetaMTAMail::newMailers(
            array(
                'inbound' => true,
                'types' => array(
                    PhabricatorMailSendGridAdapter::ADAPTERTYPE,
                ),
            ));
        if (!$mailers) {
            return new Aphront404Response();
        }

        // No CSRF for SendGrid.
        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        $user = $request->getUser();

        $raw_headers = $request->getStr('headers');
        $raw_headers = explode("\n", rtrim($raw_headers));
        $raw_dict = array();
        foreach (array_filter($raw_headers) as $header) {
            list($name, $value) = explode(':', $header, 2);
            $raw_dict[$name] = ltrim($value);
        }

        $headers = array(
                'to' => $request->getStr('to'),
                'from' => $request->getStr('from'),
                'subject' => $request->getStr('subject'),
            ) + $raw_dict;

        $received = new PhabricatorMetaMTAReceivedMail();
        $received->setHeaders($headers);
        $received->setBodies(array(
            'text' => $request->getStr('text'),
            'html' => $request->getStr('from'),
        ));

        $file_phids = array();
        foreach ($_FILES as $file_raw) {
            try {
                $file = PhabricatorFile::newFromPHPUpload(
                    $file_raw,
                    array(
                        'viewPolicy' => PhabricatorPolicies::POLICY_NOONE,
                    ));
                $file_phids[] = $file->getPHID();
            } catch (Exception $ex) {
                phlog($ex);
            }
        }
        $received->setAttachments($file_phids);
        $received->save();

        $received->processReceivedMail();

        $response = new AphrontWebpageResponse();
        $response->setContent(pht('Got it! Thanks, SendGrid!') . "\n");
        return $response;
    }

}

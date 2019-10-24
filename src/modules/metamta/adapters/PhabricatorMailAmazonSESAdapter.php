<?php

namespace orangins\modules\metamta\adapters;

use orangins\lib\env\PhabricatorEnv;
use PhutilTypeSpec;
use SimpleEmailService;

/**
 * Class PhabricatorMailImplementationAmazonSESAdapter
 * @package orangins\modules\metamta\adapters
 * @author 陈妙威
 */
final class PhabricatorMailAmazonSESAdapter
    extends PhabricatorMailPHPMailerLiteAdapter
{

    /**
     *
     */
    const ADAPTERTYPE = 'ses';

    /**
     * @var
     */
    private $message;
    /**
     * @var
     */
    private $isHTML;

    /**
     * @author 陈妙威
     */
    public function prepareForSend()
    {
        parent::prepareForSend();
        $this->mailer->Mailer = 'amazon-ses';
        $this->mailer->customMailer = $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function supportsMessageIDHeader()
    {
        // Amazon SES will ignore any Message-ID we provide.
        return false;
    }

    /**
     * @param array $options
     * @return mixed|void
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @author 陈妙威
     */
    protected function validateOptions(array $options)
    {
        PhutilTypeSpec::checkMap(
            $options,
            array(
                'access-key' => 'string',
                'secret-key' => 'string',
                'endpoint' => 'string',
                'encoding' => 'string',
            ));
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function newDefaultOptions()
    {
        return array(
            'access-key' => null,
            'secret-key' => null,
            'endpoint' => null,
            'encoding' => 'base64',
        );
    }

    /**
     * @return array|mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function newLegacyOptions()
    {
        return array(
            'access-key' => PhabricatorEnv::getEnvConfig('amazon-ses.access-key'),
            'secret-key' => PhabricatorEnv::getEnvConfig('amazon-ses.secret-key'),
            'endpoint' => PhabricatorEnv::getEnvConfig('amazon-ses.endpoint'),
            'encoding' => PhabricatorEnv::getEnvConfig('phpmailer.smtp-encoding'),
        );
    }

    /**
     * @phutil-external-symbol class SimpleEmailService
     * @throws \Exception
     */
    public function executeSend($body)
    {
        $key = $this->getOption('access-key');
        $secret = $this->getOption('secret-key');
        $endpoint = $this->getOption('endpoint');

        $root = phutil_get_library_root('orangins');
        require_once $root . '/../externals/amazon-ses/ses.php';

        $service = new SimpleEmailService($key, $secret, $endpoint);
        $service->enableUseExceptions(true);
        return $service->sendRawEmail($body);
    }

}

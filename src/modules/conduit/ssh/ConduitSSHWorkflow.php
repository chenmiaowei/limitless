<?php

use orangins\lib\infrastructure\ssh\PhabricatorSSHWorkflow;
use orangins\modules\conduit\call\ConduitCall;
use orangins\modules\conduit\models\PhabricatorConduitMethodCallLog;
use orangins\modules\conduit\protocol\ConduitAPIResponse;
use orangins\modules\conduit\protocol\exception\ConduitException;
use yii\helpers\ArrayHelper;

/**
 * Class ConduitSSHWorkflow
 * @author 陈妙威
 */
final class ConduitSSHWorkflow extends PhabricatorSSHWorkflow
{

    /**
     * @author 陈妙威
     */
    protected function didConstruct()
    {
        $this->setName('conduit');
        $this->setArguments(
            array(
                array(
                    'name' => 'method',
                    'wildcard' => true,
                ),
            ));
    }

    /**
     * @param PhutilArgumentParser $args
     * @throws PhutilArgumentSpecificationException
     * @throws PhutilProxyException
     * @throws Exception
     * @author 陈妙威
     */
    public function execute(PhutilArgumentParser $args)
    {
        $time_start = microtime(true);

        $methodv = $args->getArg('method');
        if (!$methodv) {
            throw new Exception(\Yii::t("app", 'No Conduit method provided.'));
        } else if (count($methodv) > 1) {
            throw new Exception(\Yii::t("app", 'Too many Conduit methods provided.'));
        }

        $method = head($methodv);

        $json = $this->readAllInput();
        $raw_params = null;
        try {
            $raw_params = phutil_json_decode($json);
        } catch (PhutilJSONParserException $ex) {
            throw new PhutilProxyException(
                \Yii::t("app", 'Invalid JSON input.'),
                $ex);
        }

        $params = ArrayHelper::getValue($raw_params, 'params', '[]');
        $params = phutil_json_decode($params);
        $metadata = ArrayHelper::getValue($params, '__conduit__', array());
        unset($params['__conduit__']);

        $call = null;
        $error_code = null;
        $error_info = null;

        try {
            $call = new ConduitCall($method, $params);
            $call->setUser($this->getSSHUser());

            $result = $call->execute();
        } catch (ConduitException $ex) {
            $result = null;
            $error_code = $ex->getMessage();
            if ($ex->getErrorDescription()) {
                $error_info = $ex->getErrorDescription();
            } else if ($call) {
                $error_info = $call->getErrorDescription($error_code);
            }
        }

        $response = (new ConduitAPIResponse())
            ->setResult($result)
            ->setErrorCode($error_code)
            ->setErrorInfo($error_info);

        $json_out = json_encode($response->toDictionary());
        $json_out = $json_out . "\n";

        $this->getIOChannel()->write($json_out);

        // NOTE: Flush here so we can get an accurate result for the duration,
        // if the response is large and the receiver is slow to read it.
        $this->getIOChannel()->flush();

        $connection_id = ArrayHelper::getValue($metadata, 'connectionID');
        $log = (new PhabricatorConduitMethodCallLog())
            ->setCallerPHID($this->getSSHUser()->getPHID())
            ->setConnectionID($connection_id)
            ->setMethod($method)
            ->setError((string)$error_code)
            ->setDuration(phutil_microseconds_since($time_start))
            ->save();
    }
}

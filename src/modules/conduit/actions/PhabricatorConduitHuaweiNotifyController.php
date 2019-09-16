<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/19
 * Time: 10:54 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\conduit\actions;


use orangins\lib\actions\PhabricatorAction;
use orangins\lib\infrastructure\daemon\workers\PhabricatorWorker;
use orangins\lib\response\AphrontJSONResponse;
use orangins\modules\conduit\protocol\ConduitAPIResponse;
use orangins\modules\sxbzxr\models\SxbzxrImport;
use orangins\modules\xgbzxr\models\XgbzxrImport;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConduitHuaweiNotifyController
 * @package orangins\modules\conduit\actions
 * @author 陈妙威
 */
class PhabricatorConduitHuaweiNotifyController extends PhabricatorAction
{
    /**
     * @var bool
     */
    public $enableCsrfValidation = false;

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireLogin()
    {
        return false;
    }

    /**
     * @return AphrontJSONResponse
     * @throws \AphrontQueryException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    public function run()
    {
        $requestData = $this->getRequest()->getRequestData();
        $str = ArrayHelper::getValue($requestData, "message");
//        $str = '{"Records":[{"eventVersion":"2.0","eventSource":"aws:s3","awsRegion":"cn-north-1","eventTime":"2019-04-19T03:07:23.114Z","eventName":"ObjectCreated:Post","userIdentity":{"principalId":"a75a2db8ca6a4d0ea2d5c33996098741"},"requestParameters":{"sourceIPAddress":"117.88.102.70"},"responseElements":{"x-amz-request-id":"0000016A33901A6A801711F7C1970A26","x-amz-id-2":"k2U3+oqyVcpdHBe3ay6A8vx+ESkzNfjNg2eLU8hNQa1UpTmijbpn+9tQqemJC8Fs"},"s3":{"s3SchemaVersion":"1.0","configurationId":"event-e02f","bucket":{"name":"kl-obs","ownerIdentity":{"PrincipalId":"7f630cd40bd4458db0e358c71fa15875"},"arn":"arn:aws:s3:::kl-obs"},"object":{"key":"sxbzxr%2F2018072001+-+%E2%89%A4%E2%80%9A%C2%A0%E2%80%98.xml","eTag":"393b3fa493cefa8d254372d6e2aaac69","size":9988,"versionId":"null","sequencer":"0000000016A33901A72533E4C0000000"}}}]}';

        \Yii::error($requestData);
        $json_decode = json_decode($str, true);
        $records = ArrayHelper::getValue($json_decode, "Records", []);
        foreach ($records as $record) {
            $s3 = ArrayHelper::getValue($record, "s3", []);
            $isDataRenew = ArrayHelper::getValue(ArrayHelper::getValue($s3, "bucket", []), "name") === "data-renew";
            if($isDataRenew) {
                $object = ArrayHelper::getValue($s3, "object", []);
                $key = ArrayHelper::getValue($object, "key");
                if($key) {
                    if(substr($key, 0, 2) === "sx") {
                        $sxbzxrImport = new SxbzxrImport();
                        $sxbzxrImport->filename = $key;
                        $sxbzxrImport->save();
                        PhabricatorWorker::scheduleTask(
                            'PhabricatorSxbzxrImportWorker',
                            [
                                'id' => $sxbzxrImport->getID()
                            ]);
                    } else if(substr($key, 0, 2) === "xg") {
                        $sxbzxrImport = new XgbzxrImport();
                        $sxbzxrImport->filename = $key;
                        $sxbzxrImport->save();
                        PhabricatorWorker::scheduleTask(
                            'PhabricatorXgbzxrImportWorker',
                            [
                                'id' => $sxbzxrImport->getID()
                            ]);
                    }
                }
            }

        }

        $response = (new ConduitAPIResponse())
            ->setResult([]);
        return (new AphrontJSONResponse())
            ->setAddJSONShield(false)
            ->setContent($response->toDictionary());
    }
}
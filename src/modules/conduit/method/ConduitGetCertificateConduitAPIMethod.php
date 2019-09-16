<?php

namespace orangins\modules\conduit\method;

use Exception;
use orangins\modules\conduit\models\PhabricatorConduitCertificateToken;
use orangins\modules\conduit\protocol\ConduitAPIRequest;
use orangins\modules\conduit\protocol\exception\ConduitException;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserLog;

/**
 * Class ConduitGetCertificateConduitAPIMethod
 * @package orangins\modules\conduit\method
 * @author 陈妙威
 */
final class ConduitGetCertificateConduitAPIMethod extends ConduitAPIMethod
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getAPIMethodName()
    {
        return 'conduit.getcertificate';
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireAuthentication()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowUnguardedWrites()
    {
        // This method performs logging and is on the authentication pathway.
        return true;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodDescription()
    {
        return \Yii::t("app",'Retrieve certificate information for a user.');
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function defineParamTypes()
    {
        return array(
            'token' => 'required string',
            'host' => 'required string',
        );
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function defineReturnType()
    {
        return 'dict<string, any>';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function defineErrorTypes()
    {
        return array(
            'ERR-BAD-TOKEN' => \Yii::t("app",'Token does not exist or has expired.'),
            'ERR-RATE-LIMIT' => \Yii::t("app",
                'You have made too many invalid token requests recently. Wait before ' .
                'making more.'),
        );
    }

    /**
     * @param ConduitAPIRequest $request
     * @return array
     * @throws \yii\base\Exception
     * @throws ConduitException
     * @throws Exception
     * @author 陈妙威
     */
    protected function execute(ConduitAPIRequest $request)
    {
        $failed_attempts = PhabricatorUserLog::loadRecentEventsFromThisIP(
            PhabricatorUserLog::ACTION_CONDUIT_CERTIFICATE_FAILURE,
            60 * 5);

        if (count($failed_attempts) > 5) {
            $this->logFailure($request);
            throw new ConduitException('ERR-RATE-LIMIT');
        }

        $token = $request->getValue('token');

        $info = PhabricatorConduitCertificateToken::find()->andWhere(['token' => trim($token)])->one();
        if (!$info || $info->created_at < time() - (60 * 15)) {
            $this->logFailure($request, $info);
            throw new ConduitException('ERR-BAD-TOKEN');
        } else {
            $log = PhabricatorUserLog::initializeNewLog(
                $request->getUser(),
                $info->getUserPHID(),
                PhabricatorUserLog::ACTION_CONDUIT_CERTIFICATE)
                ->save();
        }

        $user = PhabricatorUser::find()->andWhere([
            'phid' => $info->getUserPHID()
        ])->one();
        if (!$user) {
            throw new Exception(\Yii::t("app",'Certificate token points to an invalid user!'));
        }
        return array(
            'username' => $user->getUserName(),
            'certificate' => $user->getConduitCertificate(),
        );
    }

    /**
     * @param ConduitAPIRequest $request
     * @param PhabricatorConduitCertificateToken|null $info
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    private function logFailure(
        ConduitAPIRequest $request,
        PhabricatorConduitCertificateToken $info = null)
    {

        $log = PhabricatorUserLog::initializeNewLog(
            $request->getUser(),
            $info ? $info->getUserPHID() : '-',
            PhabricatorUserLog::ACTION_CONDUIT_CERTIFICATE_FAILURE)
            ->save();
    }

}

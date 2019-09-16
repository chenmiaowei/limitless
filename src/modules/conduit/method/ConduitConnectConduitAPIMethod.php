<?php

namespace orangins\modules\conduit\method;

use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\conduit\protocol\ConduitAPIRequest;
use orangins\modules\conduit\protocol\exception\ConduitException;
use orangins\modules\people\models\PhabricatorUser;
use PhutilUTF8StringTruncator;

/**
 * Class ConduitConnectConduitAPIMethod
 * @package orangins\modules\conduit\method
 * @author 陈妙威
 */
final class ConduitConnectConduitAPIMethod extends ConduitAPIMethod
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAPIMethodName()
    {
        return 'conduit.connect';
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
        return true;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodDescription()
    {
        return \Yii::t("app",'Connect a session-based client.');
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function defineParamTypes()
    {
        return array(
            'client' => 'required string',
            'clientVersion' => 'required int',
            'clientDescription' => 'optional string',
            'user' => 'optional string',
            'authToken' => 'optional int',
            'authSignature' => 'optional string',
            'host' => 'deprecated',
        );
    }

    /**
     * @return mixed|string
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
            'ERR-BAD-VERSION' => \Yii::t("app",
                'Client/server version mismatch. Upgrade your server or downgrade ' .
                'your client.'),
            'NEW-ARC-VERSION' => \Yii::t("app",
                'Client/server version mismatch. Upgrade your client.'),
            'ERR-UNKNOWN-CLIENT' => \Yii::t("app",'Client is unknown.'),
            'ERR-INVALID-USER' => \Yii::t("app",
                'The username you are attempting to authenticate with is not valid.'),
            'ERR-INVALID-CERTIFICATE' => \Yii::t("app",
                'Your authentication certificate for this server is invalid.'),
            'ERR-INVALID-TOKEN' => \Yii::t("app",
                "The challenge token you are authenticating with is outside of the " .
                "allowed time range. Either your system clock is out of whack or " .
                "you're executing a replay attack."),
            'ERR-NO-CERTIFICATE' => \Yii::t("app",'This server requires authentication.'),
        );
    }

    /**
     * @param ConduitAPIRequest $request
     * @return array|mixed
     * @throws ConduitException
     * @throws \AphrontQueryException
     * @throws \yii\base\Exception
     * @throws \yii\db\IntegrityException
     * @author 陈妙威
     */
    protected function execute(ConduitAPIRequest $request)
    {
        $client = $request->getValue('client');
        $client_version = (int)$request->getValue('clientVersion');
        $client_description = (string)$request->getValue('clientDescription');
        $client_description = (new PhutilUTF8StringTruncator())
            ->setMaximumBytes(255)
            ->truncateString($client_description);
        $username = (string)$request->getValue('user');

        switch ($client) {
            case 'arc':
                $server_version = 6;
                $supported_versions = array(
                    $server_version => true,
                    // Client version 5 introduced "user.query" call
                    4 => true,
                    // Client version 6 introduced "diffusion.getlintmessages" call
                    5 => true,
                );

                if (empty($supported_versions[$client_version])) {
                    if ($server_version < $client_version) {
                        $ex = new ConduitException('ERR-BAD-VERSION');
                        $ex->setErrorDescription(
                            \Yii::t("app",
                                "Your '{0}' client version is '{1}', which is newer than the " .
                                "server version, '{2}'. Upgrade your Phabricator install.", [
                                    'arc',
                                    $client_version,
                                    $server_version
                                ]));
                    } else {
                        $ex = new ConduitException('NEW-ARC-VERSION');
                        $ex->setErrorDescription(
                            \Yii::t("app",
                                'A new version of arc is available! You need to upgrade ' .
                                'to connect to this server (you are running version ' .
                                '{0}, the server is running version {1}).', [
                                    $client_version,
                                    $server_version
                                ]));
                    }
                    throw $ex;
                }
                break;
            default:
                // Allow new clients by default.
                break;
        }

        $token = $request->getValue('authToken');
        $signature = $request->getValue('authSignature');

        $user = PhabricatorUser::find()->andWhere(['username' => $username] )->one();
        if (!$user) {
            throw new ConduitException('ERR-INVALID-USER');
        }

        $session_key = null;
        if ($token && $signature) {
            $threshold = 60 * 15;
            $now = time();
            if (abs($token - $now) > $threshold) {
                throw (new ConduitException('ERR-INVALID-TOKEN'))
                    ->setErrorDescription(
                        \Yii::t("app",
                            'The request you submitted is signed with a timestamp, but that ' .
                            'timestamp is not within %s of the current time. The ' .
                            'signed timestamp is %s (%s), and the current server time is ' .
                            '%s (%s). This is a difference of %s seconds, but the ' .
                            'timestamp must differ from the server time by no more than ' .
                            '%s seconds. Your client or server clock may not be set ' .
                            'correctly.',
                            phutil_format_relative_time($threshold),
                            $token,
                            date('r', $token),
                            $now,
                            date('r', $now),
                            ($token - $now),
                            $threshold));
            }
            $valid = sha1($token . $user->getConduitCertificate());
            if (!phutil_hashes_are_identical($valid, $signature)) {
                throw new ConduitException('ERR-INVALID-CERTIFICATE');
            }
            $session_key = (new PhabricatorAuthSessionEngine())->establishSession(
                PhabricatorAuthSession::TYPE_CONDUIT,
                $user->getPHID(),
                $partial = false);
        } else {
            throw new ConduitException('ERR-NO-CERTIFICATE');
        }

        return array(
            'connectionID' => mt_rand(),
            'sessionKey' => $session_key,
            'userPHID' => $user->getPHID(),
        );
    }

}

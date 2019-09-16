<?php

namespace orangins\modules\metamta\query;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\infrastructure\query\PhabricatorBaseQuery;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserEmail;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\people\phid\PhabricatorPeopleUserPHIDType;
use orangins\modules\people\phid\PhabricatorPeopleExternalPHIDType;
use orangins\modules\phid\helpers\PhabricatorPHID;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorMetaMTAActorQuery
 * @package orangins\modules\metamta\query
 * @author 陈妙威
 */
final class PhabricatorMetaMTAActorQuery extends PhabricatorBaseQuery
{

    /**
     * @var array
     */
    private $phids = array();

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPHIDs($phids)
    {
        $this->phids = $phids;
        return $this;
    }

    /**
     * @return PhabricatorMetaMTAActor[]
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    public function execute()
    {
        $phids = OranginsUtil::array_fuse($this->phids);
        $actors = array();
        $type_map = array();
        foreach ($phids as $phid) {
            $type_map[PhabricatorPHID::phid_get_type($phid)][] = $phid;
            $actors[$phid] = (new PhabricatorMetaMTAActor())->setPHID($phid);
        }

        // TODO: Move this to PhabricatorPHIDType, or the objects, or some
        // interface.

        foreach ($type_map as $type => $phids) {
            switch ($type) {
                case PhabricatorPeopleUserPHIDType::TYPECONST:
                    $this->loadUserActors($actors, $phids);
                    break;
                case PhabricatorPeopleExternalPHIDType::TYPECONST:
                    $this->loadExternalUserActors($actors, $phids);
                    break;
                default:
                    $this->loadUnknownActors($actors, $phids);
                    break;
            }
        }

        return $actors;
    }

    /**
     * @param PhabricatorMetaMTAActor[] $actors
     * @param array $phids
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \Exception
     */
    private function loadUserActors(array $actors, array $phids)
    {
        OranginsUtil::assert_instances_of($actors, PhabricatorMetaMTAActor::class);

        $emails = PhabricatorUserEmail::find()->where([
            'AND',
            ['IN', 'user_phid', $phids],
            ['is_primary' => 1]
        ])->all();

        $emails = OranginsUtil::mpull($emails, null, 'user_phid');

        $users = PhabricatorUser::find()
            ->setViewer($this->getViewer())
            ->withPHIDs($phids)
            ->all();
        $users = OranginsUtil::mpull($users, null, "phid");

        foreach ($phids as $phid) {
            $actor = $actors[$phid];
            /** @var PhabricatorUser $user */
            $user = ArrayHelper::getValue($users, $phid);
            if (!$user) {
                $actor->setUndeliverable(PhabricatorMetaMTAActor::REASON_UNLOADABLE);
            } else {
                $actor->setName($this->getUserName($user));
                if ($user->getIsDisabled()) {
                    $actor->setUndeliverable(PhabricatorMetaMTAActor::REASON_DISABLED);
                }
                if ($user->getIsSystemAgent()) {
                    $actor->setUndeliverable(PhabricatorMetaMTAActor::REASON_BOT);
                }

                // NOTE: We do send email to unapproved users, and to unverified users,
                // because it would otherwise be impossible to get them to verify their
                // email addresses. Possibly we should white-list this kind of mail and
                // deny all other types of mail.
            }

            $email = ArrayHelper::getValue($emails, $phid);
            if (!$email) {
                $actor->setUndeliverable(PhabricatorMetaMTAActor::REASON_NO_ADDRESS);
            } else {
                $actor->setEmailAddress($email->getAddress());
                $actor->setIsVerified($email->getIsVerified());
            }
        }
    }

    /**
     * @param PhabricatorMetaMTAActor[] $actors $actors
     * @param array $phids
     * @author 陈妙威
     * @throws \Exception
     */
    private function loadExternalUserActors(array $actors, array $phids)
    {
        OranginsUtil::assert_instances_of($actors, PhabricatorMetaMTAActor::class);

        /** @var PhabricatorExternalAccount[] $xusers */
        $xusers = PhabricatorExternalAccount::find()
            ->setViewer($this->getViewer())
            ->withPHIDs($phids)
            ->all();
        $xusers = OranginsUtil::mpull($xusers, null, 'phid');

        foreach ($phids as $phid) {
            $actor = $actors[$phid];
            /** @var PhabricatorExternalAccount $xuser */
            $xuser = ArrayHelper::getValue($xusers, $phid);
            if (!$xuser) {
                $actor->setUndeliverable(PhabricatorMetaMTAActor::REASON_UNLOADABLE);
                continue;
            }

            $actor->setName($xuser->getDisplayName());

            if ($xuser->account_type != 'email') {
                $actor->setUndeliverable(PhabricatorMetaMTAActor::REASON_EXTERNAL_TYPE);
                continue;
            }

            $actor->setEmailAddress($xuser->account_id);

            // NOTE: This effectively drops all outbound mail to unrecognized
            // addresses unless "phabricator.allow-email-users" is set. See T12237
            // for context.
            $allow_key = 'phabricator.allow-email-users';
            $allow_value = PhabricatorEnv::getEnvConfig($allow_key);
            $actor->setIsVerified((bool)$allow_value);
        }
    }


    /**
     * @param array $actors
     * @param array $phids
     * @author 陈妙威
     */
    private function loadUnknownActors(array $actors, array $phids)
    {
        foreach ($phids as $phid) {
            $actor = $actors[$phid];
            $actor->setUndeliverable(PhabricatorMetaMTAActor::REASON_UNMAILABLE);
        }
    }


    /**
     * Small helper function to make sure we format the username properly as
     * specified by the `metamta.user-address-format` configuration value.
     * @param PhabricatorUser $user
     * @return string
     * @throws \Exception
     */
    private function getUserName(PhabricatorUser $user)
    {
        $format = PhabricatorEnv::getEnvConfig('metamta.user-address-format');

        switch ($format) {
            case 'short':
                $name = $user->username;
                break;
            case 'real':
                $name = strlen($user->username) ?
                    $user->real_name : $user->username;
                break;
            case 'full':
            default:
                $name = $user->getFullName();
                break;
        }
        return $name;
    }

}

<?php

namespace orangins\modules\policy\config;

use orangins\modules\config\customer\PhabricatorConfigJSONOptionType;
use orangins\modules\config\option\PhabricatorConfigOption;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDConstants;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\models\PhabricatorPolicyQuery;
use PhutilClassMapQuery;
use Exception;
use PhutilInvalidStateException;
use ReflectionException;
use Yii;
use yii\helpers\ArrayHelper;

final class PolicyLockOptionType
    extends PhabricatorConfigJSONOptionType
{

    /**
     * @param PhabricatorConfigOption $option
     * @param $value
     * @throws Exception
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function validateOption(PhabricatorConfigOption $option, $value)
    {
        $capabilities = (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorPolicyCapability::className())
            ->setUniqueMethod('getCapabilityKey')
            ->execute();

        $policy_phids = array();
        foreach ($value as $capability_key => $policy) {
            $capability = ArrayHelper::getValue($capabilities, $capability_key);
            if (!$capability) {
                throw new Exception(
                    Yii::t("app",
                        'Capability "%s" does not exist.',
                        $capability_key));
            }
            if (PhabricatorPHID::phid_get_type($policy) !=
                PhabricatorPHIDConstants::PHID_TYPE_UNKNOWN) {
                $policy_phids[$policy] = $policy;
            } else {
                try {
                    $policy_object = PhabricatorPolicyQuery::getGlobalPolicy($policy);
                    // this exception is not helpful here as its about global policy;
                    // throw a better exception
                } catch (Exception $ex) {
                    throw new Exception(
                        Yii::t("app",
                            'Capability "{0}" has invalid policy "{1}".', [
                                $capability_key,
                                $policy
                            ]));
                }
            }

            if ($policy == PhabricatorPolicies::POLICY_PUBLIC) {
                if (!$capability->shouldAllowPublicPolicySetting()) {
                    throw new Exception(
                        Yii::t("app",
                            'Capability "{0}" does not support public policy.', [
                                $capability_key
                            ]));
                }
            }
        }

        if ($policy_phids) {
            $handles = (new PhabricatorHandleQuery())
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withPhids($policy_phids)
                ->execute();
            $handles = mpull($handles, null, 'getPHID');
            foreach ($value as $capability_key => $policy) {
                $handle = $handles[$policy];
                if (!$handle->isComplete()) {
                    throw new Exception(
                        Yii::t("app",
                            'Capability "{0}" has invalid policy "{1}"; "{2}" does not exist.',
                            [
                                $capability_key,
                                $policy,
                                $policy
                            ]));
                }
            }
        }
    }

}

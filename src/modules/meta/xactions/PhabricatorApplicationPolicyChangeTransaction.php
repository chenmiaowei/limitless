<?php

namespace orangins\modules\meta\xactions;

use orangins\modules\config\editor\PhabricatorConfigEditor;
use orangins\modules\config\models\PhabricatorConfigEntry;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\models\PhabricatorPolicyQuery;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorApplicationPolicyChangeTransaction
 * @package orangins\modules\meta\xactions
 * @author 陈妙威
 */
final class PhabricatorApplicationPolicyChangeTransaction
    extends PhabricatorApplicationTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'application.policy';
    /**
     *
     */
    const METADATA_ATTRIBUTE = 'capability.name';

    /**
     * @var
     */
    private $policies;

    /**
     * @param $object
     * @author 陈妙威
     * @return
     */
    public function generateOldValue($object)
    {
        $application = $object;
        $capability = $this->getCapabilityName();
        return $application->getPolicy($capability);
    }

    /**
     * @param $object
     * @param $value
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException

     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function applyExternalEffects($object, $value)
    {
        $application = $object;
        $user = $this->getActor();

        $key = 'phabricator.application-settings';
        $config_entry = PhabricatorConfigEntry::loadConfigEntry($key);
        $current_value = $config_entry->getValue();

        $phid = $application->getPHID();
        if (empty($current_value[$phid])) {
            $current_value[$application->getPHID()] = array();
        }
        if (empty($current_value[$phid]['policy'])) {
            $current_value[$phid]['policy'] = array();
        }

        $new = array($this->getCapabilityName() => $value);
        $current_value[$phid]['policy'] = $new + $current_value[$phid]['policy'];

        $editor = $this->getEditor();
        $content_source = $editor->getContentSource();

        // NOTE: We allow applications to have custom edit policies, but they are
        // currently stored in the Config application. The ability to edit Config
        // values is always restricted to administrators, today. Empower this
        // particular edit to punch through possible stricter policies, so normal
        // users can change application configuration if the application allows
        // them to do so.

        PhabricatorConfigEditor::storeNewValue(
            PhabricatorUser::getOmnipotentUser(),
            $config_entry,
            $current_value,
            $content_source,
            $user->getPHID());
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTitle()
    {
        $old = $this->renderApplicationPolicy($this->getOldValue());
        $new = $this->renderApplicationPolicy($this->getNewValue());

        return \Yii::t("app",
            '%s changed the "%s" policy from "%s" to "%s".',
            $this->renderAuthor(),
            $this->renderCapability(),
            $old,
            $new);
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        $old = $this->renderApplicationPolicy($this->getOldValue());
        $new = $this->renderApplicationPolicy($this->getNewValue());

        return \Yii::t("app",
            '%s changed the "%s" policy for application %s from "%s" to "%s".',
            $this->renderAuthor(),
            $this->renderCapability(),
            $this->renderObject(),
            $old,
            $new);
    }

    /**
     * @param $object
     * @param array $xactions
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $user = $this->getActor();
        $application = $object;
        $policies = id(new PhabricatorPolicyQuery())
            ->setViewer($user)
            ->setObject($application)
            ->execute();

        $errors = array();
        foreach ($xactions as $xaction) {
            $new = $xaction->getNewValue();
            $capability = $xaction->getMetadataValue(self::METADATA_ATTRIBUTE);

            if (empty($policies[$new])) {
                // Not a standard policy, check for a custom policy.
                $policy = id(new PhabricatorPolicyQuery())
                    ->setViewer($user)
                    ->withPHIDs(array($new))
                    ->executeOne();
                if (!$policy) {
                    $errors[] = $this->newInvalidError(
                        \Yii::t("app",'Policy does not exist.'));
                    continue;
                }
            } else {
                $policy = ArrayHelper::getValue($policies, $new);
            }

            if (!$policy->isValidPolicyForEdit()) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",'Can\'t set the policy to a policy you can\'t view!'));
                continue;
            }

            if ($new == PhabricatorPolicies::POLICY_PUBLIC) {
                $capobj = PhabricatorPolicyCapability::getCapabilityByKey(
                    $capability);
                if (!$capobj || !$capobj->shouldAllowPublicPolicySetting()) {
                    $errors[] = $this->newInvalidError(
                        \Yii::t("app",'Can\'t set non-public policies to public.'));
                    continue;
                }
            }

            if (!$application->isCapabilityEditable($capability)) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",'Capability "%s" is not editable for this application.',
                        $capability));
                continue;
            }
        }

        // If we're changing these policies, the viewer needs to still be able to
        // view or edit the application under the new policy.
        $validate_map = array(
            PhabricatorPolicyCapability::CAN_VIEW,
            PhabricatorPolicyCapability::CAN_EDIT,
        );
        $validate_map = array_fill_keys($validate_map, array());

        foreach ($xactions as $xaction) {
            $capability = $xaction->getMetadataValue(self::METADATA_ATTRIBUTE);
            if (!isset($validate_map[$capability])) {
                continue;
            }

            $validate_map[$capability][] = $xaction;
        }

        foreach ($validate_map as $capability => $cap_xactions) {
            if (!$cap_xactions) {
                continue;
            }

            $editor = $this->getEditor();
            $policy_errors = $editor->validatePolicyTransaction(
                $object,
                $cap_xactions,
                self::TRANSACTIONTYPE,
                $capability);

            foreach ($policy_errors as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * @param $name
     * @return string
     * @author 陈妙威
     */
    private function renderApplicationPolicy($name)
    {
        $policies = $this->getAllPolicies();
        if (empty($policies[$name])) {
            // Not a standard policy, check for a custom policy.
            $policy = id(new PhabricatorPolicyQuery())
                ->setViewer($this->getViewer())
                ->withPHIDs(array($name))
                ->executeOne();
            $policies[$name] = $policy;
        }

        $policy = ArrayHelper::getValue($policies, $name);
        return $this->renderValue($policy->getFullName());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getAllPolicies()
    {
        if (!$this->policies) {
            $viewer = $this->getViewer();
            $application = $this->getObject();
            $this->policies = id(new PhabricatorPolicyQuery())
                ->setViewer($viewer)
                ->setObject($application)
                ->execute();
        }

        return $this->policies;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function renderCapability()
    {
        $application = $this->getObject();
        $capability = $this->getCapabilityName();
        return $application->getCapabilityLabel($capability);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function getCapabilityName()
    {
        return $this->getMetadataValue(self::METADATA_ATTRIBUTE);
    }

}

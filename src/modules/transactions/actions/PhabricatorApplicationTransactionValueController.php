<?php

namespace orangins\modules\transactions\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\view\AphrontDialogView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\policy\constants\PhabricatorPolicyType;
use orangins\modules\policy\models\PhabricatorPolicy;
use orangins\modules\transactions\constants\PhabricatorTransactions;

/**
 * Class PhabricatorApplicationTransactionValueController
 * @package orangins\modules\transactions\actions
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionValueController
    extends PhabricatorApplicationTransactionController
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return Aphront404Response|\orangins\lib\view\AphrontDialogView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $phid = $request->getURIData('phid');
        $type = $request->getURIData('value');

        $xaction = (new PhabricatorObjectQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($phid))
            ->executeOne();
        if (!$xaction) {
            return new Aphront404Response();
        }

        // For now, this pathway only supports policy transactions
        // to show the details of custom policies. If / when this pathway
        // supports more transaction types, rendering coding should be moved
        // into PhabricatorTransactions e.g. feed rendering code.

        // TODO: This should be some kind of "hey do you support this?" thing on
        // the transactions themselves.

        switch ($xaction->getTransactionType()) {
            case PhabricatorTransactions::TYPE_VIEW_POLICY:
            case PhabricatorTransactions::TYPE_EDIT_POLICY:
            case PhabricatorTransactions::TYPE_JOIN_POLICY:
            case PhabricatorRepositoryPushPolicyTransaction::TRANSACTIONTYPE:
                break;
            default:
                return new Aphront404Response();
                break;
        }

        if ($type == 'old') {
            $value = $xaction->getOldValue();
        } else {
            $value = $xaction->getNewValue();
        }

        $policy = PhabricatorPolicy::find()
            ->setViewer($viewer)
            ->withPHIDs(array($value))
            ->executeOne();
        if (!$policy) {
            return new Aphront404Response();
        }

        if ($policy->getType() != PhabricatorPolicyType::TYPE_CUSTOM) {
            return new Aphront404Response();
        }

        $rule_objects = array();
        foreach ($policy->getCustomRuleClasses() as $class) {
            $rule_objects[$class] = newv($class, array());
        }
        $policy->attachRuleObjects($rule_objects);

        $this->requireResource('policy-transaction-detail-css');
        $cancel_uri = $this->guessCancelURI($viewer, $xaction);

        return $this->newDialog()
            ->setTitle($policy->getFullName())
            ->setWidth(AphrontDialogView::WIDTH_FORM)
            ->appendChild($this->renderPolicyDetails($policy, $rule_objects))
            ->addCancelButton($cancel_uri, \Yii::t("app", 'Close'));
    }

    /**
     * @param PhabricatorPolicy $policy
     * @param array $rule_objects
     * @return array
     * @author 陈妙威
     */
    private function extractPHIDs(
        PhabricatorPolicy $policy,
        array $rule_objects)
    {

        $phids = array();
        foreach ($policy->getRules() as $rule) {
            $rule_object = $rule_objects[$rule['rule']];
            $phids[] =
                $rule_object->getRequiredHandlePHIDsForSummary($rule['value']);
        }
        return array_filter(array_mergev($phids));
    }

    /**
     * @param PhabricatorPolicy $policy
     * @param array $rule_objects
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderPolicyDetails(
        PhabricatorPolicy $policy,
        array $rule_objects)
    {
        $details = array();
        $details[] = phutil_tag(
            'p',
            array(
                'class' => 'policy-transaction-detail-intro',
            ),
            \Yii::t("app", 'These rules are processed in order:'));

        foreach ($policy->getRules() as $index => $rule) {
            $rule_object = $rule_objects[$rule['rule']];
            if ($rule['action'] == 'allow') {
                $icon = 'fa-check-circle green';
            } else {
                $icon = 'fa-minus-circle red';
            }
            $icon = (new PHUIIconView())
                ->setIcon($icon)
                ->setText(
                    ucfirst($rule['action']) . ' ' . $rule_object->getRuleDescription());

            $handle_phids = $rule_object->getRequiredHandlePHIDsForSummary(
                $rule['value']);
            if ($handle_phids) {
                $value = $this->getViewer()
                    ->renderHandleList($handle_phids)
                    ->setAsInline(true);
            } else {
                $value = $rule['value'];
            }

            $details[] = phutil_tag('div',
                array(
                    'class' => 'policy-transaction-detail-row',
                ),
                array(
                    $icon,
                    $value,
                ));
        }

        $details[] = phutil_tag(
            'p',
            array(
                'class' => 'policy-transaction-detail-end',
            ),
            \Yii::t("app",
                'If no rules match, %s all other users.',
                phutil_tag('b',
                    array(),
                    $policy->getDefaultAction())));
        return $details;
    }
}

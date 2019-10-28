<?php

namespace orangins\modules\herald\actions;

use AphrontObjectMissingQueryException;
use AphrontQueryException;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\modules\herald\capability\HeraldManageGlobalRulesCapability;
use orangins\modules\herald\editors\HeraldRuleEditor;
use orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\models\HeraldRuleTransaction;
use orangins\modules\herald\xaction\HeraldRuleDisableTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException;
use orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException;
use PhutilInvalidStateException;
use PhutilJSONParserException;
use PhutilMethodNotImplementedException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use Throwable;
use yii\base\InvalidConfigException;
use yii\db\IntegrityException;

/**
 * Class HeraldDisableController
 * @package orangins\modules\herald\actions
 * @author 陈妙威
 */
final class HeraldDisableController extends HeraldController
{

    /**
     * @return mixed|Aphront404Response
     * @throws InvalidConfigException
     * @throws PhabricatorApplicationTransactionStructureException
     * @throws PhabricatorApplicationTransactionValidationException
     * @throws PhabricatorApplicationTransactionWarningException
     * @throws PhutilInvalidStateException
     * @throws PhutilJSONParserException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws AphrontObjectMissingQueryException
     * @throws AphrontQueryException
     * @throws Throwable
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws IntegrityException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');
        $action = $request->getURIData('action');

        $rule = HeraldRule::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$rule) {
            return new Aphront404Response();
        }

        if ($rule->isGlobalRule()) {
            $this->requireApplicationCapability(
                HeraldManageGlobalRulesCapability::CAPABILITY);
        }

        $view_uri = '/' . $rule->getMonogram();

        $is_disable = ($action === 'disable');

        if ($request->isFormPost()) {
            $xaction = (new HeraldRuleTransaction())
                ->setTransactionType(HeraldRuleDisableTransaction::TRANSACTIONTYPE)
                ->setNewValue($is_disable);

            (new HeraldRuleEditor())
                ->setActor($viewer)
                ->setContinueOnNoEffect(true)
                ->setContentSourceFromRequest($request)
                ->applyTransactions($rule, array($xaction));

            return (new AphrontRedirectResponse())->setURI($view_uri);
        }

        if ($is_disable) {
            $title = pht('Really disable this rule?');
            $body = pht('This rule will no longer activate.');
            $button = pht('Disable Rule');
        } else {
            $title = pht('Really enable this rule?');
            $body = pht('This rule will become active again.');
            $button = pht('Enable Rule');
        }

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->setTitle($title)
            ->appendChild($body)
            ->addSubmitButton($button)
            ->addCancelButton($view_uri);

        return (new AphrontDialogResponse())->setDialog($dialog);
    }

}

<?php

namespace orangins\modules\file\actions;

use orangins\lib\response\Aphront403Response;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\modules\file\editors\PhabricatorFileEditor;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\models\PhabricatorFileTransaction;
use orangins\modules\file\xaction\PhabricatorFileDeleteTransaction;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;

/**
 * Class PhabricatorFileDeleteAction
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileDeleteAction extends PhabricatorFileAction
{

    /**
     * @return Aphront403Response|AphrontRedirectResponse|\orangins\lib\view\AphrontDialogView|Aphront404Response
     * @throws \PhutilInvalidStateException
     * @throws \PhutilJSONParserException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilTypeExtraParametersException
     * @throws \PhutilTypeMissingParametersException
     * @throws \ReflectionException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionStructureException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionValidationException
     * @throws \orangins\modules\transactions\exception\PhabricatorApplicationTransactionWarningException
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $id = $request->getURIData('id');

        $file = PhabricatorFile::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->withIsDeleted(false)
            ->requireCapabilities(
                array(
                    PhabricatorPolicyCapability::CAN_VIEW,
                    PhabricatorPolicyCapability::CAN_EDIT,
                ))
            ->executeOne();
        if (!$file) {
            return new Aphront404Response();
        }

        if (($viewer->getPHID() != $file->getAuthorPHID()) &&
            (!$viewer->getIsAdmin())) {
            return new Aphront403Response();
        }

        if ($request->isFormPost()) {
            $xactions = array();

            $xactions[] = (new PhabricatorFileTransaction())
                ->setTransactionType(PhabricatorFileDeleteTransaction::TRANSACTIONTYPE)
                ->setNewValue(true);

            (new PhabricatorFileEditor())
                ->setActor($viewer)
                ->setContentSourceFromRequest($request)
                ->setContinueOnNoEffect(true)
                ->setContinueOnMissingFields(true)
                ->applyTransactions($file, $xactions);

            return (new AphrontRedirectResponse())->setURI('/file/');
        }

        return $this->newDialog()
            ->setTitle(\Yii::t("app", 'Really delete file?'))
            ->appendChild(hsprintf(
                '<p>%s</p>',
                \Yii::t("app",
                    'Permanently delete "%s"? This action can not be undone.',
                    $file->getName())))
            ->addSubmitButton(\Yii::t("app", 'Delete'))
            ->addCancelButton($file->getInfoURI());
    }
}

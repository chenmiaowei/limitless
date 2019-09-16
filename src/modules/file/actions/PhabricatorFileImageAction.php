<?php

namespace orangins\modules\file\actions;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontFileResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\people\models\PhabricatorUser;
use PhutilInvalidStateException;
use PhutilSafeHTML;
use PhutilURI;

/**
 * Class PhabricatorFileDataController
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileImageAction extends PhabricatorFileAction
{

    /**
     * @var
     */
    private $url;

    /**
     * @return AphrontResponse|\orangins\lib\view\AphrontDialogView
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $this->url = $request->getURIData('url');

        return $this->newDialog()
            ->addClass("wmin-600")
            ->appendChild($img[] = (new PHUIButtonView())
                ->setTag("img")
                ->setWorkflow(true)
                ->setExtraTagAttributes(['src' => $this->url,'width' => '640px']))
            ->addCancelButton(\Yii::t("app", "Cancel"));

    }
}

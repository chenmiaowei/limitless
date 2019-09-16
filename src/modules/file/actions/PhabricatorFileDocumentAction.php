<?php

namespace orangins\modules\file\actions;

use orangins\modules\file\document\PhabricatorDocumentRef;
use orangins\modules\file\document\render\PhabricatorFileDocumentRenderingEngine;
use orangins\modules\file\models\PhabricatorFile;

/**
 * Class PhabricatorFileDocumentAction
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileDocumentAction
    extends PhabricatorFileAction
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
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $engine = (new PhabricatorFileDocumentRenderingEngine())
            ->setRequest($request)
            ->setAction($this);

        $viewer = $request->getViewer();

        $file_phid = $request->getURIData('phid');

        $file = PhabricatorFile::find()
            ->setViewer($viewer)
            ->withPHIDs(array($file_phid))
            ->executeOne();
        if (!$file) {
            return $engine->newErrorResponse(
                \Yii::t("app",
                    'This file ("%s") does not exist or could not be loaded.',
                    $file_phid));
        }

        $ref = (new PhabricatorDocumentRef())
            ->setFile($file);

        return $engine->newRenderResponse($ref);
    }

}

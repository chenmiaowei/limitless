<?php

namespace orangins\modules\file\document\render;

use orangins\lib\view\phui\PHUICrumbsView;
use orangins\modules\file\document\PhabricatorDocumentEngine;
use orangins\modules\file\document\PhabricatorDocumentRef;
use PhutilMethodNotImplementedException;

/**
 * Class PhabricatorFileDocumentRenderingEngine
 * @package orangins\modules\file\document\render
 * @author 陈妙威
 */
final class PhabricatorFileDocumentRenderingEngine
    extends PhabricatorDocumentRenderingEngine
{

    /**
     * @param PhabricatorDocumentRef $ref
     * @param PhabricatorDocumentEngine $engine
     * @return mixed|string
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function newRefViewURI(
        PhabricatorDocumentRef $ref,
        PhabricatorDocumentEngine $engine)
    {

        $file = $ref->getFile();
        $engine_key = $engine->getDocumentEngineKey();

        return urisprintf(
            '/file/view/%d/%s/',
            $file->getID(),
            $engine_key);
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @param PhabricatorDocumentEngine $engine
     * @return mixed|string
     * @throws PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function newRefRenderURI(
        PhabricatorDocumentRef $ref,
        PhabricatorDocumentEngine $engine)
    {
        $file = $ref->getFile();
        if (!$file) {
            throw new PhutilMethodNotImplementedException();
        }

        $engine_key = $engine->getDocumentEngineKey();
        $file_phid = $file->getPHID();

        return \Yii::$app->urlManager->createAbsoluteUrl(['/file/index/document',
            'engineKey' => $engine_key,
            'phid' => $file_phid
        ]);
    }

    /**
     * @param PHUICrumbsView $crumbs
     * @param PhabricatorDocumentRef|null $ref
     * @author 陈妙威
     */
    protected function addApplicationCrumbs(
        PHUICrumbsView $crumbs,
        PhabricatorDocumentRef $ref = null)
    {

        if ($ref) {
            $file = $ref->getFile();
            if ($file) {
                $crumbs->addTextCrumb($file->getMonogram(), $file->getInfoURI());
            }
        }

    }

}

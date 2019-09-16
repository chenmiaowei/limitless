<?php

namespace orangins\modules\file\document;

use orangins\lib\markup\view\PHUIRemarkupView;

/**
 * Class PhabricatorRemarkupDocumentEngine
 * @package orangins\modules\file\document
 * @author 陈妙威
 */
final class PhabricatorRemarkupDocumentEngine
    extends PhabricatorDocumentEngine
{

    /**
     *
     */
    const ENGINEKEY = 'remarkup';

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed|string
     * @author 陈妙威
     */
    public function getViewAsLabel(PhabricatorDocumentRef $ref)
    {
        return \Yii::t("app",'View as Remarkup');
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return string
     * @author 陈妙威
     */
    protected function getDocumentIconIcon(PhabricatorDocumentRef $ref)
    {
        return 'fa-file-text-o';
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return int
     * @author 陈妙威
     */
    protected function getContentScore(PhabricatorDocumentRef $ref)
    {
        $name = $ref->getName();
        if (preg_match('/\\.remarkup\z/i', $name)) {
            return 2000;
        }

        return 500;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool|mixed
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function canRenderDocumentType(PhabricatorDocumentRef $ref)
    {
        return $ref->isProbablyText();
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed|\PhutilSafeHTML
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function newDocumentContent(PhabricatorDocumentRef $ref)
    {
        $viewer = $this->getViewer();

        $content = $ref->loadData();
        $content = phutil_utf8ize($content);

        $remarkup = new PHUIRemarkupView($viewer, $content);

        $container = phutil_tag(
            'div',
            array(
                'class' => 'document-engine-remarkup',
            ),
            $remarkup);

        return $container;
    }

}

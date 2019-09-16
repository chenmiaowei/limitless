<?php

namespace orangins\modules\file\document;

/**
 * Class PhabricatorVoidDocumentEngine
 * @package orangins\modules\file\document
 * @author 陈妙威
 */
final class PhabricatorVoidDocumentEngine
    extends PhabricatorDocumentEngine
{

    /**
     *
     */
    const ENGINEKEY = 'void';

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed|null
     * @author 陈妙威
     */
    public function getViewAsLabel(PhabricatorDocumentRef $ref)
    {
        return null;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return string
     * @author 陈妙威
     */
    protected function getDocumentIconIcon(PhabricatorDocumentRef $ref)
    {
        return 'fa-file';
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return int
     * @author 陈妙威
     */
    protected function getContentScore(PhabricatorDocumentRef $ref)
    {
        return 1000;
    }

    /**
     * @return float|int|null
     * @author 陈妙威
     */
    protected function getByteLengthLimit()
    {
        return null;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool|mixed
     * @author 陈妙威
     */
    protected function canRenderDocumentType(PhabricatorDocumentRef $ref)
    {
        return true;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed|\PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newDocumentContent(PhabricatorDocumentRef $ref)
    {
        $message = \Yii::t("app",
            'No document engine can render the contents of this file.');

        $container = phutil_tag(
            'div',
            array(
                'class' => 'document-engine-message',
            ),
            $message);

        return $container;
    }

}

<?php

namespace orangins\modules\file\document;

use orangins\lib\view\layout\PhabricatorFileLinkView;
use PhutilMethodNotImplementedException;

/**
 * Class PhabricatorPDFDocumentEngine
 * @package orangins\modules\file\document
 * @author 陈妙威
 */
final class PhabricatorPDFDocumentEngine
    extends PhabricatorDocumentEngine
{

    /**
     *
     */
    const ENGINEKEY = 'pdf';

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed|string
     * @author 陈妙威
     */
    public function getViewAsLabel(PhabricatorDocumentRef $ref)
    {
        return \Yii::t("app",'View as PDF');
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return string
     * @author 陈妙威
     */
    protected function getDocumentIconIcon(PhabricatorDocumentRef $ref)
    {
        return 'fa-file-pdf-o';
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool|mixed
     * @author 陈妙威
     */
    protected function canRenderDocumentType(PhabricatorDocumentRef $ref)
    {
        // Since we just render a link to the document anyway, we don't need to
        // check anything fancy in config to see if the MIME type is actually
        // viewable.

        return $ref->hasAnyMimeType(
            array(
                'application/pdf',
            ));
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed|\PhutilSafeHTML
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function newDocumentContent(PhabricatorDocumentRef $ref)
    {
        $viewer = $this->getViewer();

        $file = $ref->getFile();
        if ($file) {
            $source_uri = $file->getViewURI();
        } else {
            throw new PhutilMethodNotImplementedException();
        }

        $name = $ref->getName();
        $length = $ref->getByteLength();

        $link = (new PhabricatorFileLinkView())
            ->setViewer($viewer)
            ->setFileName($name)
            ->setFileViewURI($source_uri)
            ->setFileViewable(true)
            ->setFileSize(phutil_format_bytes($length));

        $container = phutil_tag(
            'div',
            array(
                'class' => 'document-engine-pdf',
            ),
            $link);

        return $container;
    }

}

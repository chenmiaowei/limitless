<?php

namespace orangins\modules\file\document;

use orangins\lib\env\PhabricatorEnv;
use PhutilMethodNotImplementedException;

/**
 * Class PhabricatorImageDocumentEngine
 * @package orangins\modules\file\document
 * @author 陈妙威
 */
final class PhabricatorImageDocumentEngine
    extends PhabricatorDocumentEngine
{

    /**
     *
     */
    const ENGINEKEY = 'image';

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed|string
     * @author 陈妙威
     */
    public function getViewAsLabel(PhabricatorDocumentRef $ref)
    {
        return \Yii::t("app",'View as Image');
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return string
     * @author 陈妙威
     */
    protected function getDocumentIconIcon(PhabricatorDocumentRef $ref)
    {
        return 'fa-file-image-o';
    }

    /**
     * @return float|int
     * @author 陈妙威
     */
    protected function getByteLengthLimit()
    {
        return (1024 * 1024 * 64);
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool|mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    protected function canRenderDocumentType(PhabricatorDocumentRef $ref)
    {
        $file = $ref->getFile();
        if ($file) {
            return $file->isViewableImage();
        }

        $viewable_types = PhabricatorEnv::getEnvConfig('files.viewable-mime-types');
        $viewable_types = array_keys($viewable_types);

        $image_types = PhabricatorEnv::getEnvConfig('files.image-mime-types');
        $image_types = array_keys($image_types);

        return
            $ref->hasAnyMimeType($viewable_types) &&
            $ref->hasAnyMimeType($image_types);
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed|\PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newDocumentContent(PhabricatorDocumentRef $ref)
    {
        $file = $ref->getFile();
        if ($file) {
            $source_uri = $file->getViewURI();
        } else {
            // We could use a "data:" URI here. It's not yet clear if or when we'll
            // have a ref but no backing file.
            throw new PhutilMethodNotImplementedException();
        }

        $image = phutil_tag(
            'img',
            array(
                'src' => $source_uri,
                'class' => 'w-100'
            ));

        $linked_image = phutil_tag(
            'a',
            array(
                'href' => $source_uri,
                'rel' => 'noreferrer',
            ),
            $image);

        $container = phutil_tag(
            'div',
            array(
                'class' => 'document-engine-image',
            ),
            $linked_image);

        return $container;
    }

}

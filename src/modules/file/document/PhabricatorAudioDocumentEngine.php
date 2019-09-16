<?php

namespace orangins\modules\file\document;

use orangins\lib\env\PhabricatorEnv;
use PhutilMethodNotImplementedException;

/**
 * Class PhabricatorAudioDocumentEngine
 * @package orangins\modules\file\document
 * @author 陈妙威
 */
final class PhabricatorAudioDocumentEngine
    extends PhabricatorDocumentEngine
{

    /**
     *
     */
    const ENGINEKEY = 'audio';

    /**
     * @param PhabricatorDocumentRef $ref
     * @return string
     * @author 陈妙威
     */
    public function getViewAsLabel(PhabricatorDocumentRef $ref)
    {
        return \Yii::t("app",'View as Audio');
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return string
     * @author 陈妙威
     */
    protected function getDocumentIconIcon(PhabricatorDocumentRef $ref)
    {
        return 'fa-file-sound-o';
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
     * @return bool
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    protected function canRenderDocumentType(PhabricatorDocumentRef $ref)
    {
        $file = $ref->getFile();
        if ($file) {
            return $file->isAudio();
        }

        $viewable_types = PhabricatorEnv::getEnvConfig('files.viewable-mime-types');
        $viewable_types = array_keys($viewable_types);

        $audio_types = PhabricatorEnv::getEnvConfig('files.audio-mime-types');
        $audio_types = array_keys($audio_types);

        return
            $ref->hasAnyMimeType($viewable_types) &&
            $ref->hasAnyMimeType($audio_types);
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    protected function newDocumentContent(PhabricatorDocumentRef $ref)
    {
        $file = $ref->getFile();
        if ($file) {
            $source_uri = $file->getViewURI();
        } else {
            throw new PhutilMethodNotImplementedException();
        }

        $mime_type = $ref->getMimeType();

        $audio = phutil_tag(
            'audio',
            array(
                'controls' => 'controls',
            ),
            phutil_tag(
                'source',
                array(
                    'src' => $source_uri,
                    'type' => $mime_type,
                )));

        $container = phutil_tag(
            'div',
            array(
                'class' => 'document-engine-audio',
            ),
            $audio);

        return $container;
    }

}

<?php

namespace orangins\modules\file\conduit;

use Exception;
use orangins\modules\conduit\protocol\ConduitAPIRequest;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\policy\constants\PhabricatorPolicies;

/**
 * Class FileUploadChunkConduitAPIMethod
 * @package orangins\modules\file\conduit
 * @author 陈妙威
 */
final class FileUploadChunkConduitAPIMethod
    extends FileConduitAPIMethod
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAPIMethodName()
    {
        return 'file.uploadchunk';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodDescription()
    {
        return pht('Upload a chunk of file data to the server.');
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function defineParamTypes()
    {
        return array(
            'filePHID' => 'phid',
            'byteStart' => 'int',
            'data' => 'string',
            'dataEncoding' => 'string',
        );
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function defineReturnType()
    {
        return 'void';
    }

    /**
     * @param ConduitAPIRequest $request
     * @return mixed|null
     * @throws \AphrontQueryException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function execute(ConduitAPIRequest $request)
    {
        $viewer = $request->getUser();

        $file_phid = $request->getValue('filePHID');
        $file = $this->loadFileByPHID($viewer, $file_phid);

        $start = $request->getValue('byteStart');

        $data = $request->getValue('data');
        $encoding = $request->getValue('dataEncoding');
        switch ($encoding) {
            case 'base64':
                $data = $this->decodeBase64($data);
                break;
            case null:
                break;
            default:
                throw new Exception(pht('Unsupported data encoding.'));
        }
        $length = strlen($data);

        $chunk = $this->loadFileChunkForUpload(
            $viewer,
            $file,
            $start,
            $start + $length);

        // If this is the initial chunk, leave the MIME type unset so we detect
        // it and can update the parent file. If this is any other chunk, it has
        // no meaningful MIME type. Provide a default type so we can avoid writing
        // it to disk to perform MIME type detection.
        if (!$start) {
            $mime_type = null;
        } else {
            $mime_type = 'application/octet-stream';
        }

        $params = array(
            'name' => $file->getMonogram() . '.chunk-' . $chunk->getID(),
            'viewPolicy' => PhabricatorPolicies::POLICY_NOONE,
            'chunk' => true,
        );

        if ($mime_type !== null) {
            $params['mime-type'] = 'application/octet-stream';
        }

        // NOTE: These files have a view policy which prevents normal access. They
        // are only accessed through the storage engine.
        $chunk_data = PhabricatorFile::newFromFileData(
            $data,
            $params);

        $chunk->setDataFilePHID($chunk_data->getPHID())->save();

        $needs_update = false;

        $missing = $this->loadAnyMissingChunk($viewer, $file);
        if (!$missing) {
            $file->setIsPartial(0);
            $needs_update = true;
        }

        if (!$start) {
            $file->setMimeType($chunk_data->getMimeType());
            $needs_update = true;
        }

        if ($needs_update) {
            $file->save();
        }

        return null;
    }

}

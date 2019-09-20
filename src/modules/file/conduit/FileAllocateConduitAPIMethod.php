<?php

namespace orangins\modules\file\conduit;

use orangins\modules\conduit\protocol\ConduitAPIRequest;
use orangins\modules\file\engine\PhabricatorChunkedFileStorageEngine;
use orangins\modules\file\engine\PhabricatorFileStorageEngine;
use orangins\modules\file\models\PhabricatorFile;

/**
 * Class FileAllocateConduitAPIMethod
 * @package orangins\modules\file\conduit
 * @author 陈妙威
 */
final class FileAllocateConduitAPIMethod
    extends FileConduitAPIMethod
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAPIMethodName()
    {
        return 'file.allocate';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodDescription()
    {
        return pht('Prepare to upload a file.');
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function defineParamTypes()
    {
        return array(
            'name' => 'string',
            'contentLength' => 'int',
            'contentHash' => 'optional string',
            'viewPolicy' => 'optional string',
            'deleteAfterEpoch' => 'optional int',
        );
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function defineReturnType()
    {
        return 'map<string, wild>';
    }

    /**
     * @param ConduitAPIRequest $request
     * @return array|mixed
     * @throws \Exception
     * @author 陈妙威
     */
    protected function execute(ConduitAPIRequest $request)
    {
        $viewer = $request->getUser();

        $hash = $request->getValue('contentHash');
        $name = $request->getValue('name');
        $view_policy = $request->getValue('viewPolicy');
        $length = $request->getValue('contentLength');

        $properties = array(
            'name' => $name,
            'authorPHID' => $viewer->getPHID(),
            'isExplicitUpload' => true,
        );

        if ($view_policy !== null) {
            $properties['viewPolicy'] = $view_policy;
        }

        $ttl = $request->getValue('deleteAfterEpoch');
        if ($ttl) {
            $properties['ttl.absolute'] = $ttl;
        }

        $file = null;
        if ($hash !== null) {
            $file = PhabricatorFile::newFileFromContentHash(
                $hash,
                $properties);
        }

        if ($hash !== null && !$file) {
            $chunked_hash = PhabricatorChunkedFileStorageEngine::getChunkedHash(
                $viewer,
                $hash);
            $file = PhabricatorFile::find()
                ->setViewer($viewer)
                ->withContentHashes(array($chunked_hash))
                ->executeOne();
        }

        if (strlen($name) && !$hash && !$file) {
            if ($length > PhabricatorFileStorageEngine::getChunkThreshold()) {
                // If we don't have a hash, but this file is large enough to store in
                // chunks and thus may be resumable, try to find a partially uploaded
                // file by the same author with the same name and same length. This
                // allows us to resume uploads in Javascript where we can't efficiently
                // compute file hashes.
                $file = PhabricatorFile::find()
                    ->setViewer($viewer)
                    ->withAuthorPHIDs(array($viewer->getPHID()))
                    ->withNames(array($name))
                    ->withLengthBetween($length, $length)
                    ->withIsPartial(true)
                    ->setLimit(1)
                    ->executeOne();
            }
        }

        if ($file) {
            return array(
                'upload' => (bool)$file->getIsPartial(),
                'filePHID' => $file->getPHID(),
            );
        }

        // If there are any non-chunk engines which this file can fit into,
        // just tell the client to upload the file.
        $engines = PhabricatorFileStorageEngine::loadStorageEngines($length);
        if ($engines) {
            return array(
                'upload' => true,
                'filePHID' => null,
            );
        }

        // Otherwise, this is a large file and we want to perform a chunked
        // upload if we have a chunk engine available.
        $chunk_engines = PhabricatorFileStorageEngine::loadWritableChunkEngines();
        if ($chunk_engines) {
            $chunk_properties = $properties;

            if ($hash !== null) {
                $chunk_properties += array(
                    'chunkedHash' => $chunked_hash,
                );
            }

            /** @var PhabricatorChunkedFileStorageEngine $chunk_engine */
            $chunk_engine = head($chunk_engines);
            $file = $chunk_engine->allocateChunks($length, $chunk_properties);

            return array(
                'upload' => true,
                'filePHID' => $file->getPHID(),
            );
        }

        // None of the storage engines can accept this file.
        if (PhabricatorFileStorageEngine::loadWritableEngines()) {
            $error = pht(
                'Unable to upload file: this file is too large for any ' .
                'configured storage engine.');
        } else {
            $error = pht(
                'Unable to upload file: the server is not configured with any ' .
                'writable storage engines.');
        }

        return array(
            'upload' => false,
            'filePHID' => null,
            'error' => $error,
        );
    }

}

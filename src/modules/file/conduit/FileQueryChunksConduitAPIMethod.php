<?php

namespace orangins\modules\file\conduit;

use orangins\modules\conduit\protocol\ConduitAPIRequest;

/**
 * Class FileQueryChunksConduitAPIMethod
 * @package orangins\modules\file\conduit
 * @author 陈妙威
 */
final class FileQueryChunksConduitAPIMethod
    extends FileConduitAPIMethod
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAPIMethodName()
    {
        return 'file.querychunks';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodDescription()
    {
        return pht('Get information about file chunks.');
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function defineParamTypes()
    {
        return array(
            'filePHID' => 'phid',
        );
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function defineReturnType()
    {
        return 'list<wild>';
    }

    /**
     * @param ConduitAPIRequest $request
     * @return array|mixed
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function execute(ConduitAPIRequest $request)
    {
        $viewer = $request->getUser();

        $file_phid = $request->getValue('filePHID');
        $file = $this->loadFileByPHID($viewer, $file_phid);
        $chunks = $this->loadFileChunks($viewer, $file);

        $results = array();
        foreach ($chunks as $chunk) {
            $results[] = array(
                'byteStart' => $chunk->getByteStart(),
                'byteEnd' => $chunk->getByteEnd(),
                'complete' => (bool)$chunk->getDataFilePHID(),
            );
        }

        return $results;
    }

}

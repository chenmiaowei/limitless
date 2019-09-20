<?php

namespace orangins\modules\file\conduit;

use orangins\lib\env\PhabricatorEnv;
use orangins\modules\conduit\protocol\ConduitAPIRequest;
use orangins\modules\conduit\protocol\exception\ConduitException;
use orangins\modules\file\models\PhabricatorFile;

/**
 * Class FileInfoConduitAPIMethod
 * @package orangins\modules\file\conduit
 * @author 陈妙威
 */
final class FileInfoConduitAPIMethod extends FileConduitAPIMethod
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAPIMethodName()
    {
        return 'file.info';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodDescription()
    {
        return pht('Get information about a file.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodStatus()
    {
        return self::METHOD_STATUS_FROZEN;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getMethodStatusDescription()
    {
        return pht(
            'This method is frozen and will eventually be deprecated. New code ' .
            'should use "file.search" instead.');
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function defineParamTypes()
    {
        return array(
            'phid' => 'optional phid',
            'id' => 'optional id',
        );
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function defineReturnType()
    {
        return 'nonempty dict';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function defineErrorTypes()
    {
        return array(
            'ERR-NOT-FOUND' => pht('No such file exists.'),
        );
    }

    /**
     * @param ConduitAPIRequest $request
     * @return array|mixed
     * @throws ConduitException
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function execute(ConduitAPIRequest $request)
    {
        $phid = $request->getValue('phid');
        $id = $request->getValue('id');

        $query = PhabricatorFile::find()
            ->setViewer($request->getUser());
        if ($id) {
            $query->withIDs(array($id));
        } else {
            $query->withPHIDs(array($phid));
        }

        $file = $query->executeOne();

        if (!$file) {
            throw new ConduitException('ERR-NOT-FOUND');
        }

        $uri = $file->getInfoURI();

        return array(
            'id' => $file->getID(),
            'phid' => $file->getPHID(),
            'objectName' => 'F' . $file->getID(),
            'name' => $file->getName(),
            'mimeType' => $file->getMimeType(),
            'byteSize' => $file->getByteSize(),
            'authorPHID' => $file->getAuthorPHID(),
            'dateCreated' => $file->getDateCreated(),
            'dateModified' => $file->getDateModified(),
            'uri' => PhabricatorEnv::getProductionURI($uri),
        );
    }

}

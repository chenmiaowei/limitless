<?php

namespace orangins\modules\file\conduit;

use orangins\modules\conduit\protocol\ConduitAPIRequest;
use orangins\modules\conduit\protocol\exception\ConduitException;
use orangins\modules\file\models\PhabricatorFile;

/**
 * Class FileDownloadConduitAPIMethod
 * @package orangins\modules\file\conduit
 * @author 陈妙威
 */
final class FileDownloadConduitAPIMethod extends FileConduitAPIMethod
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getAPIMethodName()
    {
        return 'file.download';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getMethodDescription()
    {
        return pht('Download a file from the server.');
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function defineParamTypes()
    {
        return array(
            'phid' => 'required phid',
        );
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function defineReturnType()
    {
        return 'nonempty base64-bytes';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function defineErrorTypes()
    {
        return array(
            'ERR-BAD-PHID' => pht('No such file exists.'),
        );
    }

    /**
     * @param ConduitAPIRequest $request
     * @return mixed|string
     * @throws ConduitException
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function execute(ConduitAPIRequest $request)
    {
        $phid = $request->getValue('phid');

        $file = PhabricatorFile::find()
            ->setViewer($request->getUser())
            ->withPHIDs(array($phid))
            ->executeOne();
        if (!$file) {
            throw new ConduitException('ERR-BAD-PHID');
        }

        return base64_encode($file->loadFileData());
    }

}

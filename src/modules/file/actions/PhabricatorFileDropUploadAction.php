<?php

namespace orangins\modules\file\actions;

use Exception;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\modules\conduit\call\ConduitCall;
use orangins\modules\file\models\PhabricatorFile;

/**
 * Class PhabricatorFileDropUploadAction
 * @package orangins\modules\file\actions
 * @author 陈妙威
 */
final class PhabricatorFileDropUploadAction extends PhabricatorFileAction
{

    /**
     * @param $parameter_name
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowRestrictedParameter($parameter_name)
    {
        // Prevent false positives from file content when it is submitted via
        // drag-and-drop upload.
        return true;
    }

    /**
     * @return AphrontAjaxResponse
     * @throws \orangins\modules\conduit\protocol\exception\ConduitException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        // NOTE: Throws if valid CSRF token is not present in the request.
        $request->validateCSRF();

        $name = $request->getStr('name');
        $file_phid = $request->getStr('phid');
        // If there's no explicit view policy, make it very restrictive by default.
        // This is the correct policy for files dropped onto objects during
        // creation, comment and edit flows.
        $view_policy = $request->getStr('viewPolicy');
        if (!$view_policy) {
            $view_policy = $viewer->getPHID();
        }

        $is_chunks = $request->getBool('querychunks');
        if ($is_chunks) {
            $params = array(
                'filePHID' => $file_phid,
            );

            $result = (new ConduitCall('file.querychunks', $params))
                ->setUser($viewer)
                ->execute();

            return (new AphrontAjaxResponse())->setContent($result);
        }

        $is_allocate = $request->getBool('allocate');
        if ($is_allocate) {
            $params = array(
                'name' => $name,
                'contentLength' => $request->getInt('length'),
                'viewPolicy' => $view_policy,
            );

            $result = (new ConduitCall('file.allocate', $params))
                ->setUser($viewer)
                ->execute();

            $file_phid = $result['filePHID'];
            if ($file_phid) {
                $file = $this->loadFile($file_phid);
                $result += $file->getDragAndDropDictionary();
            }

            return (new AphrontAjaxResponse())->setContent($result);
        }

        // Read the raw request data. We're either doing a chunk upload or a
        // vanilla upload, so we need it.
        $data = PhabricatorStartup::getRawInput();

        $is_chunk_upload = $request->getBool('uploadchunk');
        if ($is_chunk_upload) {
            $params = array(
                'filePHID' => $file_phid,
                'byteStart' => $request->getInt('byteStart'),
                'data' => $data,
            );

            $result = (new ConduitCall('file.uploadchunk', $params))
                ->setUser($viewer)
                ->execute();

            $file = $this->loadFile($file_phid);
            if ($file->getIsPartial()) {
                $result = array();
            } else {
                $result = array(
                        'complete' => true,
                    ) + $file->getDragAndDropDictionary();
            }

            return (new AphrontAjaxResponse())->setContent($result);
        }

        $file = PhabricatorFile::newFromXHRUpload(
            $data,
            array(
                'name' => $request->getStr('name'),
                'authorPHID' => $viewer->getPHID(),
                'viewPolicy' => $view_policy,
                'isExplicitUpload' => true,
            ));

        $result = $file->getDragAndDropDictionary();
        return (new AphrontAjaxResponse())->setContent($result);
    }

    /**
     * @param $file_phid
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function loadFile($file_phid)
    {
        $viewer = $this->getViewer();

        $file = PhabricatorFile::find()
            ->setViewer($viewer)
            ->withPHIDs(array($file_phid))
            ->executeOne();
        if (!$file) {
            throw new Exception(\Yii::t("app", 'Failed to load file.'));
        }

        return $file;
    }

}

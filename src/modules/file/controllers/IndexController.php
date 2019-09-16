<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/24
 * Time: 10:06 AM
 */

namespace orangins\modules\file\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\file\actions\FileDeleteAction;
use orangins\modules\file\actions\FileEditAction;
use orangins\modules\file\actions\FileListAction;
use orangins\modules\file\actions\FileViewAction;
use orangins\modules\file\actions\PhabricatorFileComposeAction;
use orangins\modules\file\actions\PhabricatorFileDataAction;
use orangins\modules\file\actions\PhabricatorFileDeleteAction;
use orangins\modules\file\actions\PhabricatorFileDocumentAction;
use orangins\modules\file\actions\PhabricatorFileDropUploadAction;
use orangins\modules\file\actions\PhabricatorFileEditAction;
use orangins\modules\file\actions\PhabricatorFileIconSetSelectAction;
use orangins\modules\file\actions\PhabricatorFileImageAction;
use orangins\modules\file\actions\PhabricatorFileImageProxyAction;
use orangins\modules\file\actions\PhabricatorFileLightboxAction;
use orangins\modules\file\actions\PhabricatorFileListAction;
use orangins\modules\file\actions\PhabricatorFileTransformListAction;
use orangins\modules\file\actions\PhabricatorFileUploadAction;
use orangins\modules\file\actions\PhabricatorFileUploadDialogAction;
use orangins\modules\file\actions\PhabricatorFileViewAction;
use orangins\modules\file\editors\PhabricatorFileEditEngine;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\file\query\FileSearchEngine;
use orangins\modules\file\query\PhabricatorFileSearchEngine;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\widgets\components\LinkAphrontView;
use orangins\lib\view\AphrontView;
use Yii;
use yii\helpers\Json;
use yii\web\UploadedFile;

/**
 * Class IndexController
 * @package orangins\modules\file\controllers
 */
class IndexController extends PhabricatorController
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'view' => PhabricatorFileViewAction::class,
            'info' => PhabricatorFileViewAction::class,
            'upload' => PhabricatorFileUploadAction::class,
            'dropupload' => PhabricatorFileDropUploadAction::class,
            'compose' => PhabricatorFileComposeAction::class,
            'thread' => PhabricatorFileLightboxAction::class,
            'delete' => PhabricatorFileDeleteAction::class,
            'imageproxy' => PhabricatorFileImageProxyAction::class,
            'transforms' => PhabricatorFileTransformListAction::class,
            'uploaddialog' => PhabricatorFileUploadDialogAction::class,
            'query' => PhabricatorFileListAction::class,
            'edit' => PhabricatorFileEditAction::class,
            'iconset' => PhabricatorFileIconSetSelectAction::class,
            'document' => PhabricatorFileDocumentAction::class,
            'image' => PhabricatorFileImageAction::class,
        ];
    }
}
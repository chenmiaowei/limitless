<?php

namespace orangins\modules\file\exception;

use yii\base\UserException;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorFileUploadException
 * @package orangins\modules\file\exception
 * @author 陈妙威
 */
final class PhabricatorFileUploadException extends UserException
{

    public function __construct($code)
    {
        $map = array(
            UPLOAD_ERR_INI_SIZE => \Yii::t("app",
                "Uploaded file is too large: current limit is {0}. To adjust " .
                "this limit change '{1}' in php.ini.", [
                    ini_get('upload_max_filesize'),
                    'upload_max_filesize'
                ]),
            UPLOAD_ERR_FORM_SIZE => \Yii::t("app",
                'File is too large.'),
            UPLOAD_ERR_PARTIAL => \Yii::t("app",
                'File was only partially transferred, upload did not complete.'),
            UPLOAD_ERR_NO_FILE => \Yii::t("app",
                'No file was uploaded.'),
            UPLOAD_ERR_NO_TMP_DIR => \Yii::t("app",
                'Unable to write file: temporary directory does not exist.'),
            UPLOAD_ERR_CANT_WRITE => \Yii::t("app",
                'Unable to write file: failed to write to temporary directory.'),
            UPLOAD_ERR_EXTENSION => \Yii::t("app",
                'Unable to upload: a PHP extension stopped the upload.'),
        );

        $message = ArrayHelper::getValue(
            $map,
            $code,
            \Yii::t("app", 'Upload failed: unknown error (%s).', $code));
        parent::__construct($message, $code);
    }
}

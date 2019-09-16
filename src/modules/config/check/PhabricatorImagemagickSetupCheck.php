<?php

namespace orangins\modules\config\check;

use orangins\lib\env\PhabricatorEnv;
use orangins\modules\file\helpers\FileSystemHelper;

/**
 * Class PhabricatorImagemagickSetupCheck
 * @package orangins\modules\config\check
 * @author 陈妙威
 */
final class PhabricatorImagemagickSetupCheck extends PhabricatorSetupCheck
{

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getDefaultGroup()
    {
        return self::GROUP_OTHER;
    }

    /**
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function executeChecks()
    {
        $imagemagick = PhabricatorEnv::getEnvConfig('files.enable-imagemagick');
        if ($imagemagick) {
            if (!FileSystemHelper::binaryExists('convert')) {
                $message = \Yii::t("app",
                    "You have enabled Imagemagick in your config, but the '%s' " .
                    "binary is not in the webserver's %s. Disable imagemagick " .
                    "or make it available to the webserver.",
                    'convert',
                    '$PATH');

                $this->newIssue('files.enable-imagemagick')
                    ->setName(\Yii::t("app",
                        "'%s' binary not found or Imagemagick is not installed.", 'convert'))
                    ->setMessage($message)
                    ->addRelatedPhabricatorConfig('files.enable-imagemagick')
                    ->addPhabricatorConfig('environment.append-paths');
            }
        }
    }
}

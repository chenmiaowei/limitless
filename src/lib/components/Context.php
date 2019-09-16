<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/21
 * Time: 12:00 PM
 */

namespace orangins\lib\components;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\PhabricatorApplication;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;

/**
 * Class Config
 * @package orangins\modules\config\components
 */
class Context extends Component implements BootstrapInterface
{
    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param Application $app the application currently running
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     */
    public function bootstrap($app)
    {
        $allApplications = PhabricatorApplication::getAllApplicationsWithShortNameKey();
        foreach ($allApplications as $applicationObject) {
            if ($applicationObject instanceof BootstrapInterface) {
                $applicationObject->bootstrap($app);
            }
            $app->setModule($applicationObject->id, $applicationObject);
        }
        PhabricatorEnv::initializeWebEnvironment();
    }
}
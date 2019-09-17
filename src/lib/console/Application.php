<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/9/17
 * Time: 12:05 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\lib\console;

use orangins\lib\env\PhabricatorEnv;

/**
 * Class Application
 * @package orangins\lib\web
 * @author 陈妙威
 */
class Application extends \yii\console\Application
{
    /**
     * @var string
     */
    public $configPath;

    /**
     * @var bool
     */
    public $configOptional = false;

    /**
     * Application constructor.
     * @param array $config
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        PhabricatorEnv::initializeScriptEnvironment($this->configOptional);
    }
}
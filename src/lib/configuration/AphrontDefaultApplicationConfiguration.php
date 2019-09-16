<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/2/19
 * Time: 10:42 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\configuration;

/**
 * Class AphrontDefaultApplicationConfiguration
 * @package orangins\lib\configuration
 * @author 陈妙威
 */
class AphrontDefaultApplicationConfiguration
{
    /**
     * @var
     */
    public $console;

    /**
     * @return mixed
     */
    public function getConsole()
    {
        return $this->console;
    }

    /**
     * @param mixed $console
     * @return self
     */
    public function setConsole($console)
    {
        $this->console = $console;
        return $this;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/25
 * Time: 11:45 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\events;

use orangins\modules\people\models\PhabricatorUser;
use Yii;
use Exception;
use yii\base\InvalidConfigException;

/**
 * Class Event
 * @package orangins\lib\events
 * @author 陈妙威
 */
class Event extends \yii\base\Event
{
    /**
     * @var PhabricatorUser
     */
    public $user;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return PhabricatorUser
     * @throws InvalidConfigException
     */
    public function getUser()
    {
        if (!$this->user) {
            throw new InvalidConfigException(Yii::t("app", "The \"user\" property of \"{0}\" must be configured.", [
                get_called_class()
            ]));

        }
        return $this->user;
    }

    /**
     * @param mixed $user
     * @return static
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }
}
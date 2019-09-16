<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/7/10
 * Time: 11:15 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\people\engineextension;


use orangins\lib\OranginsObject;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\modules\people\models\PhabricatorUser;
use PhutilClassMapQuery;

/**
 * Class PeopleCustomMainMenuBarExtension
 * @package orangins\modules\people\engineextension
 * @author 陈妙威
 */
abstract class PeopleCustomMainMenuBarExtension extends OranginsObject
{
    /**
     * @param PhabricatorUser $viewer
     * @param bool $isFullSession
     * @param $application
     * @return PHUIButtonView
     * @author 陈妙威
     */
    abstract public function newMainMenus($viewer, $isFullSession, $application);

    /**
     * @return PeopleCustomMainMenuBarExtension[]
     * @author 陈妙威
     */
    final public static function getAllExtensions()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(PeopleCustomMainMenuBarExtension::class)
            ->execute();
    }
}
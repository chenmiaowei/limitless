<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/16
 * Time: 2:58 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\userservice\servicetype;


class PhabricatorAPITimeUserServiceType extends PhabricatorUserServiceType
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return "API按时间计费服务";
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return "icon-database-time2";
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getKey()
    {
        return "api.time";
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/16
 * Time: 3:01 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\userservice\servicetype;


class PhabricatorAPIAmountUserServiceType extends PhabricatorUserServiceType
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getName()
    {
        return "API按按量计费服务";
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getIcon()
    {
        return "icon-seven-segment-8";
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getKey()
    {
        return "api.amount";
    }
}
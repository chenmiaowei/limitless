<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/6/11
 * Time: 1:39 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\cache\cachekey;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorRbacUserNodeCacheKey
 * @package orangins\modules\rbac\cachekey
 * @author 陈妙威
 */
abstract class PhabricatorCacheKey extends OranginsObject
{
    /**
     * @return string
     * @throws \ReflectionException
     * @author 陈妙威
     */
    final public function getSettingKey()
    {
        return $this->getPhobjectClassConstant('CACHEKEY');
    }
}
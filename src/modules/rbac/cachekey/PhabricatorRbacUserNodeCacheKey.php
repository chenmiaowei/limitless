<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/6/11
 * Time: 1:39 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\rbac\cachekey;


use orangins\modules\cache\cachekey\PhabricatorCacheKey;

/**
 * Class PhabricatorRbacUserNodeCacheKey
 * @package orangins\modules\rbac\cachekey
 * @author 陈妙威
 */
class PhabricatorRbacUserNodeCacheKey extends PhabricatorCacheKey
{
    const CACHEKEY = 'user.nodes';
}
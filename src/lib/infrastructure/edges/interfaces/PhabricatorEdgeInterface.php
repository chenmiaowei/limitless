<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/26
 * Time: 4:32 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\lib\infrastructure\edges\interfaces;

/**
 * Interface PhabricatorEdgeInterface
 * @package orangins\lib\infrastructure\edges\query
 */
interface PhabricatorEdgeInterface
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function edgeBaseTableName();
}
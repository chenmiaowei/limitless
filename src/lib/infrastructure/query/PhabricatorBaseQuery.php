<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/5/23
 * Time: 5:07 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\infrastructure\query;

use yii\db\Query;

/**
 * Class PhabricatorBaseQuery
 * @package orangins\lib\infrastructure\query
 * @author 陈妙威
 */
class PhabricatorBaseQuery extends Query
{
    private $viewer;

    /**
     * @return mixed
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param mixed $viewer
     * @return self
     */
    public function setViewer($viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function execute()
    {
        return [];
    }
}
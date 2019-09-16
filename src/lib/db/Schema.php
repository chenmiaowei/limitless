<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/3/28
 * Time: 1:06 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\db;

class Schema extends \yii\db\mysql\Schema
{
    /**
     * Creates a query builder for the database.
     * This method may be overridden by child classes to create a DBMS-specific query builder.
     * @return QueryBuilder query builder instance
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this->db);
    }
}
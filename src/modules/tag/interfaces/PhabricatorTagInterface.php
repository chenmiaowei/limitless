<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/3
 * Time: 5:24 PM
 * Email: chenmiaowei0914@gmail.com
 */
namespace orangins\modules\tag\interfaces;

use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;

/**
 * Interface PhabricatorTagInterface
 * @package orangins\modules\tag\interfaces
 */
interface PhabricatorTagInterface
{
    /**
     * @return PhabricatorTypeaheadDatasource
     * @author 陈妙威
     */
    public function getDatasource();
}
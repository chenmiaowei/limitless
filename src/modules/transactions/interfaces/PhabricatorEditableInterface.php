<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/6
 * Time: 8:38 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\transactions\interfaces;


/**
 * Interface PhabricatorEditableInterface
 * @package orangins\modules\transactions\interfaces
 */
interface PhabricatorEditableInterface
{
    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getMonogram();

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getURI();

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getInfoURI();
}
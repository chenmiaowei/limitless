<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019-11-17
 * Time: 12:08
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\infrastructure\customfield\field;


use Yii;

/**
 * Class PhabricatorGeneralCustomFieldGroup
 * @package orangins\lib\infrastructure\customfield\field
 * @author 陈妙威
 */
class PhabricatorGeneralCustomFieldGroup extends PhabricatorCustomFieldGroup
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function getKey()
    {
        return 'General';
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return Yii::t("app", "General");
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSortOrder()
    {
        return 0;
    }
}
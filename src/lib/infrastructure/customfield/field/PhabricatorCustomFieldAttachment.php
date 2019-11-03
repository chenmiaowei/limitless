<?php

namespace orangins\lib\infrastructure\customfield\field;

use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldNotAttachedException;
use orangins\lib\OranginsObject;
use Yii;

/**
 * Convenience class which simplifies the implementation of
 * @{interface:PhabricatorCustomFieldInterface} by obscuring the details of how
 * custom fields are stored.
 *
 * Generally, you should not use this class directly. It is used by
 * @{class:PhabricatorCustomField} to manage field storage on objects.
 */
final class PhabricatorCustomFieldAttachment extends OranginsObject
{

    /**
     * @var PhabricatorCustomFieldList[]
     */
    private $lists = array();

    /**
     * @param $role
     * @param PhabricatorCustomFieldList $list
     * @return $this
     * @author 陈妙威
     */
    public function addCustomFieldList($role, PhabricatorCustomFieldList $list)
    {
        $this->lists[$role] = $list;
        return $this;
    }

    /**
     * @param $role
     * @return mixed
     * @throws PhabricatorCustomFieldNotAttachedException
     * @author 陈妙威
     */
    public function getCustomFieldList($role)
    {
        if (empty($this->lists[$role])) {
            throw new PhabricatorCustomFieldNotAttachedException(
                Yii::t("app", "Role list '{0}' is not available!", [
                        $role
                    ]));
        }
        return $this->lists[$role];
    }

}

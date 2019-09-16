<?php

namespace orangins\modules\people\customfield;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorStandardCustomFieldInterface;
use orangins\lib\infrastructure\standard\PhabricatorStandardCustomField;

/**
 * Class PhabricatorUserConfiguredCustomField
 * @package orangins\modules\people\customfield
 * @author 陈妙威
 */
final class PhabricatorUserConfiguredCustomField
    extends PhabricatorUserCustomField
    implements PhabricatorStandardCustomFieldInterface
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getStandardCustomFieldNamespace()
    {
        return 'user';
    }

    /**
     * @param $object
     * @return \orangins\lib\infrastructure\customfield\field\list
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function createFields($object)
    {
        return PhabricatorStandardCustomField::buildStandardFields(
            $this,
            PhabricatorEnv::getEnvConfig('user.custom-field-definitions'));
    }

    /**
     * @return \orangins\lib\infrastructure\customfield\field\PhabricatorCustomFieldStorage|PhabricatorUserConfiguredCustomFieldStorage
     * @author 陈妙威
     */
    public function newStorageObject()
    {
        return new PhabricatorUserConfiguredCustomFieldStorage();
    }

    /**
     * @return \orangins\lib\infrastructure\customfield\field\PhabricatorCustomFieldStringIndexStorage|PhabricatorUserCustomFieldStringIndex
     * @author 陈妙威
     */
    protected function newStringIndexStorage()
    {
        return new PhabricatorUserCustomFieldStringIndex();
    }

    /**
     * @return \orangins\lib\infrastructure\customfield\field\PhabricatorCustomFieldStringIndexStorage|PhabricatorUserCustomFieldNumericIndex
     * @author 陈妙威
     */
    protected function newNumericIndexStorage()
    {
        return new PhabricatorUserCustomFieldNumericIndex();
    }

}

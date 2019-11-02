<?php

namespace orangins\modules\people\customfield;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldNotProxyException;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorStandardCustomFieldInterface;
use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldStorage;
use orangins\lib\infrastructure\customfield\storage\PhabricatorCustomFieldStringIndexStorage;
use orangins\lib\infrastructure\customfield\standard\PhabricatorStandardCustomField;
use PhutilInvalidStateException;

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
     * @return array
     * @throws PhutilInvalidStateException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws PhabricatorCustomFieldNotProxyException
     * @throws Exception
     * @author 陈妙威
     */
    public function createFields($object)
    {
        return PhabricatorStandardCustomField::buildStandardFields(
            $this,
            PhabricatorEnv::getEnvConfig('user.custom-field-definitions'));
    }

    /**
     * @return PhabricatorCustomFieldStorage|PhabricatorUserConfiguredCustomFieldStorage
     * @author 陈妙威
     */
    public function newStorageObject()
    {
        return new PhabricatorUserConfiguredCustomFieldStorage();
    }

    /**
     * @return PhabricatorCustomFieldStringIndexStorage|PhabricatorUserCustomFieldStringIndex
     * @author 陈妙威
     */
    protected function newStringIndexStorage()
    {
        return new PhabricatorUserCustomFieldStringIndex();
    }

    /**
     * @return PhabricatorCustomFieldStringIndexStorage|PhabricatorUserCustomFieldNumericIndex
     * @author 陈妙威
     */
    protected function newNumericIndexStorage()
    {
        return new PhabricatorUserCustomFieldNumericIndex();
    }

}

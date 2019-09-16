<?php

namespace orangins\modules\people\searchfield;

use orangins\lib\request\AphrontRequest;
use orangins\modules\conduit\parametertype\ConduitUserListParameterType;
use orangins\modules\people\typeahead\PhabricatorPeopleUserFunctionDatasource;
use orangins\modules\search\field\PhabricatorSearchTokenizerField;

/**
 * Class PhabricatorUsersSearchField
 * @package orangins\modules\people\searchfield
 * @author 陈妙威
 */
final class PhabricatorUsersSearchField
    extends PhabricatorSearchTokenizerField
{

    /**
     * @return array|null
     * @author 陈妙威
     */
    protected function getDefaultValue()
    {
        return array();
    }

    /**
     * @param AphrontRequest $request
     * @param $key
     * @return array|mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function getValueFromRequest(AphrontRequest $request, $key)
    {
        return $this->getUsersFromRequest($request, $key);
    }

    /**
     * @return PhabricatorPeopleUserFunctionDatasource
     * @author 陈妙威
     */
    protected function newDatasource()
    {
        return new PhabricatorPeopleUserFunctionDatasource();
    }

    /**
     * @return ConduitUserListParameterType|null
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitUserListParameterType();
    }

}

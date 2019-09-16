<?php

namespace orangins\modules\search\field;

use orangins\lib\request\AphrontRequest;
use orangins\modules\conduit\parametertype\ConduitUserListParameterType;
use orangins\modules\metamta\typeahead\PhabricatorMetaMTAMailableFunctionDatasource;

/**
 * Class PhabricatorSearchSubscribersField
 * @package orangins\modules\search\field
 * @author 陈妙威
 */
final class PhabricatorSearchSubscribersField
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
        $allow_types = array(
//      PhabricatorProjectProjectPHIDType::TYPECONST,
//      PhabricatorOwnersPackagePHIDType::TYPECONST,
        );
        return $this->getUsersFromRequest($request, $key, $allow_types);
    }

    /**
     * @return mixed|PhabricatorMetaMTAMailableFunctionDatasource
     * @author 陈妙威
     */
    protected function newDatasource()
    {
        return new PhabricatorMetaMTAMailableFunctionDatasource();
    }

    /**
     * @return null|ConduitUserListParameterType
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        // TODO: Ideally, this should eventually be a "Subscribers" type which
        // accepts projects as well.
        return new ConduitUserListParameterType();
    }

}

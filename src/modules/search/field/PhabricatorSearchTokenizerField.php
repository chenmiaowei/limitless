<?php

namespace orangins\modules\search\field;

use orangins\lib\helpers\OranginsUtil;
use orangins\lib\request\AphrontRequest;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\phid\PhabricatorPeopleUserPHIDType;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\lib\view\form\control\AphrontFormTokenizerControl;

/**
 * Class PhabricatorSearchTokenizerField
 * @package orangins\modules\search\field
 * @author 陈妙威
 */
abstract class PhabricatorSearchTokenizerField extends PhabricatorSearchField
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
     * @author 陈妙威
     */
    protected function getValueFromRequest(AphrontRequest $request, $key)
    {
        return $this->getListFromRequest($request, $key);
    }

    /**
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function getValueForQuery($value)
    {
        return $this->newDatasource()
            ->setViewer($this->getViewer())
            ->evaluateTokens($value);
    }

    /**
     * @return AphrontFormTokenizerControl
     * @author 陈妙威
     */
    protected function newControl()
    {
        return (new AphrontFormTokenizerControl())
            ->setDatasource($this->newDatasource());
    }


    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function newDatasource();


    /**
     * @param AphrontRequest $request
     * @param $key
     * @param array $allow_types
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    protected function getUsersFromRequest(
        AphrontRequest $request,
        $key,
        array $allow_types = array())
    {
        $list = $this->getListFromRequest($request, $key);

        $phids = array();
        $names = array();
        $allow_types = array_fuse($allow_types);
        $user_type = PhabricatorPeopleUserPHIDType::TYPECONST;
        foreach ($list as $item) {
            $type = PhabricatorPHID::phid_get_type($item);
            if ($type == $user_type) {
                $phids[] = $item;
            } else if (isset($allow_types[$type])) {
                $phids[] = $item;
            } else {
                if (PhabricatorTypeaheadDatasource::isFunctionToken($item)) {
                    // If this is a function, pass it through unchanged; we'll evaluate
                    // it later.
                    $phids[] = $item;
                } else {
                    $names[] = $item;
                }
            }
        }

        if ($names) {
            /** @var PhabricatorUser[] $users */
            $users = PhabricatorUser::find()
                ->setViewer($this->getViewer())
                ->withUsernames($names)
                ->execute();
            foreach ($users as $user) {
                $phids[] = $user->getPHID();
            }
            $phids = array_unique($phids);
        }

        return $phids;
    }

}

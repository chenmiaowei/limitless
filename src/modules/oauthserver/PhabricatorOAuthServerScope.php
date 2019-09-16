<?php

namespace orangins\modules\oauthserver;

use orangins\lib\OranginsObject;

/**
 * Class PhabricatorOAuthServerScope
 * @package orangins\modules\oauthserver
 * @author 陈妙威
 */
final class PhabricatorOAuthServerScope extends OranginsObject
{

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getScopeMap()
    {
        return array();
    }

    /**
     * @param array $scope
     * @return array
     * @author 陈妙威
     */
    public static function filterScope(array $scope)
    {
        $valid_scopes = self::getScopeMap();

        foreach ($scope as $key => $scope_item) {
            if (!isset($valid_scopes[$scope_item])) {
                unset($scope[$key]);
            }
        }

        return $scope;
    }

}

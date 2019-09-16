<?php
namespace orangins\modules\phid\resolver;

use orangins\modules\people\phid\PhabricatorPeopleUserPHIDType;
use orangins\modules\phid\query\PhabricatorObjectQuery;

/**
 * Class PhabricatorUserPHIDResolver
 * @package orangins\modules\phid\resolver
 * @author 陈妙威
 */
final class PhabricatorUserPHIDResolver extends PhabricatorPHIDResolver {

    /**
     * @param array $names
     * @return array|mixed
     * @author 陈妙威
     */protected function getResolutionMap(array $names) {
    // Pick up the normalization and case rules from the PHID type query.

    foreach ($names as $key => $name) {
      $names[$key] = '@'.$name;
    }

    $query = (new PhabricatorObjectQuery())
      ->setViewer($this->getViewer());

    $users = (new PhabricatorPeopleUserPHIDType())
      ->loadNamedObjects($query, $names);

    $results = array();
    foreach ($users as $at_username => $user) {
      $results[substr($at_username, 1)] = $user->getPHID();
    }

    return $results;
  }
}

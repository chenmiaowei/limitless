<?php
namespace orangins\modules\config\type;

/**
 * Class PhabricatorStringListConfigType
 * @package orangins\modules\config\type
 * @author 陈妙威
 */
final class PhabricatorStringListConfigType
  extends PhabricatorTextListConfigType {

    /**
     *
     */
    const TYPEKEY = 'list<string>';

}

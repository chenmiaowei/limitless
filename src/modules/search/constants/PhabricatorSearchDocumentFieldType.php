<?php
namespace orangins\modules\search\constants;

use orangins\lib\OranginsObject;

final class PhabricatorSearchDocumentFieldType extends OranginsObject {

  const FIELD_TITLE         = 'titl';
  const FIELD_BODY          = 'body';
  const FIELD_COMMENT       = 'cmnt';
  const FIELD_ALL           = 'full';
  const FIELD_CORE          = 'core';

}

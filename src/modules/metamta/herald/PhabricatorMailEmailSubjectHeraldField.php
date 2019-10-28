<?php

namespace orangins\modules\metamta\herald;

/**
 * Class PhabricatorMailEmailSubjectHeraldField
 * @package orangins\modules\metamta\herald
 * @author 陈妙威
 */
final class PhabricatorMailEmailSubjectHeraldField
    extends PhabricatorMailEmailHeraldField
{

    /**
     *
     */
    const FIELDCONST = 'mail.message.subject';

    /**
     * @return string
     * @author 陈妙威
     */
    public function getHeraldFieldName()
    {
        return pht('Subject');
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getHeraldFieldValue($object)
    {
        return $object->getSubject();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getHeraldFieldStandardType()
    {
        return self::STANDARD_TEXT;
    }

}

<?php

namespace orangins\modules\file\format;

/**
 * Class PhabricatorFileRawStorageFormat
 * @package orangins\modules\file\format
 * @author 陈妙威
 */
final class PhabricatorFileRawStorageFormat extends PhabricatorFileStorageFormat
{

    /**
     *
     */
    const FORMATKEY = 'raw';

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getStorageFormatName()
    {
        return \Yii::t("app",'Raw Data');
    }

    /**
     * @param $raw_iterator
     * @return mixed
     * @author 陈妙威
     */
    public function newReadIterator($raw_iterator)
    {
        return $raw_iterator;
    }

    /**
     * @param $raw_iterator
     * @return mixed
     * @author 陈妙威
     */
    public function newWriteIterator($raw_iterator)
    {
        return $raw_iterator;
    }

}

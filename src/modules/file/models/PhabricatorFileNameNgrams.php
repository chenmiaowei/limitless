<?php

namespace orangins\modules\file\models;

use orangins\modules\search\ngrams\PhabricatorSearchNgrams;
use Yii;

/**
 * This is the model class for table "file_filename_ngrams".
 *
 */
class PhabricatorFileNameNgrams extends PhabricatorSearchNgrams
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'file_filename_ngrams';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getNgramKey()
    {
        return 'filename';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getColumnName()
    {
        return 'name';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return 'file';
    }
}

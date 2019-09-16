<?php

namespace orangins\lib\infrastructure\query;

use yii\db\ActiveRecord;

/**
 * Class PhabricatorQuery
 * @package orangins\lib\infrastructure\query
 * @see \yii\db\ActiveQuery
 * @author 陈妙威
 */
class PhabricatorQuery extends \yii\db\ActiveQuery
{

    /**
     * Constructor.
     * @param string $modelClass the model class associated with this query
     * @param array $config configurations to be applied to the newly created query object
     */
    public function __construct($modelClass = null, $config = [])
    {
        $this->modelClass = $modelClass;
        parent::__construct($modelClass, $config);
    }
    /**
     * @return array
     * @author 陈妙威
     */
    public function execute()
    {
        return [];
    }

    /**
     * @return string primary table name
     * @since 2.0.12
     * @throws \AphrontAccessDeniedQueryException
     */
    protected function getPrimaryTableName()
    {
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;
        if($modelClass === null) {
            throw new \AphrontAccessDeniedQueryException(\Yii::t("app", "The modelClass of '{0}' can not be null. Please use ::find() get query.", [
                get_called_class()
            ]));
        }
        return $modelClass::tableName();
    }
}
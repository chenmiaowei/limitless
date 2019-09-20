<?php

namespace orangins\modules\config\schema;

use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConfigServerSchema
 * @package orangins\modules\config\schema
 * @author 陈妙威
 */
final class PhabricatorConfigServerSchema
    extends PhabricatorConfigStorageSchema
{

    /**
     * @var
     */
    private $ref;
    /**
     * @var array
     */
    private $databases = array();

    /**
     * @param PhabricatorDatabaseRef $ref
     * @return $this
     * @author 陈妙威
     */
    public function setRef(PhabricatorDatabaseRef $ref)
    {
        $this->ref = $ref;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * @param PhabricatorConfigDatabaseSchema $database
     * @return $this
     * @author 陈妙威
     */
    public function addDatabase(PhabricatorConfigDatabaseSchema $database)
    {
        $key = $database->getName();
        if (isset($this->databases[$key])) {
            throw new Exception(
                \Yii::t("app", 'Trying to add duplicate database "%s"!', $key));
        }
        $this->databases[$key] = $database;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getDatabases()
    {
        return $this->databases;
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     */
    public function getDatabase($key)
    {
        return ArrayHelper::getValue($this->getDatabases(), $key);
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSubschemata()
    {
        return $this->getDatabases();
    }

    /**
     * @param PhabricatorConfigStorageSchema $expect
     * @return array|mixed
     * @author 陈妙威
     */
    protected function compareToSimilarSchema(
        PhabricatorConfigStorageSchema $expect)
    {
        return array();
    }

    /**
     * @return mixed|PhabricatorConfigServerSchema
     * @author 陈妙威
     */
    public function newEmptyClone()
    {
        $clone = clone $this;
        $clone->databases = array();
        return $clone;
    }

}

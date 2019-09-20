<?php

namespace orangins\modules\config\schema;

use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConfigDatabaseSchema
 * @package orangins\modules\config\schema
 * @author 陈妙威
 */
final class PhabricatorConfigDatabaseSchema
    extends PhabricatorConfigStorageSchema
{

    /**
     * @var
     */
    private $characterSet;
    /**
     * @var
     */
    private $collation;
    /**
     * @var array
     */
    private $tables = array();
    /**
     * @var
     */
    private $accessDenied;

    /**
     * @param PhabricatorConfigTableSchema $table
     * @return $this
     * @author 陈妙威
     */
    public function addTable(PhabricatorConfigTableSchema $table)
    {
        $key = $table->getName();
        if (isset($this->tables[$key])) {

            if ($key == 'application_application') {
                // NOTE: This is a terrible hack to allow Application subclasses to
                // extend LiskDAO so we can apply transactions to them.
                return $this;
            }

            throw new Exception(
                \Yii::t("app", 'Trying to add duplicate table "%s"!', $key));
        }
        $this->tables[$key] = $table;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     */
    public function getTable($key)
    {
        return ArrayHelper::getValue($this->tables, $key);
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    protected function getSubschemata()
    {
        return $this->getTables();
    }

    /**
     * @param PhabricatorConfigStorageSchema $expect
     * @return array|mixed
     * @author 陈妙威
     */
    protected function compareToSimilarSchema(
        PhabricatorConfigStorageSchema $expect)
    {

        $issues = array();
        if ($this->getAccessDenied()) {
            $issues[] = self::ISSUE_ACCESSDENIED;
        } else {
            if ($this->getCharacterSet() != $expect->getCharacterSet()) {
                $issues[] = self::ISSUE_CHARSET;
            }

            if ($this->getCollation() != $expect->getCollation()) {
                $issues[] = self::ISSUE_COLLATION;
            }
        }

        return $issues;
    }

    /**
     * @return mixed|PhabricatorConfigDatabaseSchema
     * @author 陈妙威
     */
    public function newEmptyClone()
    {
        $clone = clone $this;
        $clone->tables = array();
        return $clone;
    }

    /**
     * @param $collation
     * @return $this
     * @author 陈妙威
     */
    public function setCollation($collation)
    {
        $this->collation = $collation;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCollation()
    {
        return $this->collation;
    }

    /**
     * @param $character_set
     * @return $this
     * @author 陈妙威
     */
    public function setCharacterSet($character_set)
    {
        $this->characterSet = $character_set;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getCharacterSet()
    {
        return $this->characterSet;
    }

    /**
     * @param $access_denied
     * @return $this
     * @author 陈妙威
     */
    public function setAccessDenied($access_denied)
    {
        $this->accessDenied = $access_denied;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAccessDenied()
    {
        return $this->accessDenied;
    }

}

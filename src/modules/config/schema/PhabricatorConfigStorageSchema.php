<?php

namespace orangins\modules\config\schema;

use orangins\lib\OranginsObject;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConfigStorageSchema
 * @package orangins\modules\config\schema
 * @author 陈妙威
 */
abstract class PhabricatorConfigStorageSchema extends OranginsObject
{

    /**
     *
     */
    const ISSUE_MISSING = 'missing';
    /**
     *
     */
    const ISSUE_MISSINGKEY = 'missingkey';
    /**
     *
     */
    const ISSUE_SURPLUS = 'surplus';
    /**
     *
     */
    const ISSUE_SURPLUSKEY = 'surpluskey';
    /**
     *
     */
    const ISSUE_CHARSET = 'charset';
    /**
     *
     */
    const ISSUE_COLLATION = 'collation';
    /**
     *
     */
    const ISSUE_COLUMNTYPE = 'columntype';
    /**
     *
     */
    const ISSUE_NULLABLE = 'nullable';
    /**
     *
     */
    const ISSUE_KEYCOLUMNS = 'keycolumns';
    /**
     *
     */
    const ISSUE_UNIQUE = 'unique';
    /**
     *
     */
    const ISSUE_LONGKEY = 'longkey';
    /**
     *
     */
    const ISSUE_SUBWARN = 'subwarn';
    /**
     *
     */
    const ISSUE_SUBFAIL = 'subfail';
    /**
     *
     */
    const ISSUE_AUTOINCREMENT = 'autoincrement';
    /**
     *
     */
    const ISSUE_UNKNOWN = 'unknown';
    /**
     *
     */
    const ISSUE_ACCESSDENIED = 'accessdenied';
    /**
     *
     */
    const ISSUE_ENGINE = 'engine';

    /**
     *
     */
    const STATUS_OKAY = 'okay';
    /**
     *
     */
    const STATUS_WARN = 'warn';
    /**
     *
     */
    const STATUS_FAIL = 'fail';

    /**
     * @var array
     */
    private $issues = array();
    /**
     * @var
     */
    private $name;

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function newEmptyClone();

    /**
     * @param PhabricatorConfigStorageSchema $expect
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function compareToSimilarSchema(
        PhabricatorConfigStorageSchema $expect);

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function getSubschemata();

    /**
     * @param PhabricatorConfigStorageSchema $expect
     * @return mixed
     * @author 陈妙威
     */
    public function compareTo(PhabricatorConfigStorageSchema $expect)
    {
        if (get_class($expect) != get_class($this)) {
            throw new Exception(\Yii::t("app", 'Classes must match to compare schemata!'));
        }

        if ($this->getName() != $expect->getName()) {
            throw new Exception(\Yii::t("app", 'Names must match to compare schemata!'));
        }

        return $this->compareToSimilarSchema($expect);
    }

    /**
     * @param $name
     * @return $this
     * @author 陈妙威
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array $issues
     * @return $this
     * @author 陈妙威
     */
    public function setIssues(array $issues)
    {
        $this->issues = array_fuse($issues);
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getIssues()
    {
        $issues = $this->issues;

        foreach ($this->getSubschemata() as $sub) {
            switch ($sub->getStatus()) {
                case self::STATUS_WARN:
                    $issues[self::ISSUE_SUBWARN] = self::ISSUE_SUBWARN;
                    break;
                case self::STATUS_FAIL:
                    $issues[self::ISSUE_SUBFAIL] = self::ISSUE_SUBFAIL;
                    break;
            }
        }

        return $issues;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getLocalIssues()
    {
        return $this->issues;
    }

    /**
     * @param $issue
     * @return bool
     * @author 陈妙威
     */
    public function hasIssue($issue)
    {
        return (bool)ArrayHelper::getValue($this->getIssues(), $issue);
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getAllIssues()
    {
        $issues = $this->getIssues();
        foreach ($this->getSubschemata() as $sub) {
            $issues += $sub->getAllIssues();
        }
        return $issues;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getStatus()
    {
        $status = self::STATUS_OKAY;
        foreach ($this->getAllIssues() as $issue) {
            $issue_status = self::getIssueStatus($issue);
            $status = self::getStrongestStatus($status, $issue_status);
        }
        return $status;
    }

    /**
     * @param $issue
     * @return string
     * @author 陈妙威
     */
    public static function getIssueName($issue)
    {
        switch ($issue) {
            case self::ISSUE_MISSING:
                return \Yii::t("app", 'Missing');
            case self::ISSUE_MISSINGKEY:
                return \Yii::t("app", 'Missing Key');
            case self::ISSUE_SURPLUS:
                return \Yii::t("app", 'Surplus');
            case self::ISSUE_SURPLUSKEY:
                return \Yii::t("app", 'Surplus Key');
            case self::ISSUE_CHARSET:
                return \Yii::t("app", 'Better Character Set Available');
            case self::ISSUE_COLLATION:
                return \Yii::t("app", 'Better Collation Available');
            case self::ISSUE_COLUMNTYPE:
                return \Yii::t("app", 'Wrong Column Type');
            case self::ISSUE_NULLABLE:
                return \Yii::t("app", 'Wrong Nullable Setting');
            case self::ISSUE_KEYCOLUMNS:
                return \Yii::t("app", 'Key on Wrong Columns');
            case self::ISSUE_UNIQUE:
                return \Yii::t("app", 'Key has Wrong Uniqueness');
            case self::ISSUE_LONGKEY:
                return \Yii::t("app", 'Key is Too Long');
            case self::ISSUE_SUBWARN:
                return \Yii::t("app", 'Subschemata Have Warnings');
            case self::ISSUE_SUBFAIL:
                return \Yii::t("app", 'Subschemata Have Failures');
            case self::ISSUE_AUTOINCREMENT:
                return \Yii::t("app", 'Column has Wrong Autoincrement');
            case self::ISSUE_UNKNOWN:
                return \Yii::t("app", 'Column Has No Specification');
            case self::ISSUE_ACCESSDENIED:
                return \Yii::t("app", 'Access Denied');
            case self::ISSUE_ENGINE:
                return \Yii::t("app", 'Better Table Engine Available');
            default:
                throw new Exception(\Yii::t("app", 'Unknown schema issue "%s"!', $issue));
        }
    }

    /**
     * @param $issue
     * @return string
     * @author 陈妙威
     */
    public static function getIssueDescription($issue)
    {
        switch ($issue) {
            case self::ISSUE_MISSING:
                return \Yii::t("app", 'This schema is expected to exist, but does not.');
            case self::ISSUE_MISSINGKEY:
                return \Yii::t("app", 'This key is expected to exist, but does not.');
            case self::ISSUE_SURPLUS:
                return \Yii::t("app", 'This schema is not expected to exist.');
            case self::ISSUE_SURPLUSKEY:
                return \Yii::t("app", 'This key is not expected to exist.');
            case self::ISSUE_CHARSET:
                return \Yii::t("app", 'This schema can use a better character set.');
            case self::ISSUE_COLLATION:
                return \Yii::t("app", 'This schema can use a better collation.');
            case self::ISSUE_COLUMNTYPE:
                return \Yii::t("app", 'This schema can use a better column type.');
            case self::ISSUE_NULLABLE:
                return \Yii::t("app", 'This schema has the wrong nullable setting.');
            case self::ISSUE_KEYCOLUMNS:
                return \Yii::t("app", 'This key is on the wrong columns.');
            case self::ISSUE_UNIQUE:
                return \Yii::t("app", 'This key has the wrong uniqueness setting.');
            case self::ISSUE_LONGKEY:
                return \Yii::t("app", 'This key is too long for utf8mb4.');
            case self::ISSUE_SUBWARN:
                return \Yii::t("app", 'Subschemata have setup warnings.');
            case self::ISSUE_SUBFAIL:
                return \Yii::t("app", 'Subschemata have setup failures.');
            case self::ISSUE_AUTOINCREMENT:
                return \Yii::t("app", 'This column has the wrong autoincrement setting.');
            case self::ISSUE_UNKNOWN:
                return \Yii::t("app", 'This column is missing a type specification.');
            case self::ISSUE_ENGINE:
                return \Yii::t("app", 'This table can use a better table engine.');
            default:
                throw new Exception(\Yii::t("app", 'Unknown schema issue "%s"!', $issue));
        }
    }

    /**
     * @param $issue
     * @return string
     * @author 陈妙威
     */
    public static function getIssueStatus($issue)
    {
        switch ($issue) {
            case self::ISSUE_MISSING:
            case self::ISSUE_ACCESSDENIED:
            case self::ISSUE_SURPLUS:
            case self::ISSUE_NULLABLE:
            case self::ISSUE_SUBFAIL:
            case self::ISSUE_UNKNOWN:
                return self::STATUS_FAIL;
            case self::ISSUE_SUBWARN:
            case self::ISSUE_COLUMNTYPE:
            case self::ISSUE_CHARSET:
            case self::ISSUE_COLLATION:
            case self::ISSUE_MISSINGKEY:
            case self::ISSUE_SURPLUSKEY:
            case self::ISSUE_UNIQUE:
            case self::ISSUE_KEYCOLUMNS:
            case self::ISSUE_LONGKEY:
            case self::ISSUE_AUTOINCREMENT:
            case self::ISSUE_ENGINE:
                return self::STATUS_WARN;
            default:
                throw new Exception(\Yii::t("app", 'Unknown schema issue "%s"!', $issue));
        }
    }

    /**
     * @param $status
     * @return int
     * @author 陈妙威
     */
    public static function getStatusSeverity($status)
    {
        switch ($status) {
            case self::STATUS_FAIL:
                return 2;
            case self::STATUS_WARN:
                return 1;
            case self::STATUS_OKAY:
                return 0;
            default:
                throw new Exception(\Yii::t("app", 'Unknown schema status "%s"!', $status));
        }
    }

    /**
     * @param $u
     * @param $v
     * @return mixed
     * @author 陈妙威
     */
    public static function getStrongestStatus($u, $v)
    {
        $u_sev = self::getStatusSeverity($u);
        $v_sev = self::getStatusSeverity($v);

        if ($u_sev >= $v_sev) {
            return $u;
        } else {
            return $v;
        }
    }


}

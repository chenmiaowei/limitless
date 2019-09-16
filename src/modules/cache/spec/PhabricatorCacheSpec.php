<?php

namespace orangins\modules\cache\spec;

use orangins\lib\OranginsObject;
use orangins\modules\config\issue\PhabricatorSetupIssue;

/**
 * Class PhabricatorCacheSpec
 * @package orangins\modules\cache\spec
 * @author 陈妙威
 */
abstract class PhabricatorCacheSpec extends OranginsObject
{

    /**
     * @var
     */
    private $name;
    /**
     * @var bool
     */
    private $isEnabled = false;
    /**
     * @var
     */
    private $version;
    /**
     * @var null
     */
    private $clearCacheCallback = null;
    /**
     * @var array
     */
    private $issues = array();

    /**
     * @var int
     */
    private $usedMemory = 0;
    /**
     * @var int
     */
    private $totalMemory = 0;
    /**
     * @var null
     */
    private $entryCount = null;

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
     * @param $is_enabled
     * @return $this
     * @author 陈妙威
     */
    public function setIsEnabled($is_enabled)
    {
        $this->isEnabled = $is_enabled;
        return $this;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * @param $version
     * @return $this
     * @author 陈妙威
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param $key
     * @return mixed
     * @author 陈妙威
     */
    protected function newIssue($key)
    {
        $issue = (new PhabricatorSetupIssue())
            ->setIssueKey($key);
        $this->issues[$key] = $issue;

        return $issue;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getIssues()
    {
        return $this->issues;
    }

    /**
     * @param $used_memory
     * @return $this
     * @author 陈妙威
     */
    public function setUsedMemory($used_memory)
    {
        $this->usedMemory = $used_memory;
        return $this;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getUsedMemory()
    {
        return $this->usedMemory;
    }

    /**
     * @param $total_memory
     * @return $this
     * @author 陈妙威
     */
    public function setTotalMemory($total_memory)
    {
        $this->totalMemory = $total_memory;
        return $this;
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getTotalMemory()
    {
        return $this->totalMemory;
    }

    /**
     * @param $entry_count
     * @return $this
     * @author 陈妙威
     */
    public function setEntryCount($entry_count)
    {
        $this->entryCount = $entry_count;
        return $this;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getEntryCount()
    {
        return $this->entryCount;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function raiseInstallAPCIssue()
    {
        $message = \Yii::t("app",
            "Installing the PHP extension 'APC' (Alternative PHP Cache) will " .
            "dramatically improve performance. Note that APC versions 3.1.14 and " .
            "3.1.15 are broken; 3.1.13 is recommended instead.");

        return $this
            ->newIssue('extension.apc')
            ->setShortName(\Yii::t("app",'APC'))
            ->setName(\Yii::t("app","PHP Extension 'APC' Not Installed"))
            ->setMessage($message)
            ->addPHPExtension('apc');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function raiseEnableAPCIssue()
    {
        $summary = \Yii::t("app",'Enabling APC/APCu will improve performance.');
        $message = \Yii::t("app",
            'The APC or APCu PHP extensions are installed, but not enabled in your ' .
            'PHP configuration. Enabling these extensions will improve Phabricator ' .
            'performance. Edit the "%s" setting to enable these extensions.',
            'apc.enabled');

        return $this
            ->newIssue('extension.apc.enabled')
            ->setShortName(\Yii::t("app",'APC/APCu Disabled'))
            ->setName(\Yii::t("app",'APC/APCu Extensions Not Enabled'))
            ->setSummary($summary)
            ->setMessage($message)
            ->addPHPConfig('apc.enabled');
    }

    /**
     * @param $callback
     * @return $this
     * @author 陈妙威
     */
    public function setClearCacheCallback($callback)
    {
        $this->clearCacheCallback = $callback;
        return $this;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function getClearCacheCallback()
    {
        return $this->clearCacheCallback;
    }
}

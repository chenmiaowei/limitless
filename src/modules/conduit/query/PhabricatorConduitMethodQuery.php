<?php

namespace orangins\modules\conduit\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\PhabricatorApplication;
use orangins\modules\conduit\application\PhabricatorConduitApplication;
use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use PhutilClassMapQuery;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConduitMethodQuery
 * @package orangins\modules\conduit\query
 * @author 陈妙威
 */
final class PhabricatorConduitMethodQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $isDeprecated;
    /**
     * @var
     */
    private $isStable;
    /**
     * @var
     */
    private $isUnstable;
    /**
     * @var
     */
    private $applicationNames;
    /**
     * @var
     */
    private $nameContains;
    /**
     * @var
     */
    private $methods;
    /**
     * @var
     */
    private $isInternal;

    /**
     * @param array $methods
     * @return $this
     * @author 陈妙威
     */
    public function withMethods(array $methods)
    {
        $this->methods = $methods;
        return $this;
    }

    /**
     * @param $name_contains
     * @return $this
     * @author 陈妙威
     */
    public function withNameContains($name_contains)
    {
        $this->nameContains = $name_contains;
        return $this;
    }

    /**
     * @param $is_stable
     * @return $this
     * @author 陈妙威
     */
    public function withIsStable($is_stable)
    {
        $this->isStable = $is_stable;
        return $this;
    }

    /**
     * @param $is_unstable
     * @return $this
     * @author 陈妙威
     */
    public function withIsUnstable($is_unstable)
    {
        $this->isUnstable = $is_unstable;
        return $this;
    }

    /**
     * @param $is_deprecated
     * @return $this
     * @author 陈妙威
     */
    public function withIsDeprecated($is_deprecated)
    {
        $this->isDeprecated = $is_deprecated;
        return $this;
    }

    /**
     * @param $is_internal
     * @return $this
     * @author 陈妙威
     */
    public function withIsInternal($is_internal)
    {
        $this->isInternal = $is_internal;
        return $this;
    }

    /**
     * @return array|null
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $methods = $this->getAllMethods();
        $methods = $this->filterMethods($methods);
        return $methods;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    private function getAllMethods()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(ConduitAPIMethod::className())
            ->setSortMethod('getSortOrder')
            ->execute();
    }

    /**
     * @param array $methods
     * @return array
     * @author 陈妙威
     */
    private function filterMethods(array $methods)
    {
        foreach ($methods as $key => $method) {
            $application = $method->getApplication();
            if (!$application) {
                continue;
            }
            if (!$application->isInstalled()) {
                unset($methods[$key]);
            }
        }

        $status = array(
            ConduitAPIMethod::METHOD_STATUS_STABLE => $this->isStable,
            ConduitAPIMethod::METHOD_STATUS_FROZEN => $this->isStable,
            ConduitAPIMethod::METHOD_STATUS_DEPRECATED => $this->isDeprecated,
            ConduitAPIMethod::METHOD_STATUS_UNSTABLE => $this->isUnstable,
        );

        // Only apply status filters if any of them are set.
        if (array_filter($status)) {
            foreach ($methods as $key => $method) {
                $keep = ArrayHelper::getValue($status, $method->getMethodStatus());
                if (!$keep) {
                    unset($methods[$key]);
                }
            }
        }

        if ($this->nameContains) {
            $needle = phutil_utf8_strtolower($this->nameContains);
            foreach ($methods as $key => $method) {
                $haystack = $method->getAPIMethodName();
                $haystack = phutil_utf8_strtolower($haystack);
                if (strpos($haystack, $needle) === false) {
                    unset($methods[$key]);
                }
            }
        }

        if ($this->methods) {
            $map = array_fuse($this->methods);
            foreach ($methods as $key => $method) {
                $needle = $method->getAPIMethodName();
                if (empty($map[$needle])) {
                    unset($methods[$key]);
                }
            }
        }

        if ($this->isInternal !== null) {
            foreach ($methods as $key => $method) {
                if ($method->isInternalAPI() !== $this->isInternal) {
                    unset($methods[$key]);
                }
            }
        }

        return $methods;
    }

    /**
     * @param array $methods
     * @return array
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function willFilterPage(array $methods)
    {
        $application_phids = array();
        foreach ($methods as $method) {
            $application = $method->getApplication();
            if ($application === null) {
                continue;
            }
            $application_phids[] = $application->getPHID();
        }

        /** @var PhabricatorApplication[] $applications */
        if ($application_phids) {
            $applications = (new PhabricatorApplicationQuery())
                ->setParentQuery($this)
                ->setViewer($this->getViewer())
                ->withPHIDs($application_phids)
                ->execute();
            $applications = mpull($applications, null, 'getPHID');
        } else {
            $applications = array();
        }

        // Remove methods which belong to an application the viewer can not see.
        foreach ($methods as $key => $method) {
            $application = $method->getApplication();
            if ($application === null) {
                continue;
            }

            if (empty($applications[$application->getPHID()])) {
                $this->didRejectResult($method);
                unset($methods[$key]);
            }
        }

        return $methods;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorConduitApplication::className();
    }

}

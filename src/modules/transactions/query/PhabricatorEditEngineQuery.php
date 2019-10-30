<?php

namespace orangins\modules\transactions\query;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\PhabricatorApplication;
use orangins\modules\transactions\application\PhabricatorTransactionsApplication;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use PhutilInvalidStateException;
use ReflectionException;

/**
 * Class PhabricatorEditEngineQuery
 * @package orangins\modules\transactions\query
 * @author 陈妙威
 */
final class PhabricatorEditEngineQuery extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $engineKeys;

    /**
     * @param array $keys
     * @return $this
     * @author 陈妙威
     */
    public function withEngineKeys(array $keys)
    {
        $this->engineKeys = $keys;
        return $this;
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $engines = PhabricatorEditEngine::getAllEditEngines();
        if ($this->engineKeys !== null) {
            $engines = OranginsUtil::array_select_keys($engines, $this->engineKeys);
        }
        return $engines;
    }

    /**
     * @param array $engines
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    protected function willFilterPage(array $engines)
    {
        $viewer = $this->getViewer();

        foreach ($engines as $key => $engine) {
            $app_class = $engine->getEngineApplicationClass();
            if ($app_class === null) {
                continue;
            }

            $can_see = PhabricatorApplication::isClassInstalledForViewer(
                $app_class,
                $viewer);
            if (!$can_see) {
                $this->didRejectResult($engine);
                unset($engines[$key]);
                continue;
            }
        }

        return $engines;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorTransactionsApplication::class;
    }

    /**
     * @param $object
     * @return null
     * @author 陈妙威
     */
    protected function getResultCursor($object)
    {
        return null;
    }

}

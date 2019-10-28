<?php

namespace orangins\modules\search\models;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\transactions\application\PhabricatorTransactionsApplication;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\query\PhabricatorEditEngineQuery;
use PhutilInvalidStateException;
use ReflectionException;
use yii\helpers\ArrayHelper;

/**
 * This is the ActiveQuery class for [[SearchEditengineconfiguration]].
 *
 * @see PhabricatorEditEngineConfiguration
 */
class PhabricatorEditEngineConfigurationQuery extends PhabricatorCursorPagedPolicyAwareQuery
{
    /**
     * @var
     */
    private $ids;
    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $engineKeys;
    /**
     * @var
     */
    private $builtinKeys;
    /**
     * @var
     */
    private $identifiers;
    /**
     * @var
     */
    private $default;
    /**
     * @var
     */
    private $isEdit;
    /**
     * @var
     */
    private $disabled;
    /**
     * @var
     */
    private $ignoreDatabaseConfigurations;
    /**
     * @var
     */
    private $subtypes;

    /**
     * @param array $ids
     * @return $this
     * @author 陈妙威
     */
    public function withIDs(array $ids)
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPHIDs(array $phids)
    {
        $this->phids = $phids;
        return $this;
    }

    /**
     * @param array $engine_keys
     * @return $this
     * @author 陈妙威
     */
    public function withEngineKeys(array $engine_keys)
    {
        $this->engineKeys = $engine_keys;
        return $this;
    }

    /**
     * @param array $builtin_keys
     * @return $this
     * @author 陈妙威
     */
    public function withBuiltinKeys(array $builtin_keys)
    {
        $this->builtinKeys = $builtin_keys;
        return $this;
    }

    /**
     * @param array $identifiers
     * @return $this
     * @author 陈妙威
     */
    public function withIdentifiers(array $identifiers)
    {
        $this->identifiers = $identifiers;
        return $this;
    }

    /**
     * @param $default
     * @return $this
     * @author 陈妙威
     */
    public function withIsDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @param $edit
     * @return $this
     * @author 陈妙威
     */
    public function withIsEdit($edit)
    {
        $this->isEdit = $edit;
        return $this;
    }

    /**
     * @param $disabled
     * @return $this
     * @author 陈妙威
     */
    public function withIsDisabled($disabled)
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * @param $ignore
     * @return $this
     * @author 陈妙威
     */
    public function withIgnoreDatabaseConfigurations($ignore)
    {
        $this->ignoreDatabaseConfigurations = $ignore;
        return $this;
    }

    /**
     * @param array $subtypes
     * @return $this
     * @author 陈妙威
     */
    public function withSubtypes(array $subtypes)
    {
        $this->subtypes = $subtypes;
        return $this;
    }

    /**
     * @return PhabricatorEditEngineConfiguration
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorEditEngineConfiguration();
    }

    /**
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        // TODO: The logic here is a little flimsy and won't survive pagination.
        // For now, I'm just not bothering with pagination since I believe it will
        // take some time before any install manages to produce a large enough
        // number of edit forms for any particular engine for the lack of UI
        // pagination to become a problem.

        /** @var PhabricatorEditEngineConfiguration[] $page */
        if ($this->ignoreDatabaseConfigurations) {
            $page = array();
        } else {
            $page = $this->loadStandardPage();
        }

        // Now that we've loaded the real results from the database, we're going
        // to load builtins from the edit engines and add them to the list.

        $engines = PhabricatorEditEngine::getAllEditEngines();
        if ($this->engineKeys) {
            $engines = array_select_keys($engines, $this->engineKeys);
        }

        foreach ($engines as $engine) {
            $engine->setViewer($this->getViewer());
        }

        // List all the builtins which have already been saved to the database as
        // real objects.
        $concrete = array();
        foreach ($page as $config) {
            $builtin_key = $config->builtin_key;
            if ($builtin_key !== null) {
                $engine_key = $config->engine_key;
                $concrete[$engine_key][$builtin_key] = $config;
            }
        }

        $builtins = array();
        foreach ($engines as $engine_key => $engine) {
            $engine_builtins = $engine->getBuiltinEngineConfigurations();
            foreach ($engine_builtins as $engine_builtin) {
                $builtin_key = $engine_builtin->builtin_key;
                if (isset($concrete[$engine_key][$builtin_key])) {
                    continue;
                } else {
                    $builtins[] = $engine_builtin;
                }
            }
        }

        foreach ($builtins as $builtin) {
            $page[] = $builtin;
        }

        // Now we have to do some extra filtering to make sure everything we're
        // about to return really satisfies the query.

        if ($this->ids !== null) {
            $ids = array_fuse($this->ids);
            foreach ($page as $key => $config) {
                if (empty($ids[$config->getID()])) {
                    unset($page[$key]);
                }
            }
        }

        if ($this->phids !== null) {
            $phids = array_fuse($this->phids);
            foreach ($page as $key => $config) {
                if (empty($phids[$config->getPHID()])) {
                    unset($page[$key]);
                }
            }
        }

        if ($this->builtinKeys !== null) {
            $builtin_keys = array_fuse($this->builtinKeys);
            foreach ($page as $key => $config) {
                if (empty($builtin_keys[$config->getBuiltinKey()])) {
                    unset($page[$key]);
                }
            }
        }

        if ($this->default !== null) {
            foreach ($page as $key => $config) {
                if ($config->is_default != $this->default) {
                    unset($page[$key]);
                }
            }
        }

        if ($this->isEdit !== null) {
            foreach ($page as $key => $config) {
                if ($config->is_edit != $this->isEdit) {
                    unset($page[$key]);
                }
            }
        }

        if ($this->disabled !== null) {
            foreach ($page as $key => $config) {
                if ($config->is_disabled != $this->disabled) {
                    unset($page[$key]);
                }
            }
        }

        if ($this->identifiers !== null) {
            $identifiers = array_fuse($this->identifiers);
            foreach ($page as $key => $config) {
                if (isset($identifiers[$config->builtin_key])) {
                    continue;
                }
                if (isset($identifiers[$config->getID()])) {
                    continue;
                }
                unset($page[$key]);
            }
        }

        if ($this->subtypes !== null) {
            $subtypes = array_fuse($this->subtypes);
            foreach ($page as $key => $config) {
                if (isset($subtypes[$config->subtype])) {
                    continue;
                }

                unset($page[$key]);
            }
        }

        return $page;
    }

    /**
     * @param PhabricatorEditEngineConfiguration[] $configs
     * @return array
     * @throws ReflectionException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function willFilterPage(array $configs)
    {
        $engine_keys = mpull($configs, 'getEngineKey');

        $engines = (new PhabricatorEditEngineQuery())
            ->setParentQuery($this)
            ->setViewer($this->getViewer())
            ->withEngineKeys($engine_keys)
            ->execute();
        $engines = mpull($engines, null, 'getEngineKey');

        foreach ($configs as $key => $config) {
            $engine = ArrayHelper::getValue($engines, $config->engine_key);

            if (!$engine) {
                $this->didRejectResult($config);
                unset($configs[$key]);
                continue;
            }

            $config->attachEngine($engine);
        }

        return $configs;
    }

    /**
     * @return array|PhabricatorEditEngineConfiguration[]
     * @author 陈妙威
     */
    public function loadStandardPage()
    {
        return $this->all();
    }

    /**
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        if ($this->ids !== null) {
            $this->andWhere(['IN', 'id', $this->ids]);
        }

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->engineKeys !== null) {
            $this->andWhere(['IN', 'engine_key', $this->engineKeys]);
        }

        if ($this->builtinKeys !== null) {
            $this->andWhere(['IN', 'builtin_key', $this->builtinKeys]);
        }

        if ($this->identifiers !== null) {
            $this->andWhere([
                'OR',
                ['IN', 'id', $this->identifiers],
                ['IN', 'builtin_key', $this->identifiers],
            ]);
        }

        if ($this->subtypes !== null) {
            $this->andWhere(['IN', 'subtype', $this->subtypes]);
        }
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorTransactionsApplication::class;
    }
}

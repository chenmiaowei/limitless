<?php

namespace orangins\modules\typeahead\datasource;

use orangins\lib\helpers\OranginsUtf8;
use orangins\lib\helpers\OranginsUtil;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\search\engine\PhabricatorDatasourceEngine;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;

/**
 * Class PhabricatorTypeaheadCompositeDatasource
 * @package orangins\modules\typeahead\datasource
 * @author 陈妙威
 */
abstract class PhabricatorTypeaheadCompositeDatasource extends PhabricatorTypeaheadDatasource
{

    /**
     * @var
     */
    private $usable;
    /**
     * @var
     */
    private $prefixString;
    /**
     * @var
     */
    private $prefixLength;

    /**
     * @return PhabricatorTypeaheadDatasource[]
     * @author 陈妙威
     */
    abstract public function getComponentDatasources();

    /**
     * @return bool
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function isBrowsable()
    {
        $phabricatorTypeaheadDatasources = $this->getUsableDatasources();
        foreach ($phabricatorTypeaheadDatasources as $datasource) {
            if (!$datasource->isBrowsable()) {
                return false;
            }
        }

        return parent::isBrowsable();
    }

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return null;
    }

    /**
     * @return PhabricatorTypeaheadResult[]
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws \PhutilMethodNotImplementedException
     */
    public function loadResults()
    {
        $phases = array();

        // We only need to do a prefix phase query if there's an actual query
        // string. If the user didn't type anything, nothing can possibly match it.
        if (strlen($this->getRawQuery())) {
            $phases[] = self::PHASE_PREFIX;
        }

        $phases[] = self::PHASE_CONTENT;

        $offset = $this->getOffset();
        $limit = $this->getLimit();

        $results = array();
        foreach ($phases as $phase) {
            if ($limit) {
                $phase_limit = ($offset + $limit) - count($results);
            } else {
                $phase_limit = 0;
            }

            $phase_results = $this->loadResultsForPhase(
                $phase,
                $phase_limit);

            foreach ($phase_results as $result) {
                $results[] = $result;
            }

            if ($limit) {
                if (count($results) >= $offset + $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * @param $phase
     * @param $limit
     * @return array|mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilInvalidStateException
     */
    protected function loadResultsForPhase($phase, $limit)
    {
        if ($phase == self::PHASE_PREFIX) {
            $this->prefixString = $this->getPrefixQuery();
            $this->prefixLength = strlen($this->prefixString);
        }

        // If the input query is a function like `members(platy`, and we can
        // parse the function, we strip the function off and hand the stripped
        // query to child sources. This makes it easier to implement function
        // sources in terms of real object sources.
        $raw_query = $this->getRawQuery();

        $is_function = false;
        if (self::isFunctionToken($raw_query)) {
            $is_function = true;
        }

        $stack = $this->getFunctionStack();
        $is_browse = $this->getIsBrowse();

        $results = array();
        foreach ($this->getUsableDatasources() as $source) {
            $source_stack = $stack;

            $source_query = $raw_query;
            if ($is_function) {
                // If this source can't handle the function, skip it.
                $function = $source->parseFunction($raw_query, $allow_partial = true);
                if (!$function) {
                    continue;
                }

                // If this source handles the function directly, strip the function.
                // Otherwise, this is something like a composite source which has
                // some internal source which can evaluate the function, but will
                // perform stripping later.
                if ($source->shouldStripFunction($function['name'])) {
                    $source_query = OranginsUtil::head($function['argv']);
                    $source_stack[] = $function['name'];
                }
            }

            $source
                ->setPhase($phase)
                ->setFunctionStack($source_stack)
                ->setRawQuery($source_query)
                ->setQuery($this->getQuery())
                ->setViewer($this->getViewer());

            if ($is_browse) {
                $source->setIsBrowse(true);
            }

//            $source_results = null;
            if ($limit) {
                // If we are loading results from a source with a limit, it may return
                // some results which belong to the wrong phase. We need an entire page
                // of valid results in the correct phase AFTER any results for the
                // wrong phase are filtered for pagination to work correctly.

                // To make sure we can get there, we fetch more and more results until
                // enough of them survive filtering to generate a full page.

                // We start by fetching 150% of the results than we think we need, and
                // double the amount we overfetch by each time.
                $factor = 1.5;
                while (true) {
                    $query_source = clone $source;
                    $total = (int)ceil($limit * $factor) + 1;
                    $query_source->setLimit($total);

                    $source_results = $query_source->loadResultsForPhase(
                        $phase,
                        $limit);

                    // If there are fewer unfiltered results than we asked for, we know
                    // this is the entire result set and we don't need to keep going.
                    if (count($source_results) < $total) {
                        $source_results = $query_source->didLoadResults($source_results);
                        $source_results = $this->filterPhaseResults(
                            $phase,
                            $source_results);
                        break;
                    }

                    // Otherwise, this result set have everything we need, or may not.
                    // Filter the results that are part of the wrong phase out first...
                    $source_results = $query_source->didLoadResults($source_results);
                    $source_results = $this->filterPhaseResults($phase, $source_results);

                    // Now check if we have enough results left. If we do, we're all set.
                    if (count($source_results) >= $total) {
                        break;
                    }

                    // We filtered out too many results to have a full page left, so we
                    // need to run the query again, asking for even more results. We'll
                    // keep doing this until we get a full page or get all of the
                    // results.
                    $factor = $factor * 2;
                }
            } else {
                $source_results = $source->loadResults();
                $source_results = $source->didLoadResults($source_results);
                $source_results = $this->filterPhaseResults($phase, $source_results);
            }
            $results[] = $source_results;
        }

        $results = OranginsUtil::array_mergev($results);
        $results = OranginsUtil::msort($results, 'getSortKey');

        $results = $this->sliceResults($results);

        return $results;
    }

    /**
     * @param $phase
     * @param PhabricatorTypeaheadResult[] $source_results
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    private function filterPhaseResults($phase, $source_results)
    {
        foreach ($source_results as $key => $source_result) {
            $result_phase = $this->getResultPhase($source_result);
            if ($result_phase != $phase) {
                unset($source_results[$key]);
                continue;
            }
            $source_result->setPhase($result_phase);
        }

        return $source_results;
    }

    /**
     * @param PhabricatorTypeaheadResult $result
     * @return string
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    private function getResultPhase(PhabricatorTypeaheadResult $result)
    {
        if ($this->prefixLength) {
            $result_name = OranginsUtf8::phutil_utf8_strtolower($result->getName());
            if (!strncmp($result_name, $this->prefixString, $this->prefixLength)) {
                return self::PHASE_PREFIX;
            }
        }

        return self::PHASE_CONTENT;
    }

    /**
     * @param array $results
     * @return array
     * @author 陈妙威
     */
    protected function sliceResults(array $results)
    {
        $offset = $this->getOffset();
        $limit = $this->getLimit();

        if ($offset || $limit) {
            if (!$limit) {
                $limit = count($results);
            }

            $results = array_slice($results, $offset, $limit, $preserve_keys = true);
        }

        return $results;
    }

    /**
     * @return PhabricatorTypeaheadDatasource[]
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    private function getUsableDatasources()
    {
        if ($this->usable === null) {
            $viewer = $this->getViewer();

            $sources = $this->getComponentDatasources();
            $extension_sources = (new PhabricatorDatasourceEngine())
                ->setViewer($viewer)
                ->newDatasourcesForCompositeDatasource($this);
            foreach ($extension_sources as $extension_source) {
                $sources[] = $extension_source;
            }

            $usable = array();
            foreach ($sources as $source) {
                $application_class = $source->getDatasourceApplicationClass();

                if ($application_class) {
                    $result = (new PhabricatorApplicationQuery())
                        ->setViewer($this->getViewer())
                        ->withShortName(false)
                        ->withClasses(array($application_class))
                        ->execute();
                    if (!$result) {
                        continue;
                    }
                }

                $source->setViewer($viewer);
                $usable[] = $source;
            }
            $this->usable = $usable;
        }
        return $this->usable;
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function getAllDatasourceFunctions()
    {
        $results = parent::getAllDatasourceFunctions();
        foreach ($this->getUsableDatasources() as $source) {
            $results += $source->getAllDatasourceFunctions();
        }
        return $results;
    }

    /**
     * @param array $results
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function didEvaluateTokens(array $results)
    {
        foreach ($this->getUsableDatasources() as $source) {
            $results = $source->didEvaluateTokens($results);
        }
        return $results;
    }

    /**
     * @param $function
     * @return bool
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function canEvaluateFunction($function)
    {
        foreach ($this->getUsableDatasources() as $source) {
            if ($source->canEvaluateFunction($function)) {
                return true;
            }
        }

        return parent::canEvaluateFunction($function);
    }

    /**
     * @param array $values
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function evaluateValues(array $values)
    {
        foreach ($this->getUsableDatasources() as $source) {
            $values = $source->evaluateValues($values);
        }

        return parent::evaluateValues($values);
    }

    /**
     * @param $function
     * @param array $argv
     * @return void
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function evaluateFunction($function, array $argv)
    {
        foreach ($this->getUsableDatasources() as $source) {
            if ($source->canEvaluateFunction($function)) {
                return $source->evaluateFunction($function, $argv);
            }
        }

        return parent::evaluateFunction($function, $argv);
    }

    /**
     * @param $function
     * @param array $argv_list
     * @return \orangins\modules\typeahead\view\PhabricatorTypeaheadTokenView[]
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function renderFunctionTokens($function, array $argv_list)
    {
        foreach ($this->getUsableDatasources() as $source) {
            if ($source->canEvaluateFunction($function)) {
                return $source->renderFunctionTokens($function, $argv_list);
            }
        }

        return parent::renderFunctionTokens($function, $argv_list);
    }

    /**
     * @param array $values
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function renderSpecialTokens(array $values)
    {
        $result = array();
        foreach ($this->getUsableDatasources() as $source) {
            $special = $source->renderSpecialTokens($values);
            foreach ($special as $key => $token) {
                $result[$key] = $token;
                unset($values[$key]);
            }
            if (!$values) {
                break;
            }
        }
        return $result;
    }


}

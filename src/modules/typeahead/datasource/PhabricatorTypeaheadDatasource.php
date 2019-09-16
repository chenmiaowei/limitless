<?php

namespace orangins\modules\typeahead\datasource;

use orangins\lib\OranginsObject;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use PhutilMethodNotImplementedException;
use orangins\lib\helpers\OranginsUtf8;
use orangins\lib\helpers\OranginsUtil;
use PhutilURI;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDConstants;
use orangins\modules\typeahead\exception\PhabricatorTypeaheadInvalidTokenException;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;
use orangins\modules\typeahead\view\PhabricatorTypeaheadTokenView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * @task functions Token Functions
 */
abstract class PhabricatorTypeaheadDatasource extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $query;
    /**
     * @var
     */
    private $rawQuery;
    /**
     * @var
     */
    private $offset;
    /**
     * @var
     */
    private $limit;
    /**
     * @var array
     */
    private $parameters = array();
    /**
     * @var array
     */
    private $functionStack = array();
    /**
     * @var
     */
    private $isBrowse;
    /**
     * @var string
     */
    private $phase = self::PHASE_CONTENT;

    /**
     *
     */
    const PHASE_PREFIX = 'prefix';
    /**
     *
     */
    const PHASE_CONTENT = 'content';

    /**
     * @param $limit
     * @return $this
     * @author 陈妙威
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param $offset
     * @return $this
     * @author 陈妙威
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return PhabricatorUser
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param $raw_query
     * @return $this
     * @author 陈妙威
     */
    public function setRawQuery($raw_query)
    {
        $this->rawQuery = $raw_query;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function getPrefixQuery()
    {
        return OranginsUtf8::phutil_utf8_strtolower($this->getRawQuery());
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRawQuery()
    {
        return $this->rawQuery;
    }

    /**
     * @param $query
     * @return $this
     * @author 陈妙威
     */
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param array $params
     * @return $this
     * @author 陈妙威
     */
    public function setParameters(array $params)
    {
        $this->parameters = $params;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed
     * @author 陈妙威
     */
    public function getParameter($name, $default = null)
    {
        return ArrayHelper::getValue($this->parameters, $name, $default);
    }

    /**
     * @param $is_browse
     * @return $this
     * @author 陈妙威
     */
    public function setIsBrowse($is_browse)
    {
        $this->isBrowse = $is_browse;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsBrowse()
    {
        return $this->isBrowse;
    }

    /**
     * @param $phase
     * @return $this
     * @author 陈妙威
     */
    public function setPhase($phase)
    {
        $this->phase = $phase;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getPhase()
    {
        return $this->phase;
    }

    /**
     * @return string
     * @author 陈妙威
     * @throws \ReflectionException
     */
    public function getDatasourceURI()
    {
        return Url::to(['/typeahead/index/index', 'class' => $this->getClassShortName()]);
    }

    /**
     * @return null|string
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     */
    public function getBrowseURI()
    {
        if (!$this->isBrowsable()) {
            return null;
        }

        $merge = ArrayHelper::merge(['/typeahead/index/index'],
            $this->parameters, [
                'class' => $this->getClassShortName(),
                'action' => 'browse'
            ]);
        return Url::to($merge);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getPlaceholderText();

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return get_class($this);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getDatasourceApplicationClass();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function loadResults();

    /**
     * @param $phase
     * @param $limit
     * @return mixed
     * @author 陈妙威
     */
    protected function loadResultsForPhase($phase, $limit)
    {
        // By default, sources just load all of their results in every phase and
        // rely on filtering at a higher level to sequence phases correctly.
        $this->setLimit($limit);
        $loadResults = $this->loadResults();
        return $loadResults;
    }

    /**
     * @param array $results
     * @return array
     * @author 陈妙威
     */
    protected function didLoadResults(array $results)
    {
        return $results;
    }

    /**
     * @param $string
     * @return array
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public static function tokenizeString($string)
    {
        $string = OranginsUtf8::phutil_utf8_strtolower($string);
        $string = trim($string);
        if (!strlen($string)) {
            return array();
        }

        // NOTE: Splitting on "(" and ")" is important for milestones.

        $tokens = preg_split('/[\s\[\]\(\)-]+/u', $string);
        $tokens = array_unique($tokens);

        // Make sure we don't return the empty token, as this will boil down to a
        // JOIN against every token.
        foreach ($tokens as $key => $value) {
            if (!strlen($value)) {
                unset($tokens[$key]);
            }
        }

        return array_values($tokens);
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function getTokens()
    {
        return self::tokenizeString($this->getRawQuery());
    }

    /**
     * @param PhabricatorCursorPagedPolicyAwareQuery $query
     * @return mixed
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function executeQuery(
        PhabricatorCursorPagedPolicyAwareQuery $query)
    {
        return $query
            ->setViewer($this->getViewer())
            ->setOffset($this->getOffset())
            ->setLimit($this->getLimit())
            ->execute();
    }


    /**
     * Can the user browse through results from this datasource?
     *
     * Browsable datasources allow the user to switch from typeahead mode to
     * a browse mode where they can scroll through all results.
     *
     * By default, datasources are browsable, but some datasources can not
     * generate a meaningful result set or can't filter results on the server.
     *
     * @return bool
     */
    public function isBrowsable()
    {
        return true;
    }


    /**
     * Filter a list of results, removing items which don't match the query
     * tokens.
     *
     * This is useful for datasources which return a static list of hard-coded
     * or configured results and can't easily do query filtering in a real
     * query class. Instead, they can just build the entire result set and use
     * this method to filter it.
     *
     * For datasources backed by database objects, this is often much less
     * efficient than filtering at the query level.
     *
     * @param PhabricatorTypeaheadResult[]> List of typeahead results.
     * @return PhabricatorTypeaheadResult[]
     * @throws \yii\base\Exception
     */
    protected function filterResultsAgainstTokens(array $results)
    {
        $tokens = $this->getTokens();
        if (!$tokens) {
            return $results;
        }

        $map = array();
        foreach ($tokens as $token) {
            $map[$token] = strlen($token);
        }

        foreach ($results as $key => $result) {
            $rtokens = self::tokenizeString($result->getName());

            // For each token in the query, we need to find a match somewhere
            // in the result name.
            foreach ($map as $token => $length) {
                // Look for a match.
                $match = false;
                foreach ($rtokens as $rtoken) {
                    if (!strncmp($rtoken, $token, $length)) {
                        // This part of the result name has the query token as a prefix.
                        $match = true;
                        break;
                    }
                }

                if (!$match) {
                    // We didn't find a match for this query token, so throw the result
                    // away. Try with the next result.
                    unset($results[$key]);
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function newFunctionResult()
    {
        return (new PhabricatorTypeaheadResult())
            ->setTokenType(PhabricatorTypeaheadTokenView::TYPE_FUNCTION)
            ->setIcon('fa-asterisk')
            ->addAttribute(\Yii::t("app", 'Function'));
    }

    /**
     * @param $name
     * @return mixed
     * @author 陈妙威
     */
    public function newInvalidToken($name)
    {
        return (new PhabricatorTypeaheadTokenView())
            ->setValue($name)
            ->setIcon('fa-exclamation-circle')
            ->setTokenType(PhabricatorTypeaheadTokenView::TYPE_INVALID);
    }

    /**
     * @param array $values
     * @return PhabricatorTypeaheadTokenView[]
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function renderTokens(array $values)
    {
        $phids = array();
        $setup = array();
        $tokens = array();

        foreach ($values as $key => $value) {
            if (!self::isFunctionToken($value)) {
                $phids[$key] = $value;
            } else {
                $function = $this->parseFunction($value);
                if ($function) {
                    $setup[$function['name']][$key] = $function;
                } else {
                    $name = \Yii::t("app", 'Invalid Function: %s', $value);
                    $tokens[$key] = $this->newInvalidToken($name)
                        ->setKey($value);
                }
            }
        }

        // Give special non-function tokens which are also not PHIDs (like statuses
        // and priorities) an opportunity to render.
        $type_unknown = PhabricatorPHIDConstants::PHID_TYPE_UNKNOWN;
        $special = array();
        foreach ($values as $key => $value) {
            if (PhabricatorPHID::phid_get_type($value) == $type_unknown) {
                $special[$key] = $value;
            }
        }

        if ($special) {
            $special_tokens = $this->renderSpecialTokens($special);
            foreach ($special_tokens as $key => $token) {
                $tokens[$key] = $token;
                unset($phids[$key]);
            }
        }

        if ($phids) {
            $handles = $this->getViewer()->loadHandles($phids);
            foreach ($phids as $key => $phid) {
                $handle = $handles[$phid];
                $tokens[$key] = PhabricatorTypeaheadTokenView::newFromHandle($handle);
            }
        }

        if ($setup) {
            foreach ($setup as $function_name => $argv_list) {
                // Render the function tokens.
                /** @var PhabricatorTypeaheadTokenView[] $function_tokens */
                $function_tokens = $this->renderFunctionTokens(
                    $function_name,
                    OranginsUtil::ipull($argv_list, 'argv'));

                // Rekey the function tokens using the original array keys.
                $function_tokens = array_combine(
                    array_keys($argv_list),
                    $function_tokens);

                // For any functions which were invalid, set their value to the
                // original input value before it was parsed.
                foreach ($function_tokens as $key => $token) {
                    $type = $token->getTokenType();
                    if ($type == PhabricatorTypeaheadTokenView::TYPE_INVALID) {
                        $token->setKey($values[$key]);
                    }
                }

                $tokens += $function_tokens;
            }
        }

        return OranginsUtil::array_select_keys($tokens, array_keys($values));
    }

    /**
     * @param array $values
     * @return array
     * @author 陈妙威
     */
    protected function renderSpecialTokens(array $values)
    {
        return array();
    }

    /* -(  Token Functions  )---------------------------------------------------- */


    /**
     * @task functions
     */
    public function getDatasourceFunctions()
    {
        return array();
    }


    /**
     * @task functions
     */
    public function getAllDatasourceFunctions()
    {
        return $this->getDatasourceFunctions();
    }


    /**
     * @task functions
     * @param $function
     * @return bool
     */
    protected function canEvaluateFunction($function)
    {
        return $this->shouldStripFunction($function);
    }


    /**
     * @task functions
     * @param $function
     * @return bool
     */
    protected function shouldStripFunction($function)
    {
        $functions = $this->getDatasourceFunctions();
        return isset($functions[$function]);
    }


    /**
     * @task functions
     * @param $function
     * @param array $argv_list
     * @throws PhutilMethodNotImplementedException
     */
    protected function evaluateFunction($function, array $argv_list)
    {
        throw new PhutilMethodNotImplementedException();
    }


    /**
     * @task functions
     * @param array $values
     * @return array
     */
    protected function evaluateValues(array $values)
    {
        return $values;
    }


    /**
     * @task functions
     * @param array $tokens
     * @return array
     * @throws PhabricatorTypeaheadInvalidTokenException
     * @throws PhutilMethodNotImplementedException
     */
    public function evaluateTokens(array $tokens)
    {
        $results = array();
        $evaluate = array();
        foreach ($tokens as $token) {
            if (!self::isFunctionToken($token)) {
                $results[] = $token;
            } else {
                // Put a placeholder in the result list so that we retain token order
                // when possible. We'll overwrite this below.
                $results[] = null;
                $evaluate[OranginsUtil::last_key($results)] = $token;
            }
        }

        $results = $this->evaluateValues($results);

        foreach ($evaluate as $result_key => $function) {
            $function = $this->parseFunction($function);
            if (!$function) {
                throw new PhabricatorTypeaheadInvalidTokenException();
            }

            $name = $function['name'];
            $argv = $function['argv'];

            $evaluated_tokens = $this->evaluateFunction($name, array($argv));
            if (!$evaluated_tokens) {
                unset($results[$result_key]);
            } else {
                $is_first = true;
                foreach ($evaluated_tokens as $phid) {
                    if ($is_first) {
                        $results[$result_key] = $phid;
                        $is_first = false;
                    } else {
                        $results[] = $phid;
                    }
                }
            }
        }

        $results = array_values($results);
        $results = $this->didEvaluateTokens($results);

        return $results;
    }


    /**
     * @task functions
     * @param array $results
     * @return array
     */
    protected function didEvaluateTokens(array $results)
    {
        return $results;
    }


    /**
     * @task functions
     * @param $token
     * @return bool
     */
    public static function isFunctionToken($token)
    {
        // We're looking for a "(" so that a string like "members(q" is identified
        // and parsed as a function call. This allows us to start generating
        // results immediately, before the user fully types out "members(quack)".
        return (strpos($token, '(') !== false);
    }


    /**
     * @task functions
     * @param $token
     * @param bool $allow_partial
     * @return array|null
     * @throws PhabricatorTypeaheadInvalidTokenException
     * @throws PhutilMethodNotImplementedException
     */
    protected function parseFunction($token, $allow_partial = false)
    {
        $matches = null;

        if ($allow_partial) {
            $ok = preg_match('/^([^(]+)\((.*?)\)?\z/', $token, $matches);
        } else {
            $ok = preg_match('/^([^(]+)\((.*)\)\z/', $token, $matches);
        }

        if (!$ok) {
            if (!$allow_partial) {
                throw new PhabricatorTypeaheadInvalidTokenException(
                    \Yii::t("app",
                        'Unable to parse function and arguments for token "%s".',
                        $token));
            }
            return null;
        }

        $function = trim($matches[1]);

        if (!$this->canEvaluateFunction($function)) {
            if (!$allow_partial) {
                throw new PhabricatorTypeaheadInvalidTokenException(
                    \Yii::t("app",
                        'This datasource ("%s") can not evaluate the function "%s(...)".',
                        get_class($this),
                        $function));
            }

            return null;
        }

        // TODO: There is currently no way to quote characters in arguments, so
        // some characters can't be argument characters. Replace this with a real
        // parser once we get use cases.

        $argv = $matches[2];
        $argv = trim($argv);
        if (!strlen($argv)) {
            $argv = array();
        } else {
            $argv = preg_split('/,/', $matches[2]);
            foreach ($argv as $key => $arg) {
                $argv[$key] = trim($arg);
            }
        }

        foreach ($argv as $key => $arg) {
            if (self::isFunctionToken($arg)) {
                $subfunction = $this->parseFunction($arg);

                $results = $this->evaluateFunction(
                    $subfunction['name'],
                    array($subfunction['argv']));

                $argv[$key] = OranginsUtil::head($results);
            }
        }

        return array(
            'name' => $function,
            'argv' => $argv,
        );
    }


    /**
     * @task functions
     * @param $function
     * @param array $argv_list
     * @return PhabricatorTypeaheadTokenView[]
     * @throws PhutilMethodNotImplementedException
     */
    public function renderFunctionTokens($function, array $argv_list)
    {
        throw new PhutilMethodNotImplementedException();
    }


    /**
     * @task functions
     */
    public function setFunctionStack(array $function_stack)
    {
        $this->functionStack = $function_stack;
        return $this;
    }


    /**
     * @task functions
     */
    public function getFunctionStack()
    {
        return $this->functionStack;
    }


    /**
     * @task functions
     */
    protected function getCurrentFunction()
    {
        return OranginsUtil::nonempty(OranginsUtil::last($this->functionStack), null);
    }

    /**
     * @param array $results
     * @param array $values
     * @return array
     * @author 陈妙威
     */
    protected function renderTokensFromResults(array $results, array $values)
    {
        $tokens = array();
        foreach ($values as $key => $value) {
            if (empty($results[$value])) {
                continue;
            }
            $tokens[$key] = PhabricatorTypeaheadTokenView::newFromTypeaheadResult(
                $results[$value]);
        }

        return $tokens;
    }

    /**
     * @param array $values
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function getWireTokens(array $values)
    {
        // TODO: This is a bit hacky for now: we're sort of generating wire
        // results, rendering them, then reverting them back to wire results. This
        // is pretty silly. It would probably be much cleaner to make
        // renderTokens() call this method instead, then render from the result
        // structure.
        $rendered = $this->renderTokens($values);

        $tokens = array();
        foreach ($rendered as $key => $render) {
            $tokens[$key] = (new PhabricatorTypeaheadResult())
                ->setPHID($render->getKey())
                ->setIcon($render->getIcon())
                ->setColor($render->getColor())
                ->setDisplayName($render->getValue())
                ->setTokenType($render->getTokenType());
        }

        return OranginsUtil::mpull($tokens, 'getWireFormat', 'getPHID');
    }

}

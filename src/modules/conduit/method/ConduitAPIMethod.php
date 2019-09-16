<?php

namespace orangins\modules\conduit\method;

use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\lib\PhabricatorApplication;
use orangins\lib\view\control\AphrontCursorPagerView;
use orangins\modules\cache\PhabricatorCachedClassMapQuery;
use orangins\modules\conduit\protocol\ConduitAPIRequest;
use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;
use orangins\modules\policy\interfaces\PhabricatorPolicyInterface;
use orangins\modules\userservice\conduitprice\UserServiceConduitPrice;
use PhutilClassMapQuery;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * @task info Method Information
 * @task status Method Status
 * @task pager Paging Results
 */
abstract class ConduitAPIMethod extends OranginsObject implements PhabricatorPolicyInterface
{

    /**
     * @var
     */
    private $viewer;

    /**
     *
     */
    const METHOD_STATUS_STABLE = 'stable';
    /**
     *
     */
    const METHOD_STATUS_UNSTABLE = 'unstable';
    /**
     *
     */
    const METHOD_STATUS_DEPRECATED = 'deprecated';
    /**
     *
     */
    const METHOD_STATUS_FROZEN = 'frozen';

    /**
     *
     */
    const SCOPE_NEVER = 'scope.never';
    /**
     *
     */
    const SCOPE_ALWAYS = 'scope.always';

    /**
     * Get a short, human-readable text summary of the method.
     *
     * @return string Short summary of method.
     * @task info
     */
    public function getMethodSummary()
    {
        return $this->getMethodDescription();
    }


    /**
     * Get a detailed description of the method.
     *
     * This method should return remarkup.
     *
     * @return string Detailed description of the method.
     * @task info
     */
    abstract public function getMethodDescription();

    /**
     * @return null
     * @author 陈妙威
     */
    public function getMethodDocumentation()
    {
        return null;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function defineParamTypes();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function defineReturnType();

    /**
     * @return array
     * @author 陈妙威
     */
    protected function defineErrorTypes()
    {
        return array();
    }

    /**
     * @param ConduitAPIRequest $request
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function execute(ConduitAPIRequest $request);

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isInternalAPI()
    {
        return false;
    }

    /**
     * @return array|mixed
     * @author 陈妙威
     */
    public function getParamTypes()
    {
        $types = $this->defineParamTypes();

        $query = $this->newQueryObject();
        if ($query) {
            $types['order'] = 'optional order';
            $types += $this->getPagerParamTypes();
        }

        return $types;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getReturnType()
    {
        return $this->defineReturnType();
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getErrorTypes()
    {
        return $this->defineErrorTypes();
    }

    /**
     * This is mostly for compatibility with
     * @{class:PhabricatorCursorPagedPolicyAwareQuery}.
     */
    public function getID()
    {
        return $this->getAPIMethodName();
    }

    /**
     * Get the status for this method (e.g., stable, unstable or deprecated).
     * Should return a METHOD_STATUS_* constant. By default, methods are
     * "stable".
     *
     * @return string  METHOD_STATUS_* constant.
     * @task status
     */
    public function getMethodStatus()
    {
        return self::METHOD_STATUS_STABLE;
    }

    /**
     * Optional description to supplement the method status. In particular, if
     * a method is deprecated, you can return a string here describing the reason
     * for deprecation and stable alternatives.
     *
     * @return string|null  Description of the method status, if available.
     * @task status
     */
    public function getMethodStatusDescription()
    {
        return null;
    }

    /**
     * @param $error_code
     * @return object
     * @author 陈妙威
     */
    public function getErrorDescription($error_code)
    {
        return ArrayHelper::getValue($this->getErrorTypes(), $error_code, \Yii::t("app",'Unknown Error'));
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getRequiredScope()
    {
        return self::SCOPE_NEVER;
    }

    /**
     * @param ConduitAPIRequest $request
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function executeMethod(ConduitAPIRequest $request)
    {
        $this->setViewer($request->getUser());

        return $this->execute($request);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getAPIMethodName();

    /**
     * Return a key which sorts methods by application name, then method status,
     * then method name.
     */
    public function getSortOrder()
    {
        $name = $this->getAPIMethodName();

        $map = array(
            self::METHOD_STATUS_STABLE => 0,
            self::METHOD_STATUS_UNSTABLE => 1,
            self::METHOD_STATUS_DEPRECATED => 2,
        );
        $ord = ArrayHelper::getValue($map, $this->getMethodStatus(), 0);

        list($head, $tail) = explode('.', $name, 2);

        return "{$head}.{$ord}.{$tail}";
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public static function getMethodStatusMap()
    {
        $map = array(
            self::METHOD_STATUS_STABLE => \Yii::t("app",'Stable'),
            self::METHOD_STATUS_UNSTABLE => \Yii::t("app",'Unstable'),
            self::METHOD_STATUS_DEPRECATED => \Yii::t("app",'Deprecated'),
        );

        return $map;
    }

    /**
     * @return object
     * @author 陈妙威
     */
    public function getApplicationName()
    {
        return head(explode('.', $this->getAPIMethodName(), 2));
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public static function loadAllConduitMethods()
    {
        return self::newClassMapQuery()->execute();
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private static function newClassMapQuery()
    {
        return (new PhutilClassMapQuery())
            ->setAncestorClass(__CLASS__)
            ->setUniqueMethod('getAPIMethodName');
    }

    /**
     * @param $method_name
     * @return array|ConduitAPIMethod
     * @author 陈妙威
     * @throws Exception
     */
    public static function getConduitMethod($method_name)
    {
        return (new PhabricatorCachedClassMapQuery())
            ->setClassMapQuery(self::newClassMapQuery())
            ->setMapKeyMethod('getAPIMethodName')
            ->loadClass($method_name);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldRequireAuthentication()
    {
        return true;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldPayFee()
    {
        return false;
    }

    /**
     * @return UserServiceConduitPrice
     * @author 陈妙威
     */
    public function getPayCounterPrice()
    {
        return null;
    }


    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return false;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowUnguardedWrites()
    {
        return false;
    }


    /**
     * Optionally, return a @{class:PhabricatorApplication} which this call is
     * part of. The call will be disabled when the application is uninstalled.
     *
     * @return PhabricatorApplication|null  Related application.
     */
    public function getApplication()
    {
        return null;
    }

    /**
     * @param $constants
     * @return string
     * @author 陈妙威
     */
    protected function formatStringConstants($constants)
    {
        foreach ($constants as $key => $value) {
            $constants[$key] = '"' . $value . '"';
        }
        $constants = implode(', ', $constants);
        return 'string-constant<' . $constants . '>';
    }

    /**
     * @param $key
     * @return bool|null|string
     * @author 陈妙威
     */
    public static function getParameterMetadataKey($key)
    {
        if (strncmp($key, 'api_', 4) === 0) {
            // All keys passed beginning with "api." are always metadata keys.
            return substr($key, 4);
        } else {
            switch ($key) {
                // These are real keys which always belong to request metadata.
                case 'access_token':
                case 'scope':
                case 'output':

                    // This is not a real metadata key; it is included here only to
                    // prevent Conduit methods from defining it.
                case '__conduit__':

                    // This is prevented globally as a blanket defense against OAuth
                    // redirection attacks. It is included here to stop Conduit methods
                    // from defining it.
                case 'code':

                    // This is not a real metadata key, but the presence of this
                    // parameter triggers an alternate request decoding pathway.
                case 'params':
                    return $key;
            }
        }

        return null;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /* -(  Paging Results  )----------------------------------------------------- */


    /**
     * @task pager
     */
    protected function getPagerParamTypes()
    {
        return array(
            'before' => 'optional string',
            'after' => 'optional string',
            'limit' => 'optional int (default = 100)',
        );
    }


    /**
     * @task pager
     * @param ConduitAPIRequest $request
     * @return AphrontCursorPagerView
     */
    protected function newPager(ConduitAPIRequest $request)
    {
        $limit = $request->getValue('limit', 100);
        $limit = min(1000, $limit);
        $limit = max(1, $limit);

        $pager = (new AphrontCursorPagerView())
            ->setPageSize($limit);

        $before_id = $request->getValue('before');
        if ($before_id !== null) {
            $pager->setBeforeID($before_id);
        }

        $after_id = $request->getValue('after');
        if ($after_id !== null) {
            $pager->setAfterID($after_id);
        }

        return $pager;
    }


    /**
     * @task pager
     * @param array $results
     * @param AphrontCursorPagerView $pager
     * @return array
     */
    protected function addPagerResults(
        array $results,
        AphrontCursorPagerView $pager)
    {

        $results['cursor'] = array(
            'limit' => $pager->getPageSize(),
            'after' => $pager->getNextPageID(),
            'before' => $pager->getPrevPageID(),
        );

        return $results;
    }


    /* -(  Implementing Query Methods  )----------------------------------------- */


    /**
     * @return null
     * @author 陈妙威
     */
    public function newQueryObject()
    {
        return null;
    }


    /**
     * @param ConduitAPIRequest $request
     * @return null
     * @throws Exception
     * @author 陈妙威
     */
    protected function newQueryForRequest(ConduitAPIRequest $request)
    {
        $query = $this->newQueryObject();

        if (!$query) {
            throw new Exception(
                \Yii::t("app",
                    'You can not call newQueryFromRequest() in this method ("{0}") ' .
                    'because it does not implement newQueryObject().', [
                        get_class($this)
                    ]));
        }

        if (!($query instanceof PhabricatorCursorPagedPolicyAwareQuery)) {
            throw new Exception(
                \Yii::t("app",
                    'Call to method newQueryObject() did not return an object of class ' .
                    '"{0}".', [
                        'PhabricatorCursorPagedPolicyAwareQuery'
                    ]));
        }

        $query->setViewer($request->getUser());

        $order = $request->getValue('order');
        if ($order !== null) {
            if (is_scalar($order)) {
                $query->setOrder($order);
            } else {
                $query->setOrderVector($order);
            }
        }

        return $query;
    }


    /* -(  PhabricatorPolicyInterface  )----------------------------------------- */


    /**
     * @return mixed|null
     * @author 陈妙威
     */
    public function getPHID()
    {
        return "PHID-COND-" . $this->getAPIMethodName();
    }

    /**
     * @return array|string[]
     * @author 陈妙威
     */
    public function getCapabilities()
    {
        return array(
            PhabricatorPolicyCapability::CAN_VIEW,
        );
    }

    /**
     * @param $capability
     * @return mixed|string
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function getPolicy($capability)
    {
        // Application methods get application visibility; other methods get open
        // visibility.

        $application = $this->getApplication();
        if ($application) {
            return $application->getPolicy($capability);
        }

        return PhabricatorPolicies::getMostOpenPolicy();
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool|mixed
     * @author 陈妙威
     */
    public function hasAutomaticCapability($capability, PhabricatorUser $viewer)
    {
        if (!$this->shouldRequireAuthentication()) {
            // Make unauthenticated methods universally visible.
            return true;
        }

        return false;
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @return bool
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function hasApplicationCapability(
        $capability,
        PhabricatorUser $viewer)
    {

        $application = $this->getApplication();

        if (!$application) {
            return false;
        }

        return PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $application,
            $capability);
    }

    /**
     * @param $capability
     * @param PhabricatorUser $viewer
     * @throws Exception
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    protected function requireApplicationCapability(
        $capability,
        PhabricatorUser $viewer)
    {

        $application = $this->getApplication();
        if (!$application) {
            return;
        }

        PhabricatorPolicyFilter::requireCapability(
            $viewer,
            $this->getApplication(),
            $capability);
    }
}

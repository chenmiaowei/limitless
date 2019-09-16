<?php

namespace orangins\modules\conduit\query;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\conduit\application\PhabricatorConduitApplication;
use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\conduit\models\PhabricatorConduitMethodCallLog;
use orangins\modules\people\searchfield\PhabricatorUsersSearchField;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\field\PhabricatorSearchCheckboxesField;
use orangins\modules\search\field\PhabricatorSearchStringListField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;
use PhutilNumber;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorConduitLogSearchEngine
 * @package orangins\modules\conduit\query
 * @author 陈妙威
 */
final class PhabricatorConduitLogSearchEngine
    extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app",'Conduit Logs');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorConduitApplication::class;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function canUseInPanelContext()
    {
        return false;
    }

    /**
     * @return PhabricatorConduitLogQuery
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    public function newQuery()
    {
        return PhabricatorConduitMethodCallLog::find();
    }

    /**
     * @param array $map
     * @return PhabricatorConduitLogQuery
     * @author 陈妙威
     * @throws \yii\base\InvalidConfigException
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        if ($map['callerPHIDs']) {
            $query->withCallerPHIDs($map['callerPHIDs']);
        }

        if ($map['methods']) {
            $query->withMethods($map['methods']);
        }

        if ($map['statuses']) {
            $query->withMethodStatuses($map['statuses']);
        }

        return $query;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function buildCustomSearchFields()
    {
        return array(
            (new PhabricatorUsersSearchField())
                ->setKey('callerPHIDs')
                ->setLabel(\Yii::t("app",'Callers'))
                ->setAliases(array('caller', 'callers'))
                ->setDescription(\Yii::t("app",'Find calls by specific users.')),
            (new PhabricatorSearchStringListField())
                ->setKey('methods')
                ->setLabel(\Yii::t("app",'Methods'))
                ->setDescription(\Yii::t("app",'Find calls to specific methods.')),
            (new PhabricatorSearchCheckboxesField())
                ->setKey('statuses')
                ->setLabel(\Yii::t("app",'Method Status'))
                ->setAliases(array('status'))
                ->setDescription(
                    \Yii::t("app",'Find calls to stable, unstable, or deprecated methods.'))
                ->setOptions(ConduitAPIMethod::getMethodStatusMap()),
        );
    }

    /**
     * @param $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge([
            '/conduit/log/' . $path
        ], $params));
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws \PhutilInvalidStateException
     */
    protected function getBuiltinQueryNames()
    {
        $names = array();

        $viewer = $this->requireViewer();
        if ($viewer->isLoggedIn()) {
            $names['viewer'] = \Yii::t("app",'My Calls');
            $names['viewerdeprecated'] = \Yii::t("app",'My Deprecated Calls');
        }

        $names['all'] = \Yii::t("app",'All Call Logs');
        $names['deprecated'] = \Yii::t("app",'Deprecated Call Logs');

        return $names;
    }

    /**
     * @param $query_key
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildSavedQueryFromBuiltin($query_key)
    {
        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);

        $viewer = $this->requireViewer();
        $viewer_phid = $viewer->getPHID();

        $deprecated = array(
            ConduitAPIMethod::METHOD_STATUS_DEPRECATED,
        );

        switch ($query_key) {
            case 'viewer':
                return $query
                    ->setParameter('callerPHIDs', array($viewer_phid));
            case 'viewerdeprecated':
                return $query
                    ->setParameter('callerPHIDs', array($viewer_phid))
                    ->setParameter('statuses', $deprecated);
            case 'deprecated':
                return $query
                    ->setParameter('statuses', $deprecated);
            case 'all':
                return $query;
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $logs
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    protected function renderResultList(
        array $logs,
        PhabricatorSavedQuery $query,
        array $handles)
    {
        assert_instances_of($logs, 'PhabricatorConduitMethodCallLog');
        $viewer = $this->requireViewer();

        $methods = (new PhabricatorConduitMethodQuery())
            ->setViewer($viewer)
            ->execute();
        $methods = mpull($methods, null, 'getAPIMethodName');

        JavelinHtml::initBehavior(new JavelinTooltipAsset());

        $viewer = $this->requireViewer();
        $rows = array();
        foreach ($logs as $log) {
            $caller_phid = $log->getCallerPHID();

            if ($caller_phid) {
                $caller = $viewer->renderHandle($caller_phid);
            } else {
                $caller = null;
            }

            $method = ArrayHelper::getValue($methods, $log->getMethod());
            if ($method) {
                $method_status = $method->getMethodStatus();
            } else {
                $method_status = null;
            }

            switch ($method_status) {
                case ConduitAPIMethod::METHOD_STATUS_STABLE:
                    $status = null;
                    break;
                case ConduitAPIMethod::METHOD_STATUS_UNSTABLE:
                    $status = (new PHUIIconView())
                        ->setIcon('fa-exclamation-triangle yellow')
                        ->addSigil('has-tooltip')
                        ->setMetadata(
                            array(
                                'tip' => \Yii::t("app",'Unstable'),
                            ));
                    break;
                case ConduitAPIMethod::METHOD_STATUS_DEPRECATED:
                    $status = (new PHUIIconView())
                        ->setIcon('fa-exclamation-triangle red')
                        ->addSigil('has-tooltip')
                        ->setMetadata(
                            array(
                                'tip' => \Yii::t("app",'Deprecated'),
                            ));
                    break;
                default:
                    $status = (new PHUIIconView())
                        ->setIcon('fa-question-circle')
                        ->addSigil('has-tooltip')
                        ->setMetadata(
                            array(
                                'tip' => \Yii::t("app",'Unknown ("%s")', $method_status),
                            ));
                    break;
            }

            $rows[] = array(
                $status,
                $log->getMethod(),
                $caller,
                $log->getError(),
                \Yii::t("app",'%s us', new PhutilNumber($log->getDuration())),
                OranginsViewUtil::phabricator_datetime($log->created_at, $viewer),
            );
        }

        $table = (new AphrontTableView($rows))
            ->setHeaders(
                array(
                    null,
                    \Yii::t("app",'Method'),
                    \Yii::t("app",'Caller'),
                    \Yii::t("app",'Error'),
                    \Yii::t("app",'Duration'),
                    \Yii::t("app",'Date'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    'pri',
                    null,
                    'wide right',
                    null,
                    null,
                ));

        return (new PhabricatorApplicationSearchResultView())
            ->setTable($table)
            ->setNoDataString(\Yii::t("app",'No matching calls in log.'));
    }
}

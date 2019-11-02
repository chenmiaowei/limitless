<?php

namespace orangins\modules\config\actions;

use AphrontQueryException;
use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\exception\ActiveRecordException;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\infrastructure\cluster\PhabricatorDatabaseRef;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\file\exception\PhabricatorFileStorageConfigurationException;
use orangins\modules\file\FilesystemException;
use orangins\modules\widgets\javelin\JavelinTooltipAsset;
use PhutilAggregateException;
use PhutilInvalidStateException;
use PhutilMethodNotImplementedException;
use PhutilNumber;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\db\IntegrityException;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConfigClusterDatabasesAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigClusterDatabasesAction
    extends PhabricatorConfigAction
{

    /**
     * @return PhabricatorStandardPageView
     * @throws PhutilMethodNotImplementedException
     * @throws ReflectionException
     * @throws AphrontQueryException
     * @throws PhutilAggregateException
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws Throwable
     * @throws ActiveRecordException
     * @throws FilesystemException
     * @throws PhabricatorFileStorageConfigurationException
     * @throws InvalidConfigException
     * @throws UnknownPropertyException
     * @throws IntegrityException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $nav = $this->buildSideNavView();
        $nav->selectFilter('cluster/databases/');

        $title = Yii::t("app", 'Cluster Database Status');
        $doc_href = PhabricatorEnv::getDoclink('Cluster: Databases');
        $button = (new PHUIButtonView())
            ->setIcon('fa-book')
            ->setHref($doc_href)
            ->setTag('a')
            ->setText(Yii::t("app", 'Documentation'));

        $header = $this->buildHeaderView($title, $button);

        $database_status = $this->buildClusterDatabaseStatus();
        $status = $this->buildConfigBoxView(Yii::t("app", 'Status'), $database_status);

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb($title)
            ->setBorder(true);

        $content = (new PHUITwoColumnView())
            ->setNavigation($nav)
            ->setFixed(true)
            ->setMainColumn($status);

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($content);
    }

    /**
     * @return mixed
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    private function buildClusterDatabaseStatus()
    {
        $viewer = $this->getViewer();

        $databases = PhabricatorDatabaseRef::queryAll();
        $connection_map = PhabricatorDatabaseRef::getConnectionStatusMap();
        $replica_map = PhabricatorDatabaseRef::getReplicaStatusMap();
        JavelinHtml::initBehavior(new JavelinTooltipAsset());

        $rows = array();
        foreach ($databases as $database) {
            $messages = array();

            if ($database->getIsMaster()) {
                $role_icon = (new PHUIIconView())
                    ->setIcon('fa-database sky')
                    ->addSigil('has-tooltip')
                    ->setMetadata(
                        array(
                            'tip' => Yii::t("app", 'Master'),
                        ));
            } else {
                $role_icon = (new PHUIIconView())
                    ->setIcon('fa-download')
                    ->addSigil('has-tooltip')
                    ->setMetadata(
                        array(
                            'tip' => Yii::t("app", 'Replica'),
                        ));
            }

            if ($database->getDisabled()) {
                $conn_icon = 'fa-times';
                $conn_color = 'grey';
                $conn_label = Yii::t("app", 'Disabled');
            } else {
                $status = $database->getConnectionStatus();

                $info = ArrayHelper::getValue($connection_map, $status, array());
                $conn_icon = ArrayHelper::getValue($info, 'icon');
                $conn_color = ArrayHelper::getValue($info, 'color');
                $conn_label = ArrayHelper::getValue($info, 'label');

                if ($status === PhabricatorDatabaseRef::STATUS_OKAY) {
                    $latency = $database->getConnectionLatency();
                    $latency = (int)(1000000 * $latency);
                    $conn_label = Yii::t("app", '{0} us', [
                        new PhutilNumber($latency)
                    ]);
                }
            }

            $connection = array(
                (new PHUIIconView())->setIcon("{$conn_icon} {$conn_color}"),
                ' ',
                $conn_label,
            );

            if ($database->getDisabled()) {
                $replica_icon = 'fa-times';
                $replica_color = 'grey';
                $replica_label = Yii::t("app", 'Disabled');
            } else {
                $status = $database->getReplicaStatus();

                $info = ArrayHelper::getValue($replica_map, $status, array());
                $replica_icon = ArrayHelper::getValue($info, 'icon');
                $replica_color = ArrayHelper::getValue($info, 'color');
                $replica_label = ArrayHelper::getValue($info, 'label');

                if ($database->getIsMaster()) {
                    if ($status === PhabricatorDatabaseRef::REPLICATION_OKAY) {
                        $replica_icon = 'fa-database';
                    }
                } else {
                    switch ($status) {
                        case PhabricatorDatabaseRef::REPLICATION_OKAY:
                        case PhabricatorDatabaseRef::REPLICATION_SLOW:
                            $delay = $database->getReplicaDelay();
                            if ($delay) {
                                $replica_label = Yii::t("app", '{0}s Behind',[ new PhutilNumber($delay)]);
                            } else {
                                $replica_label = Yii::t("app", 'Up to Date');
                            }
                            break;
                    }
                }
            }

            $replication = array(
                (new PHUIIconView())->setIcon("{$replica_icon} {$replica_color}"),
                ' ',
                $replica_label,
            );

            $health = $database->getHealthRecord();
            $health_up = $health->getUpEventCount();
            $health_down = $health->getDownEventCount();

            if ($health->getIsHealthy()) {
                $health_icon = (new PHUIIconView())
                    ->setIcon('fa-plus green');
            } else {
                $health_icon = (new PHUIIconView())
                    ->setIcon('fa-times red');
                $messages[] = Yii::t("app",
                    'UNHEALTHY: This database has failed recent health checks. Traffic ' .
                    'will not be sent to it until it recovers.');
            }

            $health_count = Yii::t("app",
                '{0} / {1}',
                [
                    new PhutilNumber($health_up),
                    new PhutilNumber($health_up + $health_down)
                ]);

            $health_status = array(
                $health_icon,
                ' ',
                $health_count,
            );

            $conn_message = $database->getConnectionMessage();
            if ($conn_message) {
                $messages[] = $conn_message;
            }

            $replica_message = $database->getReplicaMessage();
            if ($replica_message) {
                $messages[] = $replica_message;
            }

            $messages = phutil_implode_html(phutil_tag('br'), $messages);

            $partition = null;
            if ($database->getIsMaster()) {
                if ($database->getIsDefaultPartition()) {
                    $partition = (new PHUIIconView())
                        ->setIcon('fa-circle sky')
                        ->addSigil('has-tooltip')
                        ->setMetadata(
                            array(
                                'tip' => Yii::t("app", 'Default Partition'),
                            ));
                } else {
                    $map = $database->getApplicationMap();
                    if ($map) {
                        $list = implode(', ', $map);
                    } else {
                        $list = Yii::t("app", 'Empty');
                    }

                    $partition = (new PHUIIconView())
                        ->setIcon('fa-adjust sky')
                        ->addSigil('has-tooltip')
                        ->setMetadata(
                            array(
                                'tip' => Yii::t("app", 'Partition: {0}', [$list]),
                            ));
                }
            }

            $rows[] = array(
                $role_icon,
                $partition,
                $database->getHost(),
                $database->getPort(),
                $database->getUser(),
                $connection,
                $replication,
                $health_status,
                $messages,
            );
        }


        $table = (new AphrontTableView($rows))
            ->setNoDataString(
                Yii::t("app", 'Phabricator is not configured in cluster mode.'))
            ->setHeaders(
                array(
                    null,
                    null,
                    Yii::t("app", 'Host'),
                    Yii::t("app", 'Port'),
                    Yii::t("app", 'User'),
                    Yii::t("app", 'Connection'),
                    Yii::t("app", 'Replication'),
                    Yii::t("app", 'Health'),
                    Yii::t("app", 'Messages'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    'wide',
                ));

        return $table;
    }

}

<?php

namespace orangins\modules\config\actions;

use AphrontQueryException;
use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\exception\ActiveRecordException;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\file\exception\PhabricatorFileStorageConfigurationException;
use orangins\modules\file\FilesystemException;
use orangins\modules\notification\client\PhabricatorNotificationServerRef;
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
 * Class PhabricatorConfigClusterNotificationsAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigClusterNotificationsAction
    extends PhabricatorConfigAction
{

    /**
     * @return PhabricatorStandardPageView
     * @throws AphrontQueryException
     * @throws PhutilAggregateException
     * @throws PhutilInvalidStateException
     * @throws PhutilMethodNotImplementedException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
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
        $nav->selectFilter('cluster/notifications/');

        $title = Yii::t("app",'Cluster Notifications');
        $doc_href = PhabricatorEnv::getDoclink('Cluster: Notifications');
        $button = (new PHUIButtonView())
            ->setIcon('fa-book')
            ->setHref($doc_href)
            ->setTag('a')
            ->setText(Yii::t("app",'Documentation'));

        $header = $this->buildHeaderView($title, $button);

        $notification_status = $this->buildClusterNotificationStatus();
        $status = $this->buildConfigBoxView(
            Yii::t("app",'Notifications Status'),
            $notification_status);

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
    private function buildClusterNotificationStatus()
    {
        $viewer = $this->getViewer();

        $servers = PhabricatorNotificationServerRef::newRefs();
        JavelinHtml::initBehavior(new JavelinTooltipAsset());

        $rows = array();
        foreach ($servers as $server) {
            if ($server->isAdminServer()) {
                $type_icon = 'fa-database sky';
                $type_tip = Yii::t("app",'Admin Server');
            } else {
                $type_icon = 'fa-bell sky';
                $type_tip = Yii::t("app",'Client Server');
            }

            $type_icon = (new PHUIIconView())
                ->setIcon($type_icon)
                ->addSigil('has-tooltip')
                ->setMetadata(
                    array(
                        'tip' => $type_tip,
                    ));

            $messages = array();

            $details = array();
            if ($server->isAdminServer()) {
                try {
                    $details = $server->loadServerStatus();
                    $status_icon = 'fa-exchange green';
                    $status_label = Yii::t("app",'Version %s', ArrayHelper::getValue($details, 'version'));
                } catch (Exception $ex) {
                    $status_icon = 'fa-times red';
                    $status_label = Yii::t("app",'Connection Error');
                    $messages[] = $ex->getMessage();
                }
            } else {
                try {
                    $server->testClient();
                    $status_icon = 'fa-exchange green';
                    $status_label = Yii::t("app",'Connected');
                } catch (Exception $ex) {
                    $status_icon = 'fa-times red';
                    $status_label = Yii::t("app",'Connection Error');
                    $messages[] = $ex->getMessage();
                }
            }

            if ($details) {
                $uptime = ArrayHelper::getValue($details, 'uptime');
                $uptime = $uptime / 1000;
                $uptime = phutil_format_relative_time_detailed($uptime);

                $clients = Yii::t("app",
                    '%s Active / %s Total',
                    new PhutilNumber(ArrayHelper::getValue($details, 'clients.active')),
                    new PhutilNumber(ArrayHelper::getValue($details, 'clients.total')));

                $stats = Yii::t("app",
                    '%s In / %s Out',
                    new PhutilNumber(ArrayHelper::getValue($details, 'messages.in')),
                    new PhutilNumber(ArrayHelper::getValue($details, 'messages.out')));

                if (ArrayHelper::getValue($details, 'history.size')) {
                    $history = Yii::t("app",
                        '%s Held / %sms',
                        new PhutilNumber(ArrayHelper::getValue($details, 'history.size')),
                        new PhutilNumber(ArrayHelper::getValue($details, 'history.age')));
                } else {
                    $history = Yii::t("app",'No Messages');
                }

            } else {
                $uptime = null;
                $clients = null;
                $stats = null;
                $history = null;
            }

            $status_view = array(
                (new PHUIIconView())->setIcon($status_icon),
                ' ',
                $status_label,
            );

            $messages = phutil_implode_html(phutil_tag('br'), $messages);

            $rows[] = array(
                $type_icon,
                $server->getProtocol(),
                $server->getHost(),
                $server->getPort(),
                $status_view,
                $uptime,
                $clients,
                $stats,
                $history,
                $messages,
            );
        }

        $table = (new AphrontTableView($rows))
            ->setNoDataString(
                Yii::t("app",'No notification servers are configured.'))
            ->setHeaders(
                array(
                    null,
                    Yii::t("app",'Proto'),
                    Yii::t("app",'Host'),
                    Yii::t("app",'Port'),
                    Yii::t("app",'Status'),
                    Yii::t("app",'Uptime'),
                    Yii::t("app",'Clients'),
                    Yii::t("app",'Messages'),
                    Yii::t("app",'History'),
                    null,
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
                    null,
                    'wide',
                ));

        return $table;
    }

}

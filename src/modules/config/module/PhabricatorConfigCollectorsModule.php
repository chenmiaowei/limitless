<?php

namespace orangins\modules\config\module;

use orangins\lib\infrastructure\daemon\garbagecollector\PhabricatorGarbageCollector;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIInfoView;
use PhutilNumber;

/**
 * Class PhabricatorConfigCollectorsModule
 * @package orangins\modules\config\module
 * @author 陈妙威
 */
final class PhabricatorConfigCollectorsModule extends PhabricatorConfigModule
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleKey()
    {
        return 'collectors';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleName()
    {
        return \Yii::t("app", 'Garbage Collectors');
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $collectors = PhabricatorGarbageCollector::getAllCollectors();
        $collectors = msort($collectors, 'getCollectorConstant');

        $rows = array();
        $rowc = array();
        foreach ($collectors as $key => $collector) {
            $class = null;
            if ($collector->hasAutomaticPolicy()) {
                $policy_view = phutil_tag('em', array(), \Yii::t("app", 'Automatic'));
            } else {
                $policy = $collector->getRetentionPolicy();
                if ($policy === null) {
                    $policy_view = \Yii::t("app", 'Indefinite');
                } else {
                    $days = ceil($policy / phutil_units('1 day in seconds'));
                    $policy_view = \Yii::t("app",
                        '{0} Day(s)', [$days]);
                }

                $default = $collector->getDefaultRetentionPolicy();
                if ($policy !== $default) {
                    $class = 'highlighted';
                    $policy_view = phutil_tag('strong', array(), $policy_view);
                }
            }

            $rowc[] = $class;
            $rows[] = array(
                $collector->getCollectorConstant(),
                $collector->getCollectorName(),
                $policy_view,
            );
        }

        $info = (new PHUIInfoView())
            ->setSeverity(PHUIInfoView::SEVERITY_NOTICE)
            ->appendChild(\Yii::t("app",
                'Collectors with custom policies are highlighted. Use ' .
                '%s to change retention policies.',
                phutil_tag('tt', array(), 'bin/garbage set-policy')));

        $table = (new AphrontTableView($rows))
            ->setNotice($info)
            ->setRowClasses($rowc)
            ->setHeaders(
                array(
                    \Yii::t("app", 'Constant'),
                    \Yii::t("app", 'Name'),
                    \Yii::t("app", 'Retention Policy'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    'pri wide',
                    null,
                ));

        return $table;
    }

}

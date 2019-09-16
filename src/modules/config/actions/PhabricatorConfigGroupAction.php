<?php

namespace orangins\modules\config\actions;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\config\constants\PhabricatorConfigGroupConstants;
use orangins\modules\config\json\PhabricatorConfigJSON;
use orangins\modules\config\models\PhabricatorConfigEntry;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use orangins\modules\config\option\PhabricatorConfigOption;
use PhutilSafeHTML;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorConfigGroupAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigGroupAction
    extends PhabricatorConfigAction
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView|Aphront404Response
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();
        $group_key = $request->getURIData('key');

        $groups = PhabricatorApplicationConfigOptions::loadAll();
        /** @var PhabricatorApplicationConfigOptions $options */
        $options = ArrayHelper::getValue($groups, $group_key);
        if (!$options) {
            return new Aphront404Response();
        }

        $group_uri = PhabricatorConfigGroupConstants::getGroupFullURI($options->getGroup());
        $group_name = PhabricatorConfigGroupConstants::getGroupShortName($options->getGroup());

        $nav = $this->buildSideNavView();
        $nav->addClass("w-lg-100");
        $nav->selectFilter($group_uri);

        $title = \Yii::t("app",'{0} Configuration', [$options->getName()]);
        $header = $this->buildHeaderView($title);
        $list = $this->buildOptionList($options->getOptions());
        $group_url = JavelinHtml::phutil_tag('a', array('href' => $group_uri), $group_name);

        $box_header = new PhutilSafeHTML(\Yii::t("app","{0} \xC2\xBB {1}", [$group_url, $options->getName()]));
        $view = $this->buildConfigBoxView($box_header, $list);

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb($group_name, $group_uri)
            ->addTextCrumb($options->getName())
            ->setBorder(true);

        $content = (new PHUITwoColumnView())
            ->setNavigation($nav)
            ->setFixed(true)
            ->setMainColumn($view);

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($content);
    }

    /**
     * @param array $options
     * @return PHUIObjectItemListView
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildOptionList(array $options)
    {
        assert_instances_of($options, PhabricatorConfigOption::class);
        $db_values = array();
        if ($options) {
            $db_values = PhabricatorConfigEntry::find()->andWhere([
                'AND',
                ['IN', 'config_key', mpull($options, 'getKey')],
                ['namespace' => 'default']
            ])->all();
            $db_values = mpull($db_values, null, 'getConfigKey');
        }

        $list = new PHUIObjectItemListView();
        $list->setBig(true);
        foreach ($options as $option) {
            $summary = $option->getSummary();

            $item = (new PHUIObjectItemView())
                ->setHeader($option->getKey())
                ->setHref(Url::to(['/config/index/edit', 'key' =>  $option->getKey()]))
                ->addAttribute($summary);

            $color = null;
            $db_value = ArrayHelper::getValue($db_values, $option->getKey());
            if ($db_value && !$db_value->getIsDeleted()) {
                $item->setEffect('visited');
                $color = 'violet';
            }

            if ($option->getHidden()) {
                $item->setStatusIcon('fa-eye-slash grey', \Yii::t("app",'Hidden'));
                $item->setDisabled(true);
            } else if ($option->getLocked()) {
                $item->setStatusIcon('fa-lock ' . $color, \Yii::t("app",'Locked'));
            } else if ($color) {
                $item->setStatusIcon('fa-pencil ' . $color, \Yii::t("app",'Editable'));
            } else {
                $item->setStatusIcon('fa-pencil-square-o ' . $color, \Yii::t("app",'Editable'));
            }

            if (!$option->getHidden()) {
                $current_value = PhabricatorEnv::getEnvConfig($option->getKey());
                $current_value = PhabricatorConfigJSON::prettyPrintJSON(
                    $current_value);
                $current_value = phutil_tag(
                    'div',
                    array(
                        'class' => 'config-options-current-value ' . $color,
                    ),
                    array(
                        $current_value,
                    ));

                $item->setSideColumn($current_value);
            }

            $list->addItem($item);
        }

        return $list;
    }

}

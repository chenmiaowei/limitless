<?php

namespace orangins\modules\config\actions;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\page\PhabricatorStandardPageView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\config\json\PhabricatorConfigJSON;
use orangins\modules\config\models\PhabricatorConfigEntry;
use orangins\modules\config\option\PhabricatorApplicationConfigOptions;
use PhutilMethodNotImplementedException;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConfigAllAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigAllAction
    extends PhabricatorConfigAction
{

    /**
     * @return PhabricatorStandardPageView
     * @throws \yii\base\Exception
     * @throws PhutilMethodNotImplementedException
     * @throws Exception
     * @author 陈妙威
     */
    public function run()
    {
        $db_values = PhabricatorConfigEntry::find()->andWhere(['namespace' => 'default'])->all();
        $db_values = mpull($db_values, null, 'getConfigKey');
        $rows = array();
        $options = PhabricatorApplicationConfigOptions::loadAllOptions();
        ksort($options);
        foreach ($options as $option) {
            $key = $option->getKey();

            if ($option->getHidden()) {

                $value = JavelinHtml::phutil_tag('em', array(), Yii::t("app", 'Hidden'));
            } else {
                $value = PhabricatorEnv::getEnvConfig($key);
                $value = PhabricatorConfigJSON::prettyPrintJSON($value);
            }

            $db_value = ArrayHelper::getValue($db_values, $key);
            $rows[] = array(
                JavelinHtml::phutil_tag(
                    'a',
                    array(
                        'href' => $this->getApplicationURI('index/edit', [
                            'key' => $key
                        ]),
                    ),
                    $key),
                $value,
                $db_value && !$db_value->getIsDeleted() ? Yii::t("app", 'Customized') : '',
            );
        }
        $table = (new AphrontTableView($rows))
            ->setColumnClasses(
                array(
                    '',
                    'wide',
                ))
            ->setHeaders(
                array(
                    Yii::t("app", 'Key'),
                    Yii::t("app", 'Value'),
                    Yii::t("app", 'Customized'),
                ));

        $title = Yii::t("app", 'Current Settings');
        $header = $this->buildHeaderView($title);

        $nav = $this->buildSideNavView();
        $nav->selectFilter('all/');

        $view = $this->buildConfigBoxView(
            Yii::t("app", 'All Settings'),
            $table);

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb($title)
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

}

<?php

namespace orangins\lib\view\page\menu;

use Exception;
use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\helpers\OranginsUtil;
use orangins\lib\PhabricatorApplication;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\search\actions\PhabricatorSearchAction;
use orangins\modules\search\assets\JavelinSearchTypeheadAsset;
use orangins\modules\search\query\PhabricatorSearchApplicationSearchEngine;
use orangins\modules\search\typeahead\PhabricatorSearchDatasource;
use orangins\modules\settings\setting\PhabricatorSearchScopeSetting;
use orangins\lib\view\AphrontView;
use PhutilInvalidStateException;
use ReflectionException;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorMainMenuSearchView
 * @package orangins\lib\view\page\menu
 * @author 陈妙威
 */
final class PhabricatorMainMenuSearchView extends AphrontView
{

    /**
     *
     */
    const DEFAULT_APPLICATION_ICON = 'fa-dot-circle-o';

    /**
     * @var
     */
    private $id;
    /**
     * @var
     */
    private $application;

    /**
     * @param PhabricatorApplication $application
     * @return $this
     * @author 陈妙威
     */
    public function setApplication(PhabricatorApplication $application)
    {
        $this->application = $application;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getID()
    {
        if (!$this->id) {
            $this->id = JavelinHtml::generateUniqueNodeId();
        }
        return $this->id;
    }

    /**
     * @return mixed
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws Exception
     * @author 陈妙威
     */
    public function render()
    {
        $viewer = $this->getViewer();

        $target_id = JavelinHtml::generateUniqueNodeId();
        $search_id = $this->getID();
        $button_id = JavelinHtml::generateUniqueNodeId();
        $selector_id = JavelinHtml::generateUniqueNodeId();
        $application_id = JavelinHtml::generateUniqueNodeId();

        $input = JavelinHtml::phutil_tag(
            'input',
            array(
                'type' => 'text',
                'name' => 'query',
//                'class' => 'rounded-round',
                'id' => $search_id,
                'autocomplete' => 'off',
                'autocorrect' => 'off',
                'autocapitalize' => 'off',
                'spellcheck' => 'false',
            ));

        $target = JavelinHtml::phutil_tag(
            'div',
            array(
                'id' => $target_id,
                'class' => 'phabricator-main-menu-search-target',
            ),
            '');

        $search_datasource = new PhabricatorSearchDatasource();
        $scope_key = PhabricatorSearchScopeSetting::SETTINGKEY;

        JavelinHtml::initBehavior(
            new JavelinSearchTypeheadAsset(),
            array(
                'id' => $target_id,
                'input' => $search_id,
                'button' => $button_id,
                'selectorID' => $selector_id,
                'applicationID' => $application_id,
                'defaultApplicationIcon' => self::DEFAULT_APPLICATION_ICON,
                'appScope' => PhabricatorSearchAction::SCOPE_CURRENT_APPLICATION,
                'src' => $search_datasource->getDatasourceURI(),
                'limit' => 10,
                'placeholder' => Yii::t("app", 'Search'),
                'scopeUpdateURI' => Url::to(['/settings/index/adjust', 'key' => $scope_key]),
            ));

        $primary_input = JavelinHtml::phutil_tag(
            'input',
            array(
                'type' => 'hidden',
                'name' => 'search:primary',
                'value' => 'true',
            ));

        $search_text = JavelinHtml::phutil_tag(
            'span',
            array(
                'aural' => true,
            ),
            Yii::t("app", 'Search'));

        $selector = $this->buildModeSelector($selector_id, $application_id);


        $form = JavelinHtml::phabricator_form(
            $viewer,
            array(
                'action' => Url::to(['/search/index/query']),
                'method' => 'POST',
            ),
            JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'phabricator-main-menu-search-container p-0',
                ),
                array(
                    $input,
                    JavelinHtml::phutil_tag('div', [
                        'class' => 'input-group-append'
                    ], JavelinHtml::phutil_tag(
                        'button',
                        array(
                            'id' => $button_id,
                            'class' => 'phui-icon-view fa fa-search',
                        ),
                        $search_text)),
                    $selector,
                    $primary_input,
                    $target,
                )));

        return $form;
    }

    /**
     * @param $selector_id
     * @param $application_id
     * @return array
     * @throws ReflectionException
     * @throws PhutilInvalidStateException*@throws \Exception
     * @throws Exception
     * @author 陈妙威
     */
    private function buildModeSelector($selector_id, $application_id)
    {
        $viewer = $this->getViewer();

        $items = array();
        $items[] = array(
            'name' => Yii::t("app", 'Search'),
        );

        $items[] = array(
            'icon' => 'fa-globe',
            'name' => Yii::t("app", 'All Documents'),
            'value' => 'all',
        );

        $application_value = null;
        $application_icon = self::DEFAULT_APPLICATION_ICON;
        $application = $this->getApplication();
        if ($application) {
            $application_value = get_class($application);
            if ($application->getApplicationSearchDocumentTypes()) {
                $application_icon = $application->getIcon();
            }
        }

//        $items[] = array(
//            'icon' => $application_icon,
//            'name' => \Yii::t("app", 'Current Application'),
//            'value' => PhabricatorSearchAction::SCOPE_CURRENT_APPLICATION,
//        );

        $items[] = array(
            'name' => Yii::t("app", 'Saved Queries'),
        );


        $engine = (new PhabricatorSearchApplicationSearchEngine())
            ->setViewer($viewer);
        $engine_queries = $engine->loadEnabledNamedQueries();
        $query_map = OranginsUtil::mpull($engine_queries, 'getQueryName', 'getQueryKey');
        foreach ($query_map as $query_key => $query_name) {
            if ($query_key == 'all') {
                // Skip the builtin "All" query since it's redundant with the default
                // setting.
                continue;
            }

            $items[] = array(
                'icon' => 'fa-certificate',
                'name' => $query_name,
                'value' => $query_key,
            );
        }

        $items[] = array(
            'name' => Yii::t("app", 'More Options'),
        );

        $items[] = array(
            'icon' => 'fa-search-plus',
            'name' => Yii::t("app", 'Advanced Search'),
            'href' => Url::to(['/search/index/query', 'queryKey' => 'advanced']),
        );

        $scope_key = PhabricatorSearchScopeSetting::SETTINGKEY;
        $current_value = $viewer->getUserSetting($scope_key);

        $current_icon = 'fa-globe';
        foreach ($items as $item) {
            if (ArrayHelper::getValue($item, 'value') == $current_value) {
                $current_icon = ArrayHelper::getValue($item, 'icon');
                break;
            }
        }

        $selector = (new PHUIButtonView())
            ->setID($selector_id)
            ->addClass('phabricator-main-menu-search-dropdown')
            ->addSigil('global-search-dropdown')
            ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"))
            ->setMetadata(
                array(
                    'items' => $items,
                    'icon' => $current_icon,
                    'value' => $current_value,
                ))
            ->setIcon(
                (new PHUIIconView())
                    ->addSigil('global-search-dropdown-icon')
                    ->setIcon($current_icon))
            ->setAuralLabel(Yii::t("app", 'Configure Global Search'))
            ->setDropdown(true);

        $input = JavelinHtml::phutil_tag(
            'input',
            array(
                'type' => 'hidden',
                'sigil' => 'global-search-dropdown-input',
                'name' => 'search:scope',
                'value' => $current_value,
            ));

        $application_input = JavelinHtml::phutil_tag(
            'input',
            array(
                'type' => 'hidden',
                'id' => $application_id,
                'sigil' => 'global-search-dropdown-app',
                'name' => 'search:application',
                'value' => $application_value,
            ));

        return array($selector, $input, $application_input);
    }

}

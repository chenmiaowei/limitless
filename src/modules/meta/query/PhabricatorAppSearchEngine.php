<?php

namespace orangins\modules\meta\query;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\PhabricatorApplication;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\lib\view\phui\PHUITagView;
use orangins\modules\meta\application\PhabricatorApplicationsApplication;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorAppSearchEngine
 * @package orangins\modules\meta\query
 * @author 陈妙威
 */
final class PhabricatorAppSearchEngine extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app", 'Applications');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorApplicationsApplication::className();
    }

    /**
     * @param PhabricatorSavedQuery $saved
     * @return int
     * @author 陈妙威
     */
    public function getPageSize(PhabricatorSavedQuery $saved)
    {
        return INF;
    }

    /**
     * @param AphrontRequest $request
     * @return PhabricatorSavedQuery|\orangins\modules\search\models\PhabricatorSavedQuery

     * @author 陈妙威
     */
    public function buildSavedQueryFromRequest(AphrontRequest $request)
    {
        $saved = new PhabricatorSavedQuery();

        $saved->setParameter('name', $request->getStr('name'));

        $saved->setParameter(
            'installed',
            $this->readBoolFromRequest($request, 'installed'));
        $saved->setParameter(
            'prototypes',
            $this->readBoolFromRequest($request, 'prototypes'));
        $saved->setParameter(
            'firstParty',
            $this->readBoolFromRequest($request, 'firstParty'));
        $saved->setParameter(
            'launchable',
            $this->readBoolFromRequest($request, 'launchable'));
        $saved->setParameter(
            'appemails',
            $this->readBoolFromRequest($request, 'appemails'));

        return $saved;
    }

    /**
     * @param PhabricatorSavedQuery $saved
     * @return PhabricatorApplicationQuery

     * @author 陈妙威
     */
    public function buildQueryFromSavedQuery(PhabricatorSavedQuery $saved)
    {
        $query = (new PhabricatorApplicationQuery())
            ->setOrder(PhabricatorApplicationQuery::ORDER_NAME)
            ->withUnlisted(false);

        $name = $saved->getParameter('name');
        if (strlen($name)) {
            $query->withNameContains($name);
        }

        $installed = $saved->getParameter('installed');
        if ($installed !== null) {
            $query->withInstalled($installed);
        }

        $prototypes = $saved->getParameter('prototypes');

        if ($prototypes === null) {
            // NOTE: This is the old name of the 'prototypes' option, see T6084.
            $prototypes = $saved->getParameter('beta');
            $saved->setParameter('prototypes', $prototypes);
        }

        if ($prototypes !== null) {
            $query->withPrototypes($prototypes);
        }

        $first_party = $saved->getParameter('firstParty');
        if ($first_party !== null) {
            $query->withFirstParty($first_party);
        }

        $launchable = $saved->getParameter('launchable');
        if ($launchable !== null) {
            $query->withLaunchable($launchable);
        }

        $appemails = $saved->getParameter('appemails');
        if ($appemails !== null) {
            $query->withApplicationEmailSupport($appemails);
        }

        return $query;
    }

    /**
     * @param AphrontFormView $form
     * @param PhabricatorSavedQuery $saved

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildSearchForm(AphrontFormView $form, PhabricatorSavedQuery $saved)
    {

        $form
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'Name Contains'))
                    ->setName('name')
                    ->setValue($saved->getParameter('name')))
            ->appendChild(
                (new AphrontFormSelectControl())
                    ->setLabel(\Yii::t("app", 'Installed'))
                    ->setName('installed')
                    ->setValue($this->getBoolFromQuery($saved, 'installed'))
                    ->setOptions(
                        array(
                            '' => \Yii::t("app", 'Show All Applications'),
                            'true' => \Yii::t("app", 'Show Installed Applications'),
                            'false' => \Yii::t("app", 'Show Uninstalled Applications'),
                        )))
            ->appendChild(
                (new AphrontFormSelectControl())
                    ->setLabel(\Yii::t("app", 'Prototypes'))
                    ->setName('prototypes')
                    ->setValue($this->getBoolFromQuery($saved, 'prototypes'))
                    ->setOptions(
                        array(
                            '' => \Yii::t("app", 'Show All Applications'),
                            'true' => \Yii::t("app", 'Show Prototype Applications'),
                            'false' => \Yii::t("app", 'Show Released Applications'),
                        )))
            ->appendChild(
                (new AphrontFormSelectControl())
                    ->setLabel(\Yii::t("app", 'Provenance'))
                    ->setName('firstParty')
                    ->setValue($this->getBoolFromQuery($saved, 'firstParty'))
                    ->setOptions(
                        array(
                            '' => \Yii::t("app", 'Show All Applications'),
                            'true' => \Yii::t("app", 'Show First-Party Applications'),
                            'false' => \Yii::t("app", 'Show Third-Party Applications'),
                        )))
            ->appendChild(
                (new AphrontFormSelectControl())
                    ->setLabel(\Yii::t("app", 'Launchable'))
                    ->setName('launchable')
                    ->setValue($this->getBoolFromQuery($saved, 'launchable'))
                    ->setOptions(
                        array(
                            '' => \Yii::t("app", 'Show All Applications'),
                            'true' => \Yii::t("app", 'Show Launchable Applications'),
                            'false' => \Yii::t("app", 'Show Non-Launchable Applications'),
                        )))
            ->appendChild(
                (new AphrontFormSelectControl())
                    ->setLabel(\Yii::t("app", 'Application Emails'))
                    ->setName('appemails')
                    ->setValue($this->getBoolFromQuery($saved, 'appemails'))
                    ->setOptions(
                        array(
                            '' => \Yii::t("app", 'Show All Applications'),
                            'true' => \Yii::t("app", 'Show Applications w/ App Email Support'),
                            'false' => \Yii::t("app", 'Show Applications w/o App Email Support'),
                        )));
    }

    /**
     * @param $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge(['/meta/index/' . $path], $params));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        return array(
            'launcher' => \Yii::t("app", 'Launcher'),
            'all' => \Yii::t("app", 'All Applications'),
        );
    }

    /**
     * @param $query_key
     * @return \orangins\modules\search\models\PhabricatorSavedQuery
     * @throws \ReflectionException

     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function buildSavedQueryFromBuiltin($query_key)
    {
        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);

        switch ($query_key) {
            case 'launcher':
                return $query
                    ->setParameter('installed', true)
                    ->setParameter('launchable', true);
            case 'all':
                return $query;
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $all_applications
     * @param PhabricatorSavedQuery $query
     * @param array $handle
     * @return PhabricatorApplicationSearchResultView|mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @throws \PhutilMethodNotImplementedException
     */
    protected function renderResultList(
        array $all_applications,
        PhabricatorSavedQuery $query,
        array $handle)
    {
        assert_instances_of($all_applications, PhabricatorApplication::class);

        $all_applications = msort($all_applications, 'getName');

        if ($query->getQueryKey() == 'launcher') {
            $groups = mgroup($all_applications, 'getApplicationGroup');
        } else {
            $groups = array($all_applications);
        }

        $group_names = PhabricatorApplication::getApplicationGroups();
        $groups = array_select_keys($groups, array_keys($group_names)) + $groups;

        $results = array();
        foreach ($groups as $group => $applications) {
            if (count($groups) > 1) {
                $results[] = JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' => 'card-header bg-light rounded-0 border-0 phui-oi-list-header',
                    ),
                    JavelinHtml::phutil_tag_div("card-title", ArrayHelper::getValue($group_names, $group, $group)));
            }

            $list = new PHUIObjectItemListView();

            /** @var PhabricatorApplication $application */
            foreach ($applications as $application) {
                $icon = $application->getIcon();
                if (!$icon) {
                    $icon = 'application';
                }

                $description = $application->getShortDescription();

//                $configure = (new PHUIButtonView())
//                    ->setTag('a')
//                    ->setIcon('fa-gears')
//                    ->setHref(Url::to([
//                        '/meta/index/view', 'application' => $application->getClassShortName()
//                    ]))
//                    ->setText(\Yii::t("app", 'Configure'))
//                    ->setColor(PhabricatorEnv::getEnvConfig("ui.widget-color"));

                $name = $application->getName();

                $item = (new PHUIObjectItemView())
                    ->setHeader($name)
                    ->setImageIcon($icon);
//                    ->setSideColumn($configure);

                if (!$application->isFirstParty()) {
                    $tag = (new PHUITagView())
                        ->setName(\Yii::t("app", 'Extension'))
                        ->setIcon('fa-puzzle-piece')
                        ->setColor(PHUITagView::COLOR_SUCCESS_600)
                        ->setType(PHUITagView::TYPE_SHADE)
                        ->setSlimShady(true);
                    $item->addAttribute($tag);
                }

                if ($application->isPrototype()) {
                    $prototype_tag = (new PHUITagView())
                        ->setName(\Yii::t("app", 'Prototype'))
                        ->setIcon('fa-exclamation-circle')
                        ->setColor(PHUITagView::COLOR_WARNING)
                        ->setType(PHUITagView::TYPE_SHADE)
                        ->setSlimShady(true);
                    $item->addAttribute($prototype_tag);
                }

                $item->addAttribute($description);

                if ($application->isInstalled()) {
                    $href = $application->getApplicationURI();
                    $item->setHref($href);
                }

                if (!$application->isInstalled()) {
                    $item->addAttribute(\Yii::t("app", 'Uninstalled'));
                    $item->setDisabled(true);
                }

                $list->addItem($item);
            }

            $results[] = $list;
        }

        $result = new PhabricatorApplicationSearchResultView();
        $result->setContent($results);

        return $result;
    }

}

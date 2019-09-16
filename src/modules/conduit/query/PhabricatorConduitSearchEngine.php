<?php

namespace orangins\modules\conduit\query;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormCheckboxControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\conduit\application\PhabricatorConduitApplication;
use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorConduitSearchEngine
 * @package orangins\modules\conduit\query
 * @author 陈妙威
 */
final class PhabricatorConduitSearchEngine
    extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app",'Conduit Methods');
    }

    /**
     * @return mixed|string
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
     * @param PhabricatorSavedQuery $saved
     * @return int
     * @author 陈妙威
     */
    public function getPageSize(PhabricatorSavedQuery $saved)
    {
        return PHP_INT_MAX - 1;
    }

    /**
     * @param AphrontRequest $request
     * @return PhabricatorSavedQuery
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSavedQueryFromRequest(AphrontRequest $request)
    {
        $saved = new PhabricatorSavedQuery();

        $saved->setParameter('isStable', $request->getStr('isStable'));
        $saved->setParameter('isUnstable', $request->getStr('isUnstable'));
        $saved->setParameter('isDeprecated', $request->getStr('isDeprecated'));
        $saved->setParameter('nameContains', $request->getStr('nameContains'));

        return $saved;
    }

    /**
     * @param PhabricatorSavedQuery $saved
     * @return PhabricatorConduitMethodQuery

     * @author 陈妙威
     */
    public function buildQueryFromSavedQuery(PhabricatorSavedQuery $saved)
    {
        $query = (new PhabricatorConduitMethodQuery());

        $query->withIsStable($saved->getParameter('isStable'));
        $query->withIsUnstable($saved->getParameter('isUnstable'));
        $query->withIsDeprecated($saved->getParameter('isDeprecated'));
        $query->withIsInternal(false);

        $contains = $saved->getParameter('nameContains');
        if (strlen($contains)) {
            $query->withNameContains($contains);
        }

        return $query;
    }

    /**
     * @param AphrontFormView $form
     * @param PhabricatorSavedQuery $saved

     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSearchForm(
        AphrontFormView $form,
        PhabricatorSavedQuery $saved)
    {

        $form
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app",'Name Contains'))
                    ->setName('nameContains')
                    ->setValue($saved->getParameter('nameContains')));

        $is_stable = $saved->getParameter('isStable');
        $is_unstable = $saved->getParameter('isUnstable');
        $is_deprecated = $saved->getParameter('isDeprecated');
        $form
            ->appendChild(
                (new AphrontFormCheckboxControl())
                    ->setLabel('Stability')
                    ->addCheckbox(
                        'isStable',
                        1,
                        hsprintf(
                            '<strong>%s</strong>: %s',
                            \Yii::t("app",'Stable Methods'),
                            \Yii::t("app",'Show established API methods with stable interfaces.')),
                        $is_stable)
                    ->addCheckbox(
                        'isUnstable',
                        1,
                        hsprintf(
                            '<strong>%s</strong>: %s',
                            \Yii::t("app",'Unstable Methods'),
                            \Yii::t("app",'Show new methods which are subject to change.')),
                        $is_unstable)
                    ->addCheckbox(
                        'isDeprecated',
                        1,
                        hsprintf(
                            '<strong>%s</strong>: %s',
                            \Yii::t("app",'Deprecated Methods'),
                            \Yii::t("app",
                                'Show old methods which will be deleted in a future ' .
                                'version of Phabricator.')),
                        $is_deprecated));
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
            '/conduit/index/' . $path
        ], $params));
    }


    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        return array(
            'modern' => \Yii::t("app",'Modern Methods'),
            'all' => \Yii::t("app",'All Methods'),
        );
    }

    /**
     * @param $query_key
     * @return mixed|PhabricatorSavedQuery
     * @throws \ReflectionException

     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSavedQueryFromBuiltin($query_key)
    {
        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);

        switch ($query_key) {
            case 'modern':
                return $query
                    ->setParameter('isStable', true)
                    ->setParameter('isUnstable', true);
            case 'all':
                return $query
                    ->setParameter('isStable', true)
                    ->setParameter('isUnstable', true)
                    ->setParameter('isDeprecated', true);
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $methods
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed|PhabricatorApplicationSearchResultView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderResultList(
        array $methods,
        PhabricatorSavedQuery $query,
        array $handles)
    {
        assert_instances_of($methods, ConduitAPIMethod::className());

        $out = array();

        $last = null;
        $list = null;
        foreach ($methods as $method) {
            $app = $method->getApplicationName();
            if ($app !== $last) {
                $last = $app;
                if ($list) {
                    $out[] = $list;
                }
                $list = (new PHUIObjectItemListView());
                $list->setHeader($app);

                $app_object = $method->getApplication();
                if ($app_object) {
                    $app_name = $app_object->getName();
                } else {
                    $app_name = $app;
                }
            }

            $method_name = $method->getAPIMethodName();

            $item = (new PHUIObjectItemView())
                ->setHeader($method_name)
                ->setHref($this->getApplicationURI('index/method', ['method' => $method_name]))
                ->addAttribute($method->getMethodSummary());

            switch ($method->getMethodStatus()) {
                case ConduitAPIMethod::METHOD_STATUS_STABLE:
                    break;
                case ConduitAPIMethod::METHOD_STATUS_UNSTABLE:
                    $item->addIcon('fa-warning', \Yii::t("app",'Unstable'));
                    $item->setStatusIcon('fa-warning yellow');
                    break;
                case ConduitAPIMethod::METHOD_STATUS_DEPRECATED:
                    $item->addIcon('fa-warning', \Yii::t("app",'Deprecated'));
                    $item->setStatusIcon('fa-warning red');
                    break;
                case ConduitAPIMethod::METHOD_STATUS_FROZEN:
                    $item->addIcon('fa-archive', \Yii::t("app",'Frozen'));
                    $item->setStatusIcon('fa-archive grey');
                    break;
            }

            $list->addItem($item);
        }

        if ($list) {
            $out[] = $list;
        }

        $result = new PhabricatorApplicationSearchResultView();
        $result->setContent($out);

        return $result;
    }

}

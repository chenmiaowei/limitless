<?php

namespace orangins\modules\search\query;

use orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery;;
use orangins\lib\helpers\JavelinHtml;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormCheckboxControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\form\control\AphrontFormTokenizerControl;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\modules\metamta\typeahead\PhabricatorMetaMTAMailableFunctionDatasource;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\typeahead\PhabricatorPeopleAnyOwnerDatasource;
use orangins\modules\people\typeahead\PhabricatorPeopleNoOwnerDatasource;
use orangins\modules\people\typeahead\PhabricatorPeopleOwnerDatasource;
use orangins\modules\people\typeahead\PhabricatorPeopleUserFunctionDatasource;
use orangins\modules\phid\helpers\PhabricatorPHID;
use orangins\modules\phid\PhabricatorPHIDType;
use orangins\modules\phid\query\PhabricatorObjectQuery;
use orangins\modules\search\application\PhabricatorSearchApplication;
use orangins\modules\search\constants\PhabricatorSearchRelationship;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\interfaces\PhabricatorFulltextInterface;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\typeahead\PhabricatorSearchDocumentTypeDatasource;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use orangins\modules\search\view\PhabricatorSearchResultView;
use PhutilClassMapQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorSearchApplicationSearchEngine
 * @package orangins\modules\search\query
 * @author 陈妙威
 */
final class PhabricatorSearchApplicationSearchEngine
    extends PhabricatorApplicationSearchEngine
{

    /**
     * @var PhabricatorFulltextResultSet
     */
    private $resultSet;

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return \Yii::t("app",'Fulltext Search Results');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorSearchApplication::className();
    }

    /**
     * @param AphrontRequest $request
     * @return PhabricatorSavedQuery
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException*@throws \Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSavedQueryFromRequest(AphrontRequest $request)
    {
        $saved = new PhabricatorSavedQuery();

        $saved->setParameter('query', $request->getStr('query'));
        $saved->setParameter(
            'statuses',
            $this->readListFromRequest($request, 'statuses'));
        $saved->setParameter(
            'types',
            $this->readListFromRequest($request, 'types'));

        $saved->setParameter(
            'authorPHIDs',
            $this->readUsersFromRequest($request, 'authorPHIDs'));

        $saved->setParameter(
            'ownerPHIDs',
            $this->readUsersFromRequest($request, 'ownerPHIDs'));

        $saved->setParameter(
            'subscriberPHIDs',
            $this->readSubscribersFromRequest($request, 'subscriberPHIDs'));

        $saved->setParameter(
            'projectPHIDs',
            $this->readPHIDsFromRequest($request, 'projectPHIDs'));

        return $saved;
    }

    /**
     * @param PhabricatorSavedQuery $saved
     * @return PhabricatorSearchDocumentQuery
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \orangins\modules\typeahead\exception\PhabricatorTypeaheadInvalidTokenException
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildQueryFromSavedQuery(PhabricatorSavedQuery $saved)
    {
        $query = new PhabricatorSearchDocumentQuery();

        // Convert the saved query into a resolved form (without typeahead
        // functions) which the fulltext search engines can execute.
        $config = clone $saved;
        $viewer = $this->requireViewer();

        $datasource = (new PhabricatorPeopleOwnerDatasource())
            ->setViewer($viewer);
        $owner_phids = $this->readOwnerPHIDs($config);
        $owner_phids = $datasource->evaluateTokens($owner_phids);
        foreach ($owner_phids as $key => $phid) {
            if ($phid == PhabricatorPeopleNoOwnerDatasource::FUNCTION_TOKEN) {
                $config->setParameter('withUnowned', true);
                unset($owner_phids[$key]);
            }
            if ($phid == PhabricatorPeopleAnyOwnerDatasource::FUNCTION_TOKEN) {
                $config->setParameter('withAnyOwner', true);
                unset($owner_phids[$key]);
            }
        }
        $config->setParameter('ownerPHIDs', $owner_phids);


        $datasource = (new PhabricatorPeopleUserFunctionDatasource())
            ->setViewer($viewer);
        $author_phids = $config->getParameter('authorPHIDs', array());
        $author_phids = $datasource->evaluateTokens($author_phids);
        $config->setParameter('authorPHIDs', $author_phids);


        $datasource = (new PhabricatorMetaMTAMailableFunctionDatasource())
            ->setViewer($viewer);
        $subscriber_phids = $config->getParameter('subscriberPHIDs', array());
        $subscriber_phids = $datasource->evaluateTokens($subscriber_phids);
        $config->setParameter('subscriberPHIDs', $subscriber_phids);


        $query->withSavedQuery($config);

        return $query;
    }

    /**
     * @param AphrontFormView $form
     * @param PhabricatorSavedQuery $saved
     * @throws \yii\base\Exception*@throws \Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSearchForm(
        AphrontFormView $form,
        PhabricatorSavedQuery $saved)
    {

        $options = array();
        $author_value = null;
        $owner_value = null;
        $subscribers_value = null;
        $project_value = null;

        $author_phids = $saved->getParameter('authorPHIDs', array());
        $owner_phids = $this->readOwnerPHIDs($saved);
        $subscriber_phids = $saved->getParameter('subscriberPHIDs', array());
        $project_phids = $saved->getParameter('projectPHIDs', array());

        $status_values = $saved->getParameter('statuses', array());
        $status_values = array_fuse($status_values);

        $statuses = array(
            PhabricatorSearchRelationship::RELATIONSHIP_OPEN => \Yii::t("app",'Open'),
            PhabricatorSearchRelationship::RELATIONSHIP_CLOSED => \Yii::t("app",'Closed'),
        );
        $status_control = (new AphrontFormCheckboxControl())
            ->setLabel(\Yii::t("app",'Document Status'));
        foreach ($statuses as $status => $name) {
            $status_control->addCheckbox(
                'statuses[]',
                $status,
                $name,
                isset($status_values[$status]));
        }

        $type_values = $saved->getParameter('types', array());
        $type_values = array_fuse($type_values);

        $types_control = (new AphrontFormTokenizerControl())
            ->setLabel(\Yii::t("app",'Document Types'))
            ->setName('types')
            ->setDatasource(new PhabricatorSearchDocumentTypeDatasource())
            ->setValue($type_values);

        $form
            ->appendChild(
                phutil_tag(
                    'input',
                    array(
                        'type' => 'hidden',
                        'name' => 'jump',
                        'value' => 'no',
                    )))
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app",'Query'))
                    ->setName('query')
                    ->setValue($saved->getParameter('query')))
            ->appendChild($status_control)
            ->appendControl($types_control)
            ->appendControl(
                (new AphrontFormTokenizerControl())
                    ->setName('authorPHIDs')
                    ->setLabel(\Yii::t("app",'Authors'))
                    ->setDatasource(new PhabricatorPeopleUserFunctionDatasource())
                    ->setValue($author_phids))
            ->appendControl(
                (new AphrontFormTokenizerControl())
                    ->setName('ownerPHIDs')
                    ->setLabel(\Yii::t("app",'Owners'))
                    ->setDatasource(new PhabricatorPeopleOwnerDatasource())
                    ->setValue($owner_phids))
            ->appendControl(
                (new AphrontFormTokenizerControl())
                    ->setName('subscriberPHIDs')
                    ->setLabel(\Yii::t("app",'Subscribers'))
                    ->setDatasource(new PhabricatorMetaMTAMailableFunctionDatasource())
                    ->setValue($subscriber_phids));
    }

    /**
     * @param $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge(['/search/index/' . $path], $params));
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        return array(
            'all' => \Yii::t("app",'All Documents'),
            'open' => \Yii::t("app",'Open Documents'),
        );
    }

    /**
     * @param $query_key
     * @return mixed
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSavedQueryFromBuiltin($query_key)
    {
        $query = $this->newSavedQuery();
        $query->setQueryKey($query_key);

        switch ($query_key) {
            case 'all':
                return $query;
            case 'open':
                return $query->setParameter('statuses', array('open'));
        }
        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param PhabricatorUser|null $viewer
     * @return array
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public static function getIndexableDocumentTypes(
        PhabricatorUser $viewer = null)
    {

        // TODO: This is inelegant and not very efficient, but gets us reasonable
        // results. It would be nice to do this more elegantly.

        $objects = (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorFulltextInterface::class)
            ->execute();

        $type_map = array();
        foreach ($objects as $object) {
            $phid_type = PhabricatorPHID::phid_get_type($object->generatePHID());
            $type_map[$phid_type] = $object;
        }

        if ($viewer) {
            $types = PhabricatorPHIDType::getAllInstalledTypes($viewer);
        } else {
            $types = PhabricatorPHIDType::getAllTypes();
        }

        $results = array();
        foreach ($types as $type) {
            $typeconst = $type->getTypeConstant();
            $object = ArrayHelper::getValue($type_map, $typeconst);
            if ($object) {
                $results[$typeconst] = $type->getTypeName();
            }
        }

        asort($results);

        return $results;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldUseOffsetPaging()
    {
        return true;
    }

    /**
     * @param array $results
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return PhabricatorApplicationSearchResultView|mixed
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderResultList(
        array $results,
        PhabricatorSavedQuery $query,
        array $handles)
    {

        $result_set = $this->resultSet;
        $fulltext_tokens = $result_set->getFulltextTokens();

        $viewer = $this->requireViewer();
        $list = new PHUIObjectItemListView();
        $list->setNoDataString(\Yii::t("app",'No results found.'));

        if ($results) {
            $objects = (new PhabricatorObjectQuery())
                ->setViewer($viewer)
                ->withPHIDs(mpull($results, 'getPHID'))
                ->execute();

            foreach ($results as $phid => $handle) {
                $view = (new PhabricatorSearchResultView())
                    ->setHandle($handle)
                    ->setTokens($fulltext_tokens)
                    ->setObject(ArrayHelper::getValue($objects, $phid))
                    ->render();
                $list->addItem($view);
            }
        }

        $fulltext_view = null;
        if ($fulltext_tokens) {
//            require_celerity_resource('phabricator-search-results-css');

            $fulltext_view = array();
            foreach ($fulltext_tokens as $token) {
                $fulltext_view[] = $token->newTag();
            }
            $fulltext_view = JavelinHtml::phutil_tag(
                'div',
                array(
                    'class' => 'px-3 phui-fulltext-tokens',
                ),
                array(
                    \Yii::t("app",'Searched For:'),
                    ' ',
                    $fulltext_view,
                ));
        }

        $result = new PhabricatorApplicationSearchResultView();
        $result->setContent($fulltext_view);
        $result->setObjectList($list);

        return $result;
    }

    /**
     * @param PhabricatorSavedQuery $saved
     * @return array|mixed

     * @author 陈妙威
     */
    private function readOwnerPHIDs(PhabricatorSavedQuery $saved)
    {
        $owner_phids = $saved->getParameter('ownerPHIDs', array());

        // This was an old checkbox from before typeahead functions.
        if ($saved->getParameter('withUnowned')) {
            $owner_phids[] = PhabricatorPeopleNoOwnerDatasource::FUNCTION_TOKEN;
        }

        return $owner_phids;
    }

    /**
     * @param PhabricatorPolicyAwareQuery|PhabricatorSearchDocumentQuery $query
     * @author 陈妙威
     * @throws \PhutilInvalidStateException
     */
    protected function didExecuteQuery(PhabricatorPolicyAwareQuery $query)
    {
        $this->resultSet = $query->getFulltextResultSet();
    }
}

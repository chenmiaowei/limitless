<?php

namespace orangins\modules\search\actions;

use AphrontDuplicateKeyQueryException;
use AphrontWriteGuard;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\layout\AphrontSideNavFilterView;
use orangins\modules\meta\query\PhabricatorApplicationQuery;
use orangins\modules\search\engine\PhabricatorDatasourceEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\query\PhabricatorSearchApplicationSearchEngine;
use PhutilURI;

/**
 * Class PhabricatorSearchController
 * @package orangins\modules\search\actions
 * @author 陈妙威
 */
final class PhabricatorSearchAction extends PhabricatorSearchBaseAction
{

    /**
     *
     */
    const SCOPE_CURRENT_APPLICATION = 'application';

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return mixed
     * @throws \PhutilInvalidStateException *@throws \Exception
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $query = $request->getStr('query');

        if ($request->getStr('jump') != 'no' && strlen($query)) {
            $jump_uri = (new PhabricatorDatasourceEngine())
                ->setViewer($viewer)
                ->newJumpURI($query);

            if ($jump_uri !== null) {
                return (new AphrontRedirectResponse())->setURI($jump_uri);
            }
        }

        $engine = new PhabricatorSearchApplicationSearchEngine();
        $engine->setViewer($viewer);

        // If we're coming from primary search, do some special handling to
        // interpret the scope selector and query.
        if ($request->getBool('search:primary')) {

            // If there's no query, just take the user to advanced search.
            if (!strlen($query)) {
                $advanced_uri = '/search/query/advanced/';
                return (new AphrontRedirectResponse())->setURI($advanced_uri);
            }

            // First, load or construct a template for the search by examining
            // the current search scope.
            $scope = $request->getStr('search:scope');
            $saved = null;

            if ($scope == self::SCOPE_CURRENT_APPLICATION) {
                $application = (new PhabricatorApplicationQuery())
                    ->setViewer($viewer)
                    ->withClasses(array($request->getStr('search:application')))
                    ->executeOne();
                if ($application) {
                    $types = $application->getApplicationSearchDocumentTypes();
                    if ($types) {
                        $saved = (new PhabricatorSavedQuery())
                            ->setEngineClassName($engine->getClassShortName())
                            ->setParameter('types', $types)
                            ->setParameter('statuses', array('open'));
                    }
                }
            }

            if (!$saved && !$engine->isBuiltinQuery($scope)) {
                $saved = PhabricatorSavedQuery::find()
                    ->setViewer($viewer)
                    ->withQueryKeys(array($scope))
                    ->executeOne();
            }

            if (!$saved) {
                if (!$engine->isBuiltinQuery($scope)) {
                    $scope = 'all';
                }
                $saved = $engine->buildSavedQueryFromBuiltin($scope);
            }

            // Add the user's query, then save this as a new saved query and send
            // the user to the results page.
            $saved->setParameter('query', $query);

            $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
            try {
                $saved->id = null;
                $saved->setIsNewRecord(true);
                $saved->save();
            } catch (AphrontDuplicateKeyQueryException $ex) {
                // Ignore, this is just a repeated search.
            }
            unset($unguarded);

            $query_key = $saved->getQueryKey();
            $results_uri = $engine->getQueryResultsPageURI($query_key) . '#R';
            return (new AphrontRedirectResponse())->setURI($results_uri);
        }

        $controller = (new PhabricatorApplicationSearchAction('search', $this->controller))
            ->setQueryKey($request->getURIData('queryKey'))
            ->setSearchEngine($engine)
            ->setNavigation($this->buildSideNavView());

        return $this->delegateToAction($controller);
    }

    /**
     * @return AphrontSideNavFilterView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildSideNavView()
    {
        $viewer = $this->getViewer();

        $nav = new AphrontSideNavFilterView();
        $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

        (new PhabricatorSearchApplicationSearchEngine())
            ->setViewer($viewer)
            ->addNavigationItems($nav->getMenu());

        $nav->selectFilter(null);

        return $nav;
    }

}

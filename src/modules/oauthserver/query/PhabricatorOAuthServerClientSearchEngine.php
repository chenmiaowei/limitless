<?php

namespace orangins\modules\oauthserver\query;

use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\oauthserver\application\PhabricatorOAuthServerApplication;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerClient;
use orangins\modules\people\searchfield\PhabricatorUsersSearchField;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorSavedQuery;
use orangins\modules\search\view\PhabricatorApplicationSearchResultView;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PhabricatorOAuthServerClientSearchEngine
 * @package orangins\modules\oauthserver\query
 * @author 陈妙威
 */
final class PhabricatorOAuthServerClientSearchEngine
    extends PhabricatorApplicationSearchEngine
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getResultTypeDescription()
    {
        return pht('OAuth Clients');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getApplicationClassName()
    {
        return PhabricatorOAuthServerApplication::className();
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
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery|PhabricatorOAuthServerClientQuery
     * @author 陈妙威
     */
    public function newQuery()
    {
        return PhabricatorOAuthServerClient::find();
    }

    /**
     * @param array $map
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery|PhabricatorOAuthServerClientQuery
     * @author 陈妙威
     */
    protected function buildQueryFromParameters(array $map)
    {
        $query = $this->newQuery();

        if ($map['creatorPHIDs']) {
            $query->withCreatorPHIDs($map['creatorPHIDs']);
        }

        return $query;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function buildCustomSearchFields()
    {
        return array(
            (new  PhabricatorUsersSearchField())
                ->setAliases(array('creators'))
                ->setKey('creatorPHIDs')
                ->setConduitKey('creators')
                ->setLabel(pht('Creators'))
                ->setDescription(
                    pht('Search for applications created by particular users.')),
        );
    }

    /**
     * @param null $path
     * @param array $params
     * @return string
     * @author 陈妙威
     */
    protected function getURI($path = null, $params = [])
    {
        return Url::to(ArrayHelper::merge([
            '/oauthserver/index/' . $path
        ], $params));
    }
    /**
     * @return array
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function getBuiltinQueryNames()
    {
        $names = array();

        if ($this->requireViewer()->isLoggedIn()) {
            $names['created'] = pht('Created');
        }

        $names['all'] = pht('All Applications');

        return $names;
    }

    /**
     * @param $query_key
     * @return mixed|PhabricatorSavedQuery
     * @throws \PhutilInvalidStateException
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
            case 'created':
                return $query->setParameter(
                    'creatorPHIDs',
                    array($this->requireViewer()->getPHID()));
        }

        return parent::buildSavedQueryFromBuiltin($query_key);
    }

    /**
     * @param array $clients
     * @param PhabricatorSavedQuery $query
     * @param array $handles
     * @return mixed|PhabricatorApplicationSearchResultView
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    protected function renderResultList(
        array $clients,
        PhabricatorSavedQuery $query,
        array $handles)
    {
        assert_instances_of($clients, PhabricatorOAuthServerClient::className());

        $viewer = $this->requireViewer();

        $list = (new  PHUIObjectItemListView())
            ->setUser($viewer);
        foreach ($clients as $client) {
            $item = (new  PHUIObjectItemView())
                ->setObjectName(pht('Application %d', $client->getID()))
                ->setHeader($client->getName())
                ->setHref($client->getViewURI())
                ->setObject($client);

            if ($client->getIsDisabled()) {
                $item->setDisabled(true);
            }

            $list->addItem($item);
        }

        $result = new PhabricatorApplicationSearchResultView();
        $result->setObjectList($list);
        $result->setNoDataString(pht('No clients found.'));

        return $result;
    }

}

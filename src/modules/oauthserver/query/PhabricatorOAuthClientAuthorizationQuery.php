<?php

namespace orangins\modules\oauthserver\query;

use AphrontAccessDeniedQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\oauthserver\application\PhabricatorOAuthServerApplication;
use orangins\modules\oauthserver\models\PhabricatorOAuthClientAuthorization;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerClient;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorOAuthClientAuthorizationQuery
 * @package orangins\modules\oauthserver\query
 * @author 陈妙威
 */
final class PhabricatorOAuthClientAuthorizationQuery
    extends PhabricatorCursorPagedPolicyAwareQuery
{

    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $userPHIDs;
    /**
     * @var
     */
    private $clientPHIDs;

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPHIDs(array $phids)
    {
        $this->phids = $phids;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withUserPHIDs(array $phids)
    {
        $this->userPHIDs = $phids;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withClientPHIDs(array $phids)
    {
        $this->clientPHIDs = $phids;
        return $this;
    }

    /**
     * @return null|PhabricatorOAuthClientAuthorization
     * @author 陈妙威
     */
    public function newResultObject()
    {
        return new PhabricatorOAuthClientAuthorization();
    }

    /**
     * @return array|mixed
     * @throws AphrontAccessDeniedQueryException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $page = $this->loadStandardPage();
        return $page;
    }

    /**
     * @param array $authorizations
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @author 陈妙威
     */
    protected function willFilterPage(array $authorizations)
    {
        $client_phids = mpull($authorizations, 'getClientPHID');

        $clients = PhabricatorOAuthServerClient::find()
            ->setViewer($this->getViewer())
            ->setParentQuery($this)
            ->withPHIDs($client_phids)
            ->execute();
        $clients = mpull($clients, null, 'getPHID');

        foreach ($authorizations as $key => $authorization) {
            $client = ArrayHelper::getValue($clients, $authorization->getClientPHID());

            if (!$client) {
                $this->didRejectResult($authorization);
                unset($authorizations[$key]);
                continue;
            }

            $authorization->attachClient($client);
        }
        return $authorizations;
    }

    /**
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
     * @throws PhabricatorInvalidQueryCursorException
     * @throws Exception
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
       parent::buildWhereClauseParts();

        if ($this->phids !== null) {
            $this->andWhere(['IN', 'phid', $this->phids]);
        }

        if ($this->userPHIDs !== null) {
            $this->andWhere(['IN', 'user_phid', $this->userPHIDs]);
        }

        if ($this->clientPHIDs !== null) {
            $this->andWhere(['IN', 'client_phid', $this->clientPHIDs]);
        }
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorOAuthServerApplication::className();
    }
}

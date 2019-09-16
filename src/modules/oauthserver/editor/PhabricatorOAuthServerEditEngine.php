<?php

namespace orangins\modules\oauthserver\editor;

use orangins\modules\oauthserver\application\PhabricatorOAuthServerApplication;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerClient;
use orangins\modules\oauthserver\models\PhabricatorOAuthServerTransaction;
use orangins\modules\oauthserver\capability\PhabricatorOAuthServerCreateClientsCapability;
use orangins\modules\oauthserver\query\PhabricatorOAuthServerClientQuery;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;

/**
 * Class PhabricatorOAuthServerEditEngine
 * @package orangins\modules\oauthserver\editor
 * @author 陈妙威
 */
final class PhabricatorOAuthServerEditEngine
    extends PhabricatorEditEngine
{

    /**
     *
     */
    const ENGINECONST = 'oauthserver.application';

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isEngineConfigurable()
    {
        return false;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEngineName()
    {
        return pht('OAuth Applications');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryHeader()
    {
        return pht('Edit OAuth Applications');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSummaryText()
    {
        return pht('This engine manages OAuth client applications.');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getEngineApplicationClass()
    {
        return PhabricatorOAuthServerApplication::className();
    }

    /**
     * @return mixed|\orangins\modules\transactions\editengine\PhabricatorEditEngineSubtypeInterface
     * @author 陈妙威
     */
    protected function newEditableObject()
    {
        return PhabricatorOAuthServerClient::initializeNewClient(
            $this->getViewer());
    }

    /**
     * @return \orangins\lib\infrastructure\query\policy\PhabricatorPolicyAwareQuery|PhabricatorOAuthServerClientQuery
     * @author 陈妙威
     */
    protected function newObjectQuery()
    {
        return PhabricatorOAuthServerClient::find();
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateTitleText($object)
    {
        return pht('Create OAuth Server');
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateButtonText($object)
    {
        return pht('Create OAuth Server');
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectEditTitleText($object)
    {
        return pht('Edit OAuth Server: %s', $object->getName());
    }

    /**
     * @param $object
     * @return string
     * @author 陈妙威
     */
    protected function getObjectEditShortText($object)
    {
        return pht('Edit OAuth Server');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectCreateShortText()
    {
        return pht('Create OAuth Server');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getObjectName()
    {
        return pht('OAuth Server');
    }

    /**
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    protected function getObjectViewURI($object)
    {
        return $object->getViewURI();
    }

    /**
     * @return mixed|string
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getCreateNewObjectPolicy()
    {
        return $this->getApplication()->getPolicy(PhabricatorOAuthServerCreateClientsCapability::CAPABILITY);
    }

    /**
     * @param $object
     * @return array|\orangins\modules\transactions\editfield\PhabricatorEditField[]
     * @author 陈妙威
     */
    protected function buildCustomEditFields($object)
    {
        return array(
            (new  PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(pht('Name'))
                ->setIsRequired(true)
                ->setTransactionType(PhabricatorOAuthServerTransaction::TYPE_NAME)
                ->setDescription(pht('The name of the OAuth application.'))
                ->setConduitDescription(pht('Rename the application.'))
                ->setConduitTypeDescription(pht('New application name.'))
                ->setValue($object->getName()),
            (new  PhabricatorTextEditField())
                ->setKey('redirectURI')
                ->setLabel(pht('Redirect URI'))
                ->setIsRequired(true)
                ->setTransactionType(
                    PhabricatorOAuthServerTransaction::TYPE_REDIRECT_URI)
                ->setDescription(
                    pht('The redirect URI for OAuth handshakes.'))
                ->setConduitDescription(
                    pht(
                        'Change where this application redirects users to during OAuth ' .
                        'handshakes.'))
                ->setConduitTypeDescription(
                    pht(
                        'New OAuth application redirect URI.'))
                ->setValue($object->getRedirectURI()),
        );
    }

}

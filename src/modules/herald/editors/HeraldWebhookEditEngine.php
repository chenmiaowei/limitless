<?php

namespace orangins\modules\herald\editors;


use Exception;
use orangins\modules\herald\application\PhabricatorHeraldApplication;
use orangins\modules\herald\capability\HeraldCreateWebhooksCapability;
use \orangins\modules\herald\models\HeraldWebhook;
use orangins\modules\herald\xaction\HeraldWebhookNameTransaction;
use orangins\modules\herald\xaction\HeraldWebhookStatusTransaction;
use orangins\modules\herald\xaction\HeraldWebhookURITransaction;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\modules\transactions\editfield\PhabricatorSelectEditField;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use ReflectionException;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Url;

/**
 * Class HeraldWebhookEditEngine
 */
final class HeraldWebhookEditEngine extends PhabricatorEditEngine
{
    /**
     *
     */
    const ENGINECONST = 'herald.herald_webhook';

    /**
     * @return string
     */
    public function getEngineName()
    {
        return Yii::t("app", 'Herald Webhook');
    }

    /**
     * @return bool
     */
    protected function supportsEditEngineConfiguration()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isEngineConfigurable()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getSummaryHeader()
    {
        return Yii::t("app", 'Configure Herald Webhook Forms');
    }

    /**
     * @return string
     */
    public function getSummaryText()
    {
        return Yii::t("app", 'Configure creation and editing forms in Herald Webhook.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEngineApplicationClass()
    {
        return PhabricatorHeraldApplication::className();
    }

    /**
     * @return object
     */
    protected function newEditableObject()
    {
        return new HeraldWebhook();
    }

    /**
     * @throws InvalidConfigException
     */
    protected function newObjectQuery()
    {
        $query = HeraldWebhook::find();
        return $query;
    }

    /**
     * @param $object
     * @return string
     */
    protected function getObjectCreateTitleText($object)
    {
        return Yii::t("app", 'Create New Herald Webhook');
    }


    /**
     * @return string
     */
    protected function getObjectCreateShortText()
    {
        return Yii::t("app", 'Create New Herald Webhook');
    }


    /**
     * @param HeraldWebhook $object
     * @return string
     */
    protected function getObjectEditTitleText($object)
    {
        return Yii::t("app", 'Edit Herald Webhook: {0}', [$object->name]);
    }

    /**
     * @param HeraldWebhook $object
     * @return string
     */
    protected function getObjectEditShortText($object)
    {
        return $object->name;
    }

    /**
     * @param $object
     * @return string
     */
    public function getEffectiveObjectViewURI($object)
    {
        return $this->getObjectViewURI($object);
    }


    /**
     * @return string
     */
    protected function getObjectName()
    {
        return Yii::t('app', 'Herald Webhook');
    }

    /**
     * @param HeraldWebhook $object
     * @return string
     */
    protected function getObjectViewURI($object)
    {
        return $object->getURI();
    }

    /**
     * @task uri
     * @throws Exception
     */
    protected function getEditorURI()
    {
        return $this->getApplication()->getApplicationURI('webhook/edit');
    }


    /**
     * @return mixed|string
     * @throws ReflectionException
     * @author 陈妙威
     */
    protected function getCreateNewObjectPolicy()
    {
        return $this->getApplication()->getPolicy(
            HeraldCreateWebhooksCapability::CAPABILITY);
    }

    /**
     * @param HeraldWebhook $object
     * @return array|PhabricatorEditField[]
     * @author 陈妙威
     */
    protected function buildCustomEditFields($object)
    {
        return [
            (new PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(pht('Name'))
                ->setDescription(pht('Name of the webhook.'))
                ->setTransactionType(HeraldWebhookNameTransaction::TRANSACTIONTYPE)
                ->setIsRequired(true)
                ->setValue($object->getName()),
            (new PhabricatorTextEditField())
                ->setKey('uri')
                ->setLabel(pht('URI'))
                ->setDescription(pht('URI for the webhook.'))
                ->setTransactionType(HeraldWebhookURITransaction::TRANSACTIONTYPE)
                ->setIsRequired(true)
                ->setValue($object->getWebhookURI()),
            (new PhabricatorSelectEditField())
                ->setKey('status')
                ->setLabel(pht('Status'))
                ->setDescription(pht('Status mode for the webhook.'))
                ->setTransactionType(HeraldWebhookStatusTransaction::TRANSACTIONTYPE)
                ->setOptions(HeraldWebhook::getStatusDisplayNameMap())
                ->setValue($object->getStatus()),

        ];
    }

    /**
     * @param $object
     * @return string
     */
    public function getObjectCreateCancelURI($object)
    {
        return Url::to(['/herald/webhook/query']);
    }
}


<?php

namespace orangins\modules\herald\query;

use AphrontAccessDeniedQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorEmptyQueryException;
use orangins\lib\infrastructure\query\exception\PhabricatorInvalidQueryCursorException;
use orangins\lib\infrastructure\query\policy\PhabricatorCursorPagedPolicyAwareQuery;
use orangins\modules\herald\application\PhabricatorHeraldApplication;
use orangins\modules\herald\models\HeraldWebhook;
use PhutilInvalidStateException;
use PhutilTypeExtraParametersException;
use PhutilTypeMissingParametersException;
use ReflectionException;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

/**
 * This is the ActiveQuery class for [[\orangins\modules\herald\models\HeraldWebhookRequest]].
 *
 * @see \orangins\modules\herald\models\HeraldWebhookRequest
 */
class HeraldWebhookRequestQuery extends PhabricatorCursorPagedPolicyAwareQuery
{
    /**
     * @var
     */
    private $lastRequestEpochMin;
    /**
     * @var
     */
    private $lastRequestEpochMax;

    /**
     * @param $epoch_min
     * @param $epoch_max
     * @return $this
     * @author 陈妙威
     */
    public function withLastRequestEpochBetween($epoch_min, $epoch_max) {
        $this->lastRequestEpochMin = $epoch_min;
        $this->lastRequestEpochMax = $epoch_max;
        return $this;
    }
    
    /**
     * @var array
     */
    private $id;

    /**
     * @param array $id
     * @return $this
     * @author 陈妙威
     */
    public function withId($id)
    {
        $this->id[] = $id;
        return $this;
    }

    /**
     * @param array $ids
     * @return $this
     * @author 陈妙威
     */
    public function withIds($ids)
    {
        $this->id = $ids;
        return $this;
    }

    /**
     * @var array
     */
    private $webhook_phid;

    /**
     * @param array $webhookPHID
     * @return $this
     * @author 陈妙威
     */
    public function withWebhookPHID($webhookPHID)
    {
        $this->webhook_phid[] = $webhookPHID;
        return $this;
    }

    /**
     * @param array $webhookPHIDs
     * @return $this
     * @author 陈妙威
     */
    public function withWebhookPHIDs($webhookPHIDs)
    {
        $this->webhook_phid = $webhookPHIDs;
        return $this;
    }

    /**
     * @var array
     */
    private $created_at;

    /**
     * @param array $created_at
     * @return $this
     * @author 陈妙威
     */
    public function withCreated_at($created_at)
    {
        $this->created_at[] = $created_at;
        return $this;
    }

    /**
     * @param array $created_ats
     * @return $this
     * @author 陈妙威
     */
    public function withCreated_ats($created_ats)
    {
        $this->created_at = $created_ats;
        return $this;
    }

    /**
     * @var array
     */
    private $phid;

    /**
     * @param array $phid
     * @return $this
     * @author 陈妙威
     */
    public function withPHID($phid)
    {
        $this->phid[] = $phid;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withPhids($phids)
    {
        $this->phid = $phids;
        return $this;
    }

    /**
     * @var array
     */
    private $last_request_result;

    /**
     * @param array $last_request_result
     * @return $this
     * @author 陈妙威
     */
    public function withLastRequestResult($last_request_result)
    {
        $this->last_request_result[] = $last_request_result;
        return $this;
    }

    /**
     * @param array $last_request_results
     * @return $this
     * @author 陈妙威
     */
    public function withLastRequestResults($last_request_results)
    {
        $this->last_request_result = $last_request_results;
        return $this;
    }


    /**
     * @return ActiveRecord[]
     * @throws AphrontAccessDeniedQueryException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function loadPage()
    {
        return $this->loadStandardPage();
    }


    /**
     * @throws PhutilInvalidStateException
     * @throws PhutilTypeExtraParametersException
     * @throws PhutilTypeMissingParametersException
     * @throws ReflectionException
     * @throws PhabricatorEmptyQueryException
     * @throws PhabricatorInvalidQueryCursorException
     * @author 陈妙威
     */
    protected function buildWhereClauseParts()
    {
        parent::buildWhereClauseParts();

        if ($this->id !== null) {
            $this->andWhere(['IN', 'id', $this->id]);
        }
        if ($this->webhook_phid !== null) {
            $this->andWhere(['IN', 'webhook_phid', $this->webhook_phid]);
        }
        if ($this->created_at !== null) {
            $this->andWhere(['IN', 'created_at', $this->created_at]);
        }
        if ($this->phid !== null) {
            $this->andWhere(['IN', 'phid', $this->phid]);
        }
        if ($this->last_request_result !== null) {
            $this->andWhere(['IN', 'last_request_result', $this->last_request_result]);
        }
        if ($this->lastRequestEpochMin !== null) {
            $this->andWhere(['>=', 'last_request_epoch',  $this->lastRequestEpochMin]);
        }
        if ($this->lastRequestEpochMax !== null) {
            $this->andWhere(['<=', 'last_request_epoch',  $this->lastRequestEpochMax]);
        }
    }

    /**
     * @param array $requests
     * @return array
     * @throws PhutilInvalidStateException
     * @throws ReflectionException
     * @throws InvalidConfigException
     * @author 陈妙威
     */
    protected function willFilterPage(array $requests)
    {
        $hook_phids = mpull($requests, 'getWebhookPHID');

        $hooks = HeraldWebhook::find()
            ->setViewer($this->getViewer())
            ->setParentQuery($this)
            ->withPHIDs($hook_phids)
            ->execute();
        $hooks = mpull($hooks, null, 'getPHID');

        foreach ($requests as $key => $request) {
            $hook_phid = $request->getWebhookPHID();
            $hook = idx($hooks, $hook_phid);

            if (!$hook) {
                unset($requests[$key]);
                $this->didRejectResult($request);
                continue;
            }

            $request->attachWebhook($hook);
        }

        return $requests;
    }

    /**
     * If this query belongs to an application, return the application class name
     * here. This will prevent the query from returning results if the viewer can
     * not access the application.
     *
     * If this query does not belong to an application, return `null`.
     *
     * @return string|null Application class name.
     */
    public function getQueryApplicationClass()
    {
        return PhabricatorHeraldApplication::className();
    }
}

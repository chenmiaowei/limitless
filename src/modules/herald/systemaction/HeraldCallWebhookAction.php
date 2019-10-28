<?php

namespace orangins\modules\herald\systemaction;

use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\herald\engine\HeraldEffect;
use orangins\modules\herald\models\HeraldActionRecord;
use orangins\modules\herald\typeahead\HeraldWebhookDatasource;

/**
 * Class HeraldCallWebhookAction
 * @package orangins\modules\herald\systemaction
 * @author 陈妙威
 */
final class HeraldCallWebhookAction extends HeraldAction
{

    /**
     *
     */
    const ACTIONCONST = 'webhook';
    /**
     *
     */
    const DO_WEBHOOK = 'do.call-webhook';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getHeraldActionName()
    {
        return pht('Call webhooks');
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getActionGroupKey()
    {
        return HeraldUtilityActionGroup::ACTIONGROUPKEY;
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        if (!$this->getAdapter()->supportsWebhooks()) {
            return false;
        }

        return true;
    }

    /**
     * @param $rule_type
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsRuleType($rule_type)
    {
        return ($rule_type !== HeraldRuleTypeConfig::RULE_TYPE_PERSONAL);
    }

    /**
     * @param $object
     * @param HeraldEffect $effect
     * @return mixed|void
     * @throws \Exception
     * @author 陈妙威
     */
    public function applyEffect($object, HeraldEffect $effect)
    {
        $adapter = $this->getAdapter();
        $rule = $effect->getRule();
        $target = $effect->getTarget();

        foreach ($target as $webhook_phid) {
            $adapter->queueWebhook($webhook_phid, $rule->getPHID());
        }

        $this->logEffect(self::DO_WEBHOOK, $target);
    }

    /**
     * @return string|void
     * @author 陈妙威
     */
    public function getHeraldActionStandardType()
    {
        return self::STANDARD_PHID_LIST;
    }

    /**
     * @return array|void
     * @author 陈妙威
     */
    protected function getActionEffectMap()
    {
        return array(
            self::DO_WEBHOOK => array(
                'icon' => 'fa-cloud-upload',
                'color' => 'green',
                'name' => pht('Called Webhooks'),
            ),
        );
    }

    /**
     * @param $value
     * @return mixed|string
     * @author 陈妙威
     */
    public function renderActionDescription($value)
    {
        return pht('Call webhooks: %s.', $this->renderHandleList($value));
    }

    /**
     * @param $type
     * @param $data
     * @return string|null
     * @author 陈妙威
     */
    protected function renderActionEffectDescription($type, $data)
    {
        return pht('Called webhooks: %s.', $this->renderHandleList($data));
    }

    /**
     * @return HeraldWebhookDatasource
     * @author 陈妙威
     */
    protected function getDatasource()
    {
        return new HeraldWebhookDatasource();
    }

    /**
     * @param HeraldActionRecord $record
     * @return array
     * @author 陈妙威
     */
    public function getPHIDsAffectedByAction(HeraldActionRecord $record)
    {
        return $record->getTarget();
    }

}

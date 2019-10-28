<?php

namespace orangins\modules\herald\systemaction;

use orangins\lib\markup\PhabricatorMarkupEngine;
use orangins\modules\herald\config\HeraldRuleTypeConfig;
use orangins\modules\herald\engine\HeraldEffect;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;

/**
 * Class HeraldCommentAction
 * @package orangins\modules\herald\systemaction
 * @author 陈妙威
 */
final class HeraldCommentAction extends HeraldAction
{

    /**
     *
     */
    const ACTIONCONST = 'comment';
    /**
     *
     */
    const DO_COMMENT = 'do.comment';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getHeraldActionName()
    {
        return pht('Add comment');
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
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        if (!($object instanceof PhabricatorApplicationTransactionInterface)) {
            return false;
        }

        $xaction = $object->getApplicationTransactionTemplate();

        $comment = $xaction->getApplicationTransactionCommentObject();
        if (!$comment) {
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
        return ($rule_type != HeraldRuleTypeConfig::RULE_TYPE_PERSONAL);
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
        $comment_text = $effect->getTarget();

        $xaction = $adapter->newTransaction()
            ->setTransactionType(PhabricatorTransactions::TYPE_COMMENT);

        $comment = $xaction->getApplicationTransactionCommentObject()
            ->setContent($comment_text);

        $xaction->attachComment($comment);

        $adapter->queueTransaction($xaction);

        $this->logEffect(self::DO_COMMENT, $comment_text);
    }

    /**
     * @return string|void
     * @author 陈妙威
     */
    public function getHeraldActionStandardType()
    {
        return self::STANDARD_REMARKUP;
    }

    /**
     * @return array|void
     * @author 陈妙威
     */
    protected function getActionEffectMap()
    {
        return array(
            self::DO_COMMENT => array(
                'icon' => 'fa-comment',
                'color' => 'blue',
                'name' => pht('Added Comment'),
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
        $summary = PhabricatorMarkupEngine::summarize($value);
        return pht('Add comment: %s', $summary);
    }

    /**
     * @param $type
     * @param $data
     * @return string|null
     * @author 陈妙威
     */
    protected function renderActionEffectDescription($type, $data)
    {
        $summary = PhabricatorMarkupEngine::summarize($data);
        return pht('Added a comment: %s', $summary);
    }

}

<?php

namespace orangins\modules\herald\editors;


use \orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\xaction\heraldrule\HeraldRuleNameTransactionType;
use orangins\modules\herald\xaction\heraldrule\HeraldRuleAuthorPHIDTransactionType;
use orangins\modules\herald\xaction\heraldrule\HeraldRuleContentTypeTransactionType;
use orangins\modules\herald\xaction\heraldrule\HeraldRuleMustMatchAllTransactionType;
use orangins\modules\herald\xaction\heraldrule\HeraldRuleRepetitionPolicyTransactionType;
use orangins\modules\herald\xaction\heraldrule\HeraldRuleRuleTypeTransactionType;
use orangins\modules\herald\xaction\heraldrule\HeraldRuleTriggerObjectPHIDTransactionType;
use orangins\modules\policy\constants\PhabricatorPolicies;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorTextEditField;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * Class HeraldRuleEditEngine
 */
final class HeraldRuleEditEngine extends PhabricatorEditEngine
{
    /**
     *
     */
    const ENGINECONST = 'herald.herald_rule';

    /**
     * @return string
     */
    public function getEngineName()
    {
        return Yii::t("app", 'Herald Rule');
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
    protected function getCreateNewObjectPolicy()
    {
        // TODO: For now, this EditEngine can only edit objects, since there is
        // a lot of complexity in dealing with tag data during tag creation.
        return PhabricatorPolicies::POLICY_USER;
    }

    /**
     * @return string
     */
    public function getSummaryHeader()
    {
        return Yii::t("app", 'Configure Herald Rule Forms');
    }

    /**
     * @return string
     */
    public function getSummaryText()
    {
        return Yii::t("app", 'Configure creation and editing forms in Herald Rule.');
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEngineApplicationClass()
    {
        return \orangins\modules\herald\application\PhabricatorHeraldApplication::className();
    }

    /**
     * @return object
     */
    protected function newEditableObject()
    {
        return new HeraldRule();
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function newObjectQuery()
    {
        $query = HeraldRule::find();
        return $query;
    }

    /**
     * @param $object
     * @return string
     */
    protected function getObjectCreateTitleText($object)
    {
        return Yii::t("app", 'Create New Herald Rule');
    }


    /**
     * @return string
     */
    protected function getObjectCreateShortText()
    {
        return Yii::t("app", 'Create New Herald Rule');
    }



    /**
     * @param HeraldRule $object
     * @return string
     */
    protected function getObjectEditTitleText($object)
    {
        return Yii::t("app", 'Edit Herald Rule: {0}', [$object->name]);
    }

    /**
     * @param HeraldRule $object
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
        return Yii::t('app', 'Herald Rule');
    }

    /**
     * @param HeraldRule $object
     * @return string
     */
    protected function getObjectViewURI($object)
    {
        return $object->getURI();
    }

    /**
     * @param $object
     * @return array
     */
    protected function buildCustomEditFields($object)
    {
        return array(
            (new PhabricatorTextEditField())
                ->setKey('name')
                ->setLabel(Yii::t("app", 'name'))
                ->setPlaceholder(Yii::t("app", 'Please placeholder a name'))
                ->setTransactionType(HeraldRuleNameTransactionType::TRANSACTIONTYPE)
                ->setDescription(Yii::t("app", 'The name of the Herald Rule.'))
                ->setConduitDescription(Yii::t("app", 'Set the name of Herald Rule.'))
                ->setConduitTypeDescription(Yii::t("app", 'New Herald Rule name.'))
                ->setValue(ArrayHelper::getValue($object, 'name')),
            (new PhabricatorTextEditField())
                ->setKey('author_phid')
                ->setLabel(Yii::t("app", 'author_phid'))
                ->setPlaceholder(Yii::t("app", 'Please placeholder a author_phid'))
                ->setTransactionType(HeraldRuleAuthorPHIDTransactionType::TRANSACTIONTYPE)
                ->setDescription(Yii::t("app", 'The author_phid of the Herald Rule.'))
                ->setConduitDescription(Yii::t("app", 'Set the author_phid of Herald Rule.'))
                ->setConduitTypeDescription(Yii::t("app", 'New Herald Rule author_phid.'))
                ->setValue(ArrayHelper::getValue($object, 'author_phid')),
            (new PhabricatorTextEditField())
                ->setKey('content_type')
                ->setLabel(Yii::t("app", 'content_type'))
                ->setPlaceholder(Yii::t("app", 'Please placeholder a content_type'))
                ->setTransactionType(HeraldRuleContentTypeTransactionType::TRANSACTIONTYPE)
                ->setDescription(Yii::t("app", 'The content_type of the Herald Rule.'))
                ->setConduitDescription(Yii::t("app", 'Set the content_type of Herald Rule.'))
                ->setConduitTypeDescription(Yii::t("app", 'New Herald Rule content_type.'))
                ->setValue(ArrayHelper::getValue($object, 'content_type')),
            (new PhabricatorTextEditField())
                ->setKey('must_match_all')
                ->setLabel(Yii::t("app", 'must_match_all'))
                ->setPlaceholder(Yii::t("app", 'Please placeholder a must_match_all'))
                ->setTransactionType(HeraldRuleMustMatchAllTransactionType::TRANSACTIONTYPE)
                ->setDescription(Yii::t("app", 'The must_match_all of the Herald Rule.'))
                ->setConduitDescription(Yii::t("app", 'Set the must_match_all of Herald Rule.'))
                ->setConduitTypeDescription(Yii::t("app", 'New Herald Rule must_match_all.'))
                ->setValue(ArrayHelper::getValue($object, 'must_match_all')),
            (new PhabricatorTextEditField())
                ->setKey('repetition_policy')
                ->setLabel(Yii::t("app", 'repetition_policy'))
                ->setPlaceholder(Yii::t("app", 'Please placeholder a repetition_policy'))
                ->setTransactionType(HeraldRuleRepetitionPolicyTransactionType::TRANSACTIONTYPE)
                ->setDescription(Yii::t("app", 'The repetition_policy of the Herald Rule.'))
                ->setConduitDescription(Yii::t("app", 'Set the repetition_policy of Herald Rule.'))
                ->setConduitTypeDescription(Yii::t("app", 'New Herald Rule repetition_policy.'))
                ->setValue(ArrayHelper::getValue($object, 'repetition_policy')),
            (new PhabricatorTextEditField())
                ->setKey('rule_type')
                ->setLabel(Yii::t("app", 'rule_type'))
                ->setPlaceholder(Yii::t("app", 'Please placeholder a rule_type'))
                ->setTransactionType(HeraldRuleRuleTypeTransactionType::TRANSACTIONTYPE)
                ->setDescription(Yii::t("app", 'The rule_type of the Herald Rule.'))
                ->setConduitDescription(Yii::t("app", 'Set the rule_type of Herald Rule.'))
                ->setConduitTypeDescription(Yii::t("app", 'New Herald Rule rule_type.'))
                ->setValue(ArrayHelper::getValue($object, 'rule_type')),
            (new PhabricatorTextEditField())
                ->setKey('trigger_object_phid')
                ->setLabel(Yii::t("app", 'trigger_object_phid'))
                ->setPlaceholder(Yii::t("app", 'Please placeholder a trigger_object_phid'))
                ->setTransactionType(HeraldRuleTriggerObjectPHIDTransactionType::TRANSACTIONTYPE)
                ->setDescription(Yii::t("app", 'The trigger_object_phid of the Herald Rule.'))
                ->setConduitDescription(Yii::t("app", 'Set the trigger_object_phid of Herald Rule.'))
                ->setConduitTypeDescription(Yii::t("app", 'New Herald Rule trigger_object_phid.'))
                ->setValue(ArrayHelper::getValue($object, 'trigger_object_phid')),
        );
    }

    /**
     * @param $object
     * @return string
     */
    public function getObjectCreateCancelURI($object)
    {
        return \yii\helpers\Url::to(['/herald/index/query']);
    }
}


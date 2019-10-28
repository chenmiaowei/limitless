<?php

namespace orangins\modules\herald\xaction;

use orangins\modules\herald\editors\HeraldRuleSerializer;
use orangins\modules\herald\models\HeraldRule;
use orangins\modules\herald\xaction\heraldrule\HeraldRuleTransactionType;
use orangins\modules\transactions\view\PhabricatorApplicationTransactionTextDiffDetailView;
use PhutilJSON;

/**
 * Class HeraldRuleEditTransaction
 * @package orangins\modules\herald\xaction
 * @author 陈妙威
 */
final class HeraldRuleEditTransaction
    extends HeraldRuleTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'herald:edit';

    /**
     * @param $object
     * @return array
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return (new HeraldRuleSerializer())
            ->serializeRule($object);
    }

    /**
     * @param HeraldRule $object
     * @param $value
     * @throws \Exception
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $new_state = (new HeraldRuleSerializer())
            ->deserializeRuleComponents($value);

        $object->setMustMatchAll((int)$new_state['match_all']);
        $object->attachConditions($new_state['conditions']);
        $object->attachActions($new_state['actions']);

        $new_repetition = $new_state['repetition_policy'];
        $object->setRepetitionPolicyStringConstant($new_repetition);
    }

    /**
     * @param HeraldRule $object
     * @param $value
     * @throws \PhutilInvalidStateException
     * @throws \yii\db\Exception
     * @author 陈妙威
     */
    public function applyExternalEffects($object, $value)
    {
        $object->saveConditions($object->getConditions());
        $object->saveActions($object->getActions());
    }

    /**
     * @return string|null
     * @author 陈妙威
     */
    public function getTitle()
    {
        return pht(
            '%s edited this rule.',
            $this->renderAuthor());
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function hasChangeDetailView()
    {
        return true;
    }

    /**
     * @return |null
     * @author 陈妙威
     */
    public function newChangeDetailView()
    {
        $viewer = $this->getViewer();

        $old = $this->getOldValue();
        $new = $this->getNewValue();

        $json = new PhutilJSON();
        $old_json = $json->encodeFormatted($old);
        $new_json = $json->encodeFormatted($new);

        return (new PhabricatorApplicationTransactionTextDiffDetailView())
            ->setViewer($viewer)
            ->setOldText($old_json)
            ->setNewText($new_json);
    }

}

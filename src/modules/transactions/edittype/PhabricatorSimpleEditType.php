<?php

namespace orangins\modules\transactions\edittype;

use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorSimpleEditType
 * @package orangins\modules\transactions\edittype
 * @author 陈妙威
 */
final class PhabricatorSimpleEditType extends PhabricatorEditType
{

    /**
     * @param PhabricatorApplicationTransaction $template
     * @param array $spec
     * @return array
     * @author 陈妙威
     */
    public function generateTransactions(PhabricatorApplicationTransaction $template, array $spec)
    {

        $edit = $this->newTransaction($template)
            ->setNewValue(ArrayHelper::getValue($spec, 'value'));

        return array($edit);
    }

}

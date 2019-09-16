<?php

namespace orangins\modules\transactions\edittype;

use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorDatasourceEditType
 * @package orangins\modules\transactions\edittype
 * @author 陈妙威
 */
final class PhabricatorDatasourceEditType
    extends PhabricatorPHIDListEditType
{

    /**
     * @param PhabricatorApplicationTransaction $template
     * @param array $spec
     * @return array
     * @author 陈妙威
     */
    public function generateTransactions(
        PhabricatorApplicationTransaction $template,
        array $spec)
    {

        $value = ArrayHelper::getValue($spec, 'value');

        $xaction = $this->newTransaction($template)
            ->setNewValue($value);

        return array($xaction);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getValueDescription()
    {
        return '?';
    }

}

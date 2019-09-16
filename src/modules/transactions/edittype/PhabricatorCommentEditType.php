<?php

namespace orangins\modules\transactions\edittype;

use orangins\modules\conduit\parametertype\ConduitStringParameterType;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorCommentEditType
 * @package orangins\modules\transactions\edittype
 * @author 陈妙威
 */
final class PhabricatorCommentEditType extends PhabricatorEditType
{

    /**
     * @return ConduitStringParameterType|null
     * @author 陈妙威
     */
    protected function newConduitParameterType()
    {
        return new ConduitStringParameterType();
    }

    /**
     * @return BulkRemarkupParameterType|null
     * @author 陈妙威
     */
    protected function newBulkParameterType()
    {
        return new BulkRemarkupParameterType();
    }

    /**
     * @param PhabricatorApplicationTransaction $template
     * @param array $spec
     * @return array|mixed
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    public function generateTransactions(
        PhabricatorApplicationTransaction $template,
        array $spec)
    {

        $comment = $template->getApplicationTransactionCommentObject()
            ->setContent(ArrayHelper::getValue($spec, 'value'));

        $xaction = $this->newTransaction($template)
            ->attachComment($comment);

        return array($xaction);
    }

}

<?php

namespace orangins\modules\rbac\xaction;

use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use PhutilNumber;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorFileNameTransaction
 * @package orangins\modules\file\xaction
 * @author 陈妙威
 */
final class PhabricatorRBACRoleDescTransaction extends PhabricatorRBACRoleTransactionType
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'role:description';

    /**
     * @param ActiveRecord $object
     * @return mixed
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return $object->getAttribute("description");
    }

    /**
     * @param ActiveRecord $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setAttribute("description", $value);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return \Yii::t("app",
            '{0} updated the description for this file from "{1}" to "{2}".',
            [
                $this->renderAuthor(),
                $this->renderOldValue(),
                $this->renderNewValue()
            ]);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        return \Yii::t("app",
            '{0} updated the description of {1} from "{2}" to "{3}".',
            [
                $this->renderAuthor(),
                $this->renderObject(),
                $this->renderOldValue(),
                $this->renderNewValue()
            ]);
    }

    /**
     * @param ActiveRecordPHID $object
     * @param array $xactions
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \PhutilJSONParserException
     * @throws \Exception
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $errors = array();

        if ($this->isEmptyTextTransaction(ArrayHelper::getValue($object, 'description'), $xactions)) {
            $errors[] = $this->newRequiredError(\Yii::t("app", '请填写描述.'));
        }

        $max_length = $object->getColumnMaximumByteLength('description');
        foreach ($xactions as $xaction) {
            $new_value = $xaction->getNewValue();
            $new_length = strlen($new_value);
            if ($new_length > $max_length) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",
                        'File descriptions must not be longer than {0} character(s).', [new PhutilNumber($max_length)]));
            }
        }

        return $errors;
    }
}

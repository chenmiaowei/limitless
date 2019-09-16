<?php

namespace orangins\modules\rbac\xaction;

use orangins\lib\db\ActiveRecord;
use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\rbac\models\RbacRole;
use PhutilNumber;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorFileNameTransaction
 * @package orangins\modules\file\xaction
 * @author 陈妙威
 */
final class PhabricatorRBACRoleNameTransaction extends PhabricatorRBACRoleTransactionType
{
    /**
     *
     */
    const TRANSACTIONTYPE = 'role:name';

    /**
     * @param ActiveRecord $object
     * @return mixed
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return $object->getAttribute("name");
    }

    /**
     * @param ActiveRecord $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setAttribute("name", $value);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        return \Yii::t("app",
            '{0} updated the name  from "{1}" to "{2}".',
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
            '{0} updated the name of {1} from "{2}" to "{3}".',
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

        if ($this->isEmptyTextTransaction(ArrayHelper::getValue($object, 'name'), $xactions)) {
            $errors[] = $this->newRequiredError(\Yii::t("app", 'Roles must have a name.'));
        }

        $max_length = $object->getColumnMaximumByteLength('name');
        foreach ($xactions as $xaction) {
            $new_value = $xaction->getNewValue();
            $new_length = strlen($new_value);
            if ($new_length > $max_length) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",
                        'Roles names must not be longer than {0} character(s).', [new PhutilNumber($max_length)]));
            }

            if(!preg_match("/[a-z]{1}[a-z|1-9|.]{5,31}/", $new_value)) {
                $errors[] = $this->newInvalidError(\Yii::t("app",'名称必须只能为6-32位小写字母、数字，并且以字母带头。'));
            }

            if($object->getIsNewRecord()) {
                $exist = RbacRole::find()
                    ->setViewer(PhabricatorUser::getOmnipotentUser())
                    ->withName($new_value)
                    ->executeOne();

                if($exist) {
                    $errors[] = $this->newInvalidError(\Yii::t("app",'名称已存在，请修改您输入的名称。'));
                }
            }
        }
        return $errors;
    }
}

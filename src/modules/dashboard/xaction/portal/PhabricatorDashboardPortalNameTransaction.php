<?php

namespace orangins\modules\dashboard\xaction\portal;

/**
 * Class PhabricatorDashboardPortalNameTransaction
 * @package orangins\modules\dashboard\xaction\portal
 * @author 陈妙威
 */
final class PhabricatorDashboardPortalNameTransaction
    extends PhabricatorDashboardPortalTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'name';

    /**
     * @param $object
     * @author 陈妙威
     * @return
     */
    public function generateOldValue($object)
    {
        return $object->getName();
    }

    /**
     * @param $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setName($value);
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTitle()
    {
        $old = $this->getOldValue();
        $new = $this->getNewValue();

        return \Yii::t("app",
            '%s renamed this portal from %s to %s.',
            $this->renderAuthor(),
            $this->renderOldValue(),
            $this->renderNewValue());
    }

    /**
     * @param $object
     * @param array $xactions
     * @return array
     * @throws \PhutilJSONParserException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function validateTransactions($object, array $xactions)
    {
        $errors = array();

        $max_length = $object->getColumnMaximumByteLength('name');
        foreach ($xactions as $xaction) {
            $new = $xaction->getNewValue();
            if (!strlen($new)) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",'Portals must have a title.'),
                    $xaction);
                continue;
            }

            if (strlen($new) > $max_length) {
                $errors[] = $this->newInvalidError(
                    \Yii::t("app",
                        'Portal names must not be longer than %s characters.',
                        $max_length));
                continue;
            }
        }

        if (!$errors) {
            if ($this->isEmptyTextTransaction($object->getName(), $xactions)) {
                $errors[] = $this->newRequiredError(
                    \Yii::t("app",'Portals must have a title.'));
            }
        }

        return $errors;
    }

    /**
     * @param $xaction
     * @return null|string
     * @author 陈妙威
     */
    public function getTransactionTypeForConduit($xaction)
    {
        return 'name';
    }

    /**
     * @param $xaction
     * @param $data
     * @return array
     * @author 陈妙威
     */
    public function getFieldValuesForConduit($xaction, $data)
    {
        return array(
            'old' => $xaction->getOldValue(),
            'new' => $xaction->getNewValue(),
        );
    }

}

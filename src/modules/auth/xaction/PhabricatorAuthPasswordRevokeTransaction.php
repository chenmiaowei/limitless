<?php

namespace orangins\modules\auth\xaction;

/**
 * Class PhabricatorAuthPasswordRevokeTransaction
 * @package orangins\modules\auth\xaction
 * @author 陈妙威
 */
final class PhabricatorAuthPasswordRevokeTransaction
    extends PhabricatorAuthPasswordTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'password.revoke';

    /**
     * @param $object
     * @return bool|void
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return (bool)$object->getIsRevoked();
    }

    /**
     * @param $object
     * @param $value
     * @return bool|mixed
     * @author 陈妙威
     */
    public function generateNewValue($object, $value)
    {
        return (bool)$value;
    }

    /**
     * @param $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setIsRevoked((int)$value);
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTitle()
    {
        if ($this->getNewValue()) {
            return \Yii::t("app",
                '%s revoked this password.',
                $this->renderAuthor());
        } else {
            return \Yii::t("app",
                '%s removed this password from the revocation list.',
                $this->renderAuthor());
        }
    }

}

<?php

namespace orangins\modules\auth\xaction;

use PhutilInvalidStateException;

/**
 * Class PhabricatorAuthPasswordUpgradeTransaction
 * @package orangins\modules\auth\xaction
 * @author 陈妙威
 */
final class PhabricatorAuthPasswordUpgradeTransaction
    extends PhabricatorAuthPasswordTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'password.upgrade';

    /**
     * @param $object
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        $old_hasher = $this->getEditor()->getOldHasher();

        if (!$old_hasher) {
            throw new PhutilInvalidStateException('setOldHasher');
        }

        return $old_hasher->getHashName();
    }

    /**
     * @param $object
     * @param $value
     * @return mixed
     * @author 陈妙威
     */
    public function generateNewValue($object, $value)
    {
        return $value;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getTitle()
    {
        return \Yii::t("app",
            '%s upgraded the hash algorithm for this password from "%s" to "%s".',
            $this->renderAuthor(),
            $this->renderOldValue(),
            $this->renderNewValue());
    }

}

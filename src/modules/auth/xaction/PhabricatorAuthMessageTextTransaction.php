<?php

namespace orangins\modules\auth\xaction;

use orangins\modules\transactions\view\PhabricatorApplicationTransactionTextDiffDetailView;

/**
 * Class PhabricatorAuthMessageTextTransaction
 * @package orangins\modules\auth\xaction
 * @author 陈妙威
 */
final class PhabricatorAuthMessageTextTransaction
    extends PhabricatorAuthMessageTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'text';

    /**
     * @param $object
     * @author 陈妙威
     * @return
     */
    public function generateOldValue($object)
    {
        return $object->getMessageText();
    }

    /**
     * @param $object
     * @param $value
     * @author 陈妙威
     */
    public function applyInternalEffects($object, $value)
    {
        $object->setMessageText($value);
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getTitle()
    {
        return \Yii::t("app",
            '%s updated the message text.',
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
     * @return mixed|string
     * @author 陈妙威
     */
    public function getMailDiffSectionHeader()
    {
        return \Yii::t("app",'CHANGES TO MESSAGE');
    }

    /**
     * @return null
     * @author 陈妙威
     */
    public function newChangeDetailView()
    {
        $viewer = $this->getViewer();

        return id(new PhabricatorApplicationTransactionTextDiffDetailView())
            ->setViewer($viewer)
            ->setOldText($this->getOldValue())
            ->setNewText($this->getNewValue());
    }

}

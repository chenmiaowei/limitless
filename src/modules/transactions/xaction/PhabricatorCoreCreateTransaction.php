<?php

namespace orangins\modules\transactions\xaction;


/**
 * Class PhabricatorCoreCreateTransaction
 * @package orangins\modules\transactions\xaction
 * @author 陈妙威
 */
final class PhabricatorCoreCreateTransaction
    extends PhabricatorCoreTransactionType
{

    /**
     *
     */
    const TRANSACTIONTYPE = 'core:create';

    /**
     * @param $object
     * @return null
     * @author 陈妙威
     */
    public function generateOldValue($object)
    {
        return null;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitle()
    {
        $editor = $this->getObject()->getApplicationTransactionEditor();

        $author = $this->renderAuthor();
        $object = $this->renderObject();

        return $editor->getCreateObjectTitle($author, $object);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getTitleForFeed()
    {
        $editor = $this->getObject()->getApplicationTransactionEditor();

        $author = $this->renderAuthor();
        $object = $this->renderObject();

        return $editor->getCreateObjectTitleForFeed($author, $object);
    }

}

<?php

namespace orangins\modules\transactions\query;

use orangins\modules\transactions\models\PhabricatorApplicationTransactionComment;

/**
 * Class PhabricatorApplicationTransactionTemplatedCommentQuery
 * @package orangins\modules\transactions\query
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionTemplatedCommentQuery
    extends PhabricatorApplicationTransactionCommentQuery
{

    /**
     * @var
     */
    private $template;

    /**
     * @param PhabricatorApplicationTransactionComment $template
     * @return $this
     * @author 陈妙威
     */
    public function setTemplate(
        PhabricatorApplicationTransactionComment $template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getTemplate()
    {
        return $this->template;
    }

}

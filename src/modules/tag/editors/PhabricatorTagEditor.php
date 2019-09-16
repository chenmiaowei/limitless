<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/8
 * Time: 4:48 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\tag\editors;

use orangins\modules\tag\application\PhabricatorTagsApplication;
use orangins\modules\tag\xaction\PhabricatorTagNameTransaction;
use orangins\modules\transactions\constants\PhabricatorTransactions;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use Yii;

/**
 * Class FileEditor
 * @package orangins\modules\file\editors
 * @author 陈妙威
 */
class PhabricatorTagEditor extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTransactionTypes() {
        $types = parent::getTransactionTypes();

        $types[] = PhabricatorTransactions::TYPE_EDGE;
        $types[] = PhabricatorTransactions::TYPE_COMMENT;
        $types[] = PhabricatorTransactions::TYPE_VIEW_POLICY;
        $types[] = PhabricatorTransactions::TYPE_EDIT_POLICY;
        $types[] = PhabricatorTagNameTransaction::TRANSACTIONTYPE;
        return $types;
    }

    /**
     * Get the class name for the application this editor is a part of.
     *
     * Uninstalling the application will disable the editor.
     *
     * @return string Editor's application class name.
     */
    public function getEditorApplicationClass()
    {
        return PhabricatorTagsApplication::class;
    }

    /**
     * Get a description of the objects this editor edits, like "Differential
     * Revisions".
     *
     * @return string Human readable description of edited objects.
     */
    public function getEditorObjectsDescription()
    {
        return Yii::t('app', 'Tags');
    }
}

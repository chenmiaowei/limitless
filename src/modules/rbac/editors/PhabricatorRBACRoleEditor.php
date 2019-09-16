<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/8
 * Time: 4:48 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\rbac\editors;

use orangins\modules\rbac\application\PhabricatorRBACApplication;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;
use Yii;

/**
 * Class FileEditor
 * @package orangins\modules\file\editors
 * @author 陈妙威
 */
class PhabricatorRBACRoleEditor extends PhabricatorApplicationTransactionEditor
{
    /**
     * Get the class name for the application this editor is a part of.
     *
     * Uninstalling the application will disable the editor.
     *
     * @return string Editor's application class name.
     */
    public function getEditorApplicationClass()
    {
        return PhabricatorRBACApplication::class;
    }

    /**
     * Get a description of the objects this editor edits, like "Differential
     * Revisions".
     *
     * @return string Human readable description of edited objects.
     */
    public function getEditorObjectsDescription()
    {
        return Yii::t('app', 'Role');
    }
}
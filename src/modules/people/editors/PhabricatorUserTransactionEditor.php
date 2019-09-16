<?php

namespace orangins\modules\people\editors;

use orangins\modules\people\application\PhabricatorPeopleApplication;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;

/**
 * Class PhabricatorUserTransactionEditor
 * @package orangins\modules\people\editors
 * @author 陈妙威
 */
final class PhabricatorUserTransactionEditor extends PhabricatorApplicationTransactionEditor
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorApplicationClass()
    {
        return PhabricatorPeopleApplication::class;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorObjectsDescription()
    {
        return \Yii::t("app", 'Users');
    }
}

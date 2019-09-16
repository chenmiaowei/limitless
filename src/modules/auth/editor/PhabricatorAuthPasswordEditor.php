<?php

namespace orangins\modules\auth\editor;

use orangins\lib\utils\password\PhabricatorPasswordHasher;
use orangins\modules\auth\application\PhabricatorAuthApplication;
use orangins\modules\transactions\editors\PhabricatorApplicationTransactionEditor;

/**
 * Class PhabricatorAuthPasswordEditor
 * @package orangins\modules\auth\editor
 * @author 陈妙威
 */
final class PhabricatorAuthPasswordEditor
    extends PhabricatorApplicationTransactionEditor
{

    /**
     * @var
     */
    private $oldHasher;

    /**
     * @param PhabricatorPasswordHasher $old_hasher
     * @return $this
     * @author 陈妙威
     */
    public function setOldHasher(PhabricatorPasswordHasher $old_hasher)
    {
        $this->oldHasher = $old_hasher;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOldHasher()
    {
        return $this->oldHasher;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorApplicationClass()
    {
        return PhabricatorAuthApplication::className();
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getEditorObjectsDescription()
    {
        return \Yii::t("app",'Passwords');
    }

    /**
     * @param $author
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getCreateObjectTitle($author, $object)
    {
        return \Yii::t("app",'{0} created this password.', [$author]);
    }

    /**
     * @param $author
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public function getCreateObjectTitleForFeed($author, $object)
    {
        return \Yii::t("app",'{0} created {1}.', [$author, $object]);
    }
}

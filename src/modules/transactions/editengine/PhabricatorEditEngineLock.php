<?php

namespace orangins\modules\transactions\editengine;

use orangins\lib\OranginsObject;
use orangins\lib\view\AphrontDialogView;
use orangins\modules\people\models\PhabricatorUser;

/**
 * Class PhabricatorEditEngineLock
 * @package orangins\modules\transactions\editengine
 * @author 陈妙威
 */
abstract class PhabricatorEditEngineLock extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $object;

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    final public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getObject()
    {
        return $this->object;
    }

    /**
     * @param AphrontDialogView $dialog
     * @return AphrontDialogView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function willPromptUserForLockOverrideWithDialog(AphrontDialogView $dialog)
    {

        return $dialog
            ->setTitle(\Yii::t("app",'Edit Locked Object'))
            ->appendParagraph(\Yii::t("app",'This object is locked. Edit it anyway?'))
            ->addSubmitButton(\Yii::t("app",'Override Lock'));
    }

    /**
     * @param AphrontDialogView $dialog
     * @return AphrontDialogView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function willBlockUserInteractionWithDialog(
        AphrontDialogView $dialog)
    {

        return $dialog
            ->setTitle(\Yii::t("app",'Object Locked'))
            ->appendParagraph(
                \Yii::t("app",'You can not interact with this object because it is locked.'));
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getLockedObjectDisplayText()
    {
        return \Yii::t("app",'This object has been locked.');
    }

    /**
     * @param PhabricatorUser $viewer
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    public static function newForObject(
        PhabricatorUser $viewer,
        $object)
    {

        if ($object instanceof PhabricatorEditEngineLockableInterface) {
            $lock = $object->newEditEngineLock();
        } else {
            $lock = new PhabricatorEditEngineDefaultLock();
        }

        return $lock
            ->setViewer($viewer)
            ->setObject($object);
    }


}

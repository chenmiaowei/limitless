<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/7
 * Time: 11:52 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\people\editors;

use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserTransaction;
use orangins\modules\transactions\editors\OranginsTransactionEditorBackup;
use orangins\modules\transactions\models\PhabricatorApplicationTransaction;
use orangins\modules\widgets\ActiveField;
use orangins\modules\widgets\ActiveFormWidgetView;
use orangins\modules\widgets\forms\AvatarPickerInputWidget;
use yii\web\Request;

/**
 * Class UserPictureEditor
 * @package orangins\modules\people\editors
 * @author 陈妙威
 */
class UserPictureEditor extends OranginsTransactionEditorBackup
{
    /**
     * @return string
     * @author 陈妙威
     */
    public function transactionClassName()
    {
        return PhabricatorUserTransaction::class;
    }


    /**
     * @return OranginsTransactionEditorBackup[]
     * @author 陈妙威
     */
    public function columns()
    {
        return [
            $this
                ->newAttribute("profile_image_phid")
                ->setLabel(\Yii::t('app', 'Upload Picture'))
                ->setRequired(true)
                ->setTransactionType(PhabricatorUserTransaction::TYPE_IMAGE),
        ];
    }

    /**
     * @param bool $insert
     * @return bool
     * @author 陈妙威
     */
    public function beforeSave($insert)
    {
        if ($this->getOldAttributes() && isset($this->getOldAttributes()["profile_image_phid"]) && !isset($this->getDirtyAttributes()['profile_image_phid'])) {
            $this->setAttribute("profile_image_phid", $this->getOldAttributes()["profile_image_phid"]);
        }
        return parent::beforeSave($insert);
    }

    /**
     * @param ActiveFormWidgetView $form
     * @param Request $request
     * @param PhabricatorUser $viewer
     * @return ActiveField[]
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    function buildEditFields(ActiveFormWidgetView $form, Request $request, PhabricatorUser $viewer)
    {
        return [
            $form->field($this, 'profile_image_phid', ['labelOptions' => ['label' => \Yii::t('app', 'Current Picture')]])->widget(AvatarPickerInputWidget::class, [
                'display' => true,
            ]),
            $form->field($this, 'profile_image_phid', ['labelOptions' => ['label' => \Yii::t('app', 'Use Picture')]])->widget(AvatarPickerInputWidget::class, [
                'data' => PhabricatorFile::loadDefaultAvatar($viewer)
            ])
        ];
    }
}
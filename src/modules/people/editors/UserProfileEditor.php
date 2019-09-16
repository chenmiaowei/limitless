<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/6
 * Time: 3:20 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\people\editors;

use orangins\lib\exception\ActiveRecordException;
use orangins\lib\view\OranginsPanelView;
use orangins\modules\people\models\PhabricatorUserTransaction;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\transactions\editors\OranginsTransactionEditorBackup;
use orangins\modules\widgets\ActiveField;
use orangins\modules\widgets\ActiveFormWidgetView;

/**
 * Class UserProfileEditor
 * @property string real_name
 * @property string username
 * @property string $title
 * @property string icon
 * @property string blurb
 * @package orangins\modules\people\editors
 * @author 陈妙威
 */
class UserProfileEditor extends OranginsTransactionEditorBackup
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
     * @return array
     * @author 陈妙威
     */
    public function columns()
    {
        return [
            $this
                ->newAttribute("username")
                ->setHidden(true),
            $this
                ->newAttribute("real_name")
                ->setRequired(true)
                ->setTransactionType(PhabricatorUserTransaction::TYPE_REAL_NAME),
            $this
                ->newAttribute("title")
                ->setTransactionType(PhabricatorUserTransaction::TYPE_TITLE),
            $this
                ->newAttribute("icon")
                ->setTransactionType(PhabricatorUserTransaction::TYPE_ICON)
                ->setControl(\orangins\modules\widgets\forms\BuiltinIconInputWidget::class),
            $this
                ->newAttribute("blurb")
                ->setTransactionType(PhabricatorUserTransaction::TYPE_BLURB)
                ->setControl(\orangins\modules\widgets\markdown\MarkdownEditor::class),
        ];
    }

    /**
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function init()
    {
        parent::init();
        /** @var PhabricatorUser $oranginsUser */
        $oranginsUser = $this->targetObject;
        $this->initAttributes([
            'icon' => $oranginsUser->getUserProfile()->icon,
            'title' => $oranginsUser->getUserProfile()->title,
            'blurb' => $oranginsUser->getUserProfile()->blurb,
        ]);
    }


    /**
     * @param $insert
     * @param $values
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function afterSave($insert, $values)
    {
        /** @var PhabricatorUser $user */
        $user = $this->targetObject;
        $adminProfiles = $user->getUserProfile();
        $adminProfiles->title = $this->title;
        $adminProfiles->icon = $this->icon;
        $adminProfiles->blurb = $this->blurb;
        if (!$adminProfiles->save()) {
            throw new ActiveRecordException('AdminProfile save error.', $adminProfiles->getErrorSummary(true));
        }
    }
}
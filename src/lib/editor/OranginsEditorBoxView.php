<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/1/4
 * Time: 9:29 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\editor;

use orangins\lib\view\OranginsBoxView;
use yii\base\InvalidConfigException;

/**
 * Class OranginsEditorView
 * @package orangins\lib\editor
 * @author 陈妙威
 */
class OranginsEditorBoxView extends OranginsBoxView
{
    /**
     * @var OranginsEditorBackup
     */
    public $editor;

    /**
     * @return OranginsEditorBackup
     */
    public function getEditor()
    {
        return $this->editor;
    }

    /**
     * @param OranginsEditorBackup $editor
     * @return static
     */
    public function setEditor($editor)
    {
        $this->editor = $editor;
        return $this;
    }

    /**
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function init()
    {
        parent::init();
        if (empty($this->getEditor())) {
            throw new InvalidConfigException(\Yii::t("app", 'The "editor" property of "{0}" must be instance of "{1}".', [
                get_called_class(),
                OranginsEditorBackup::class,
            ]));
        }
        $this->setContent($this->getEditor());
    }
}
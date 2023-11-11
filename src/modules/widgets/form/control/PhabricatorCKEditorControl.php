<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/4
 * Time: 10:52 AM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\widgets\form\control;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\form\control\AphrontFormTextAreaControl;
use orangins\modules\widgets\ckeditor\JavelinCKEditorBehaviorAsset;
use orangins\modules\widgets\ueditor\JavelinUEditorBehaviorAsset;
use PhutilSafeHTML;
use yii\helpers\Url;

/**
 * Class PhabricatorUEditorControl
 * @package orangins\modules\widgets\form\control
 * @author 陈妙威
 */
final class PhabricatorCKEditorControl extends AphrontFormTextAreaControl
{
    private $id;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return PhabricatorUEditorControl
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return array|mixed|string
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        $celerity_generate_unique_node_id = $this->id ? $this->id : JavelinHtml::generateUniqueNodeId();
        $phutil_tag = JavelinHtml::phutil_tag("textarea", [
            "id" => $celerity_generate_unique_node_id,
            "name" => $this->getName(),
        ], [
            new PhutilSafeHTML($this->getValue())
        ]);

        JavelinHtml::initBehavior(new JavelinCKEditorBehaviorAsset(), [
            "id" => $celerity_generate_unique_node_id,
            "name" => $this->getName(),
            "options" => [
                "filebrowserBrowseUrl" => Url::to(['/widgets/index/ckeditor']),
                "filebrowserUploadUrl" => Url::to(['/widgets/index/ckeditor']),
                "language" => "zh-cn",
                "toolbar" => [
                    ["name" => 'document', "groups" => ['mode', 'document', 'doctools'], "items" => ['Source', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates']],
                    ["name" => 'clipboard', "groups" => ['clipboard', 'undo'], "items" => ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']],
                    ["name" => 'editing', "groups" => ['find', 'selection', 'spellchecker'], "items" => ['Find', 'Replace', '-', 'SelectAll', '-', 'Scayt']],
                    ["name" => 'forms', "items" => ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField']],
                    '/',
                    ["name" => 'basicstyles', "groups" => ['basicstyles', 'cleanup'], "items" => ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat']],
                    ["name" => 'paragraph', "groups" => ['list', 'indent', 'blocks', 'align', 'bidi'], "items" => ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language']],
                    ["name" => 'links', "items" => ['Link', 'Unlink', 'Anchor']],
                    ["name" => 'insert', "items" => ['Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe']],
                    '/',
                    ["name" => 'styles', "items" => ['Styles', 'Format', 'Font', 'FontSize']],
                    ["name" => 'colors', "items" => ['TextColor', 'BGColor']],
                    ["name" => 'tools', "items" => ['Maximize', 'ShowBlocks']],
                    ["name" => 'others', "items" => ['-']],
                    ["name" => 'about', "items" => ['About']]
                ],
//                "toolbarGroups" => [
//                    ["name" => 'styles'],
//                    ["name" => 'editing', "groups" => ['find', 'selection']],
//                    ["name" => 'forms'],
//                    ["name" => 'basicstyles', "groups" => ['basicstyles']],
//                    ["name" => 'paragraph', "groups" => ['list', 'blocks', 'align']],
//                    ["name" => 'links'],
//                    ["name" => 'insert'],
//                    ["name" => 'colors'],
//                    ["name" => 'tools'],
//                    ["name" => 'others'],
//                    ["name" => 'document', "groups" => ['mode', 'document', 'doctools']]
//                ]
        ],]);
        return JavelinHtml::phutil_tag("div", [
            "class" => "mt-2",
        ], [
            $phutil_tag
        ]);
    }
}
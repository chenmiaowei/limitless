<?php

namespace orangins\lib\view\form\control;

use orangins\lib\helpers\JavelinHtml;
use orangins\modules\file\engine\PhabricatorFileStorageEngine;
use orangins\modules\file\models\PhabricatorFile;
use orangins\modules\widgets\javelin\JavelinPHUIFileUploadAjaxAsset;
use yii\helpers\Url;

/**
 * Class PHUIFormFileControl
 * @package orangins\lib\view\form\control
 * @author 陈妙威
 */
final class PHUIFormFileAjaxControl
    extends AphrontFormControl
{

    /**
     * @var
     */
    private $allowMultiple;

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    protected function getCustomControlClass()
    {
        return 'phui-form-file-upload';
    }

    /**
     * @param $allow_multiple
     * @return $this
     * @author 陈妙威
     */
    public function setAllowMultiple($allow_multiple)
    {
        $this->allowMultiple = $allow_multiple;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAllowMultiple()
    {
        return $this->allowMultiple;
    }

    /**
     * @return array|mixed
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    protected function renderInput()
    {
        $id = $this->getID() ? $this->getID() : JavelinHtml::generateUniqueNodeId();
        $uploadButtonText = "Upload File";

        JavelinHtml::initBehavior(
            new JavelinPHUIFileUploadAjaxAsset(),
            array(
                'id' => $id,
                'uploadButtonText' => $uploadButtonText,
                'inputName' => $this->getName(),
                'uploadURI' => Url::to(['/file/index/dropupload']),
                'chunkThreshold' => PhabricatorFileStorageEngine::getChunkThreshold(),
            ));


        // If the control has a value, add a hidden input which submits it as a
        // default. This allows the file control to mean "don't change anything",
        // instead of "remove the file", if the user submits the form without
        // touching it.

        // This also allows the input to "hold" the value of an uploaded file if
        // there is another error in the form: when you submit the form but are
        // stopped because of an unrelated error, submitting it again will keep
        // the value around (if you don't upload a new file) instead of requiring
        // you to pick the file again.

        // TODO: This works alright, but is a bit of a hack, and the UI should
        // provide the user better feedback about whether the state of the control
        // is "keep the value the same" or "remove the value", and about whether
        // or not the control is "holding" a value from a previous submission.

        $default_value = $this->getValue();
        $default_input = JavelinHtml::phutil_tag(
            'input',
            array(
                'type' => 'hidden',
                'sigil' => 'file-upload-ajax-input-hidden',
                'name' => $this->getName(),
                'value' => $default_value,
            ));
        $content = [
            JavelinHtml::phutil_tag('i', ['class' => 'icon-file-plus icon-2x text-success p-3 mt-3']),
            JavelinHtml::phutil_tag("h5", ['class' => 'card-title mb-3'], \Yii::t("app", $uploadButtonText)),
        ];

        if($default_value && ($phabricatorFile = PhabricatorFile::findModelByPHID($default_value))) {
            $content = [
                JavelinHtml::phutil_tag("img", ['class' => 'w-100', 'src' => $phabricatorFile->getViewURI()])
            ];
        }

//        return array(
//            JavelinHtml::phutil_tag(
//                'input',
//                array(
//                    'type' => 'file',
//                    'multiple' => $this->getAllowMultiple() ? 'multiple' : null,
//                    'name' => $this->getName() . '_raw',
//                    'id' => $file_id,
//                    'disabled' => $this->getDisabled() ? 'disabled' : null,
//                )),
//            $default_input,
//        );


        return JavelinHtml::phutil_tag("div", [
            "class" => "card justify-content-center text-center position-relative col-md-3",
            'id' => $id,
        ], [
            JavelinHtml::phutil_tag("div", [
                'sigil' => 'file-upload-ajax-content'
            ], $content),
            JavelinHtml::phutil_tag("input", [
                'type' => 'file',
                'sigil' => 'file-upload-ajax-input',
                'style' => 'height: 100%; width: 100%; opacity: 0; ',
                'class' => 'position-absolute',
                'name' => $this->getName() . "_raw",
            ]),
            $default_input
        ]);
    }
}

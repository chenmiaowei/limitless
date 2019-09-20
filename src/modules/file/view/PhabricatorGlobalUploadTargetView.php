<?php

namespace orangins\modules\file\view;

use orangins\lib\helpers\JavelinHtml;
use orangins\modules\file\engine\PhabricatorFileStorageEngine;
use orangins\modules\file\models\PhabricatorFile;
use orangins\lib\view\AphrontView;
use orangins\modules\widgets\javelin\JavelinGlobalDragAndDropAsset;
use yii\helpers\Url;

/**
 * IMPORTANT: If you use this, make sure to implement
 *
 *   public function isGlobalDragAndDropUploadEnabled() {
 *     return true;
 *   }
 *
 * on the controller(s) that render this class...! This is necessary
 * to make sure Quicksand works properly with the javascript in this
 * UI.
 */
final class PhabricatorGlobalUploadTargetView extends AphrontView
{

    /**
     * @var
     */
    private $showIfSupportedID;
    /**
     * @var
     */
    private $hintText;
    /**
     * @var
     */
    private $viewPolicy;
    /**
     * @var
     */
    private $submitURI;

    /**
     * @param $show_if_supported_id
     * @return $this
     * @author 陈妙威
     */
    public function setShowIfSupportedID($show_if_supported_id)
    {
        $this->showIfSupportedID = $show_if_supported_id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getShowIfSupportedID()
    {
        return $this->showIfSupportedID;
    }

    /**
     * @param $hint_text
     * @return $this
     * @author 陈妙威
     */
    public function setHintText($hint_text)
    {
        $this->hintText = $hint_text;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHintText()
    {
        return $this->hintText;
    }

    /**
     * @param $view_policy
     * @return $this
     * @author 陈妙威
     */
    public function setViewPolicy($view_policy)
    {
        $this->viewPolicy = $view_policy;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewPolicy()
    {
        return $this->viewPolicy;
    }

    /**
     * @param $submit_uri
     * @return $this
     * @author 陈妙威
     */
    public function setSubmitURI($submit_uri)
    {
        $this->submitURI = $submit_uri;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSubmitURI()
    {
        return $this->submitURI;
    }


    /**
     * @return null|string
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $viewer = $this->getViewer();
        if (!$viewer->isLoggedIn()) {
            return null;
        }

        $instructions_id = 'phabricator-global-drag-and-drop-upload-instructions';

        $hint_text = $this->getHintText();
        if (!strlen($hint_text)) {
            $hint_text = "\xE2\x87\xAA " . \Yii::t("app", 'Drop Files to Upload');
        }

        // Use the configured default view policy. Drag and drop uploads use
        // a more restrictive view policy if we don't specify a policy explicitly,
        // as the more restrictive policy is correct for most drop targets (like
        // Pholio uploads and Remarkup text areas).

        $view_policy = $this->getViewPolicy();
        if ($view_policy === null) {
            $view_policy = PhabricatorFile::initializeNewFile()->getViewPolicy();
        }

        $submit_uri = $this->getSubmitURI();
        $done_uri = Url::to(['/file/index/query', [
            "queryKey" => "all"
        ]]);

        JavelinHtml::initBehavior(new JavelinGlobalDragAndDropAsset(), array(
            'ifSupported' => $this->showIfSupportedID,
            'instructions' => $instructions_id,
            'uploadURI' => Url::to(['/file/index/dropupload']),
            'submitURI' => $submit_uri,
            'browseURI' => $done_uri,
            'viewPolicy' => $view_policy,
            'chunkThreshold' => PhabricatorFileStorageEngine::getChunkThreshold(),
        ));

        return JavelinHtml::tag('div', $hint_text, array(
            'id' => $instructions_id,
            'class' => 'phabricator-global-upload-instructions',
            'style' => 'display: none;',
        ));
    }
}

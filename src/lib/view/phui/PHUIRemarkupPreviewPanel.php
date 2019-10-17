<?php

namespace orangins\lib\view\phui;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontTagView;
use orangins\modules\widgets\javelin\JavelinRemarkupPreviewAsset;
use PhutilInvalidStateException;

/**
 * Render a simple preview panel for a bound Remarkup text control.
 */
final class PHUIRemarkupPreviewPanel extends AphrontTagView
{

    /**
     * @var
     */
    private $header;
    /**
     * @var
     */
    private $loadingText;
    /**
     * @var
     */
    private $controlID;
    /**
     * @var
     */
    private $previewURI;
    /**
     * @var
     */
    private $previewType;

    /**
     *
     */
    const DOCUMENT = 'document';

    /**
     * @return bool
     * @author 陈妙威
     */
    protected function canAppendChild()
    {
        return false;
    }

    /**
     * @param $preview_uri
     * @return $this
     * @author 陈妙威
     */
    public function setPreviewURI($preview_uri)
    {
        $this->previewURI = $preview_uri;
        return $this;
    }

    /**
     * @param $control_id
     * @return $this
     * @author 陈妙威
     */
    public function setControlID($control_id)
    {
        $this->controlID = $control_id;
        return $this;
    }

    /**
     * @param $header
     * @return $this
     * @author 陈妙威
     */
    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @param $loading_text
     * @return $this
     * @author 陈妙威
     */
    public function setLoadingText($loading_text)
    {
        $this->loadingText = $loading_text;
        return $this;
    }

    /**
     * @param $type
     * @return $this
     * @author 陈妙威
     */
    public function setPreviewType($type)
    {
        $this->previewType = $type;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getTagName()
    {
        return 'div';
    }

    /**
     * @return array
     * @author 陈妙威
     */
    protected function getTagAttributes()
    {
        $classes = array();
        $classes[] = 'phui-remarkup-preview';

        return array(
            'class' => $classes,
        );
    }

    /**
     * @return array|PHUIObjectBoxView
     * @throws \Exception
     * @author 陈妙威
     */
    protected function getTagContent()
    {
        if ($this->previewURI === null) {
            throw new PhutilInvalidStateException('setPreviewURI');
        }
        if ($this->controlID === null) {
            throw new PhutilInvalidStateException('setControlID');
        }

        $preview_id = JavelinHtml::generateUniqueNodeId();

//        require_celerity_resource('phui-remarkup-preview-css');
        JavelinHtml::initBehavior(
            new JavelinRemarkupPreviewAsset(),
            array(
                'previewID' => $preview_id,
                'controlID' => $this->controlID,
                'uri' => $this->previewURI,
            ));

        $loading = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => 'phui-preview-loading-text',
            ),
            nonempty($this->loadingText, \Yii::t("app", 'Loading preview...')));

        $preview = JavelinHtml::phutil_tag(
            'div',
            array(
                'id' => $preview_id,
                'class' => 'phabricator-remarkup phui-preview-body',
            ),
            $loading);

        if (!$this->previewType) {
            $header = null;
            if ($this->header) {
                $header = JavelinHtml::phutil_tag(
                    'div',
                    array(
                        'class' => 'phui-preview-header',
                    ),
                    $this->header);
            }
            $content = array($header, $preview);

        } else if ($this->previewType == self::DOCUMENT) {
            $header = (new PHUIHeaderView())
                ->setHeader(\Yii::t("app", '{0} (Preview)', [
                    $this->header
                ]));

            $content = (new PHUIDocumentView())
                ->setHeader($header)
                ->appendChild($preview);
        }

        return (new PHUIObjectBoxView())
            ->appendChild($content)
            ->setCollapsed(true);
    }

}

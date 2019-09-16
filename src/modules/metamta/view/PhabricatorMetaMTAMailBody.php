<?php

namespace orangins\modules\metamta\view;

use orangins\lib\env\PhabricatorEnv;
use orangins\lib\markup\PhabricatorMarkupEngine;
use orangins\lib\OranginsObject;
use orangins\modules\metamta\models\PhabricatorMetaMTAAttachment;
use PhutilRemarkupEngine;
use Exception;

/**
 * Render the body of an application email by building it up section-by-section.
 *
 * @task compose  Composition
 * @task render   Rendering
 */
final class PhabricatorMetaMTAMailBody extends OranginsObject
{

    /**
     * @var array
     */
    private $sections = array();
    /**
     * @var array
     */
    private $htmlSections = array();
    /**
     * @var array
     */
    private $attachments = array();

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $contextObject;

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param $viewer
     * @return $this
     * @author 陈妙威
     */
    public function setViewer($viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @param $context_object
     * @return $this
     * @author 陈妙威
     */
    public function setContextObject($context_object)
    {
        $this->contextObject = $context_object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getContextObject()
    {
        return $this->contextObject;
    }


    /* -(  Composition  )-------------------------------------------------------- */


    /**
     * Add a raw block of text to the email. This will be rendered as-is.
     *
     * @param string Block of text.
     * @return self
     * @task compose
     * @throws \Exception
     */
    public function addRawSection($text)
    {
        if (strlen($text)) {
            $text = rtrim($text);
            $this->sections[] = $text;
            $this->htmlSections[] = phutil_escape_html_newlines(
                phutil_tag('div', array(), $text));
        }
        return $this;
    }

    /**
     * @param $header
     * @param $text
     * @return $this
     * @throws \Exception
     * @author 陈妙威
     */
    public function addRemarkupSection($header, $text)
    {
        try {
            $engine = $this->newMarkupEngine()
                ->setMode(PhutilRemarkupEngine::MODE_TEXT);
            $styled_text = $engine->markupText($text);
            $this->addPlaintextSection($header, $styled_text);
        } catch (Exception $ex) {
            \Yii::error($ex);
            $this->addTextSection($header, $text);
        }

        try {
            $mail_engine = $this->newMarkupEngine()
                ->setMode(PhutilRemarkupEngine::MODE_HTML_MAIL);

            $html = $mail_engine->markupText($text);
            $this->addHTMLSection($header, $html);
        } catch (Exception $ex) {
            \Yii::error($ex);
            $this->addHTMLSection($header, $text);
        }

        return $this;
    }

    /**
     * @param $text
     * @return $this
     * @author 陈妙威
     */
    public function addRawPlaintextSection($text)
    {
        if (strlen($text)) {
            $text = rtrim($text);
            $this->sections[] = $text;
        }
        return $this;
    }

    /**
     * @param $html
     * @return $this
     * @author 陈妙威
     */
    public function addRawHTMLSection($html)
    {
        $this->htmlSections[] = phutil_safe_html($html);
        return $this;
    }


    /**
     * Add a block of text with a section header. This is rendered like this:
     *
     *    HEADER
     *      Text is indented.
     *
     * @param string Header text.
     * @param string Section text.
     * @task compose
     * @return PhabricatorMetaMTAMailBody
     * @throws \Exception
     */
    public function addTextSection($header, $section)
    {
        if ($section instanceof PhabricatorMetaMTAMailSection) {
            $plaintext = $section->getPlaintext();
            $html = $section->getHTML();
        } else {
            $plaintext = $section;
            $html = phutil_escape_html_newlines(phutil_tag('div', array(), $section));
        }

        $this->addPlaintextSection($header, $plaintext);
        $this->addHTMLSection($header, $html);
        return $this;
    }

    /**
     * @param $header
     * @param $text
     * @param bool $indent
     * @return $this
     * @author 陈妙威
     */
    public function addPlaintextSection($header, $text, $indent = true)
    {
        if ($indent) {
            $text = $this->indent($text);
        }
        $this->sections[] = $header . "\n" . $text;
        return $this;
    }

    /**
     * @param $header
     * @param $html_fragment
     * @return $this
     * @throws \Exception
     * @author 陈妙威
     */
    public function addHTMLSection($header, $html_fragment)
    {
        if ($header !== null) {
            $header = phutil_tag('strong', array(), $header);
        }

        $this->htmlSections[] = array(
            phutil_tag(
                'div',
                array(),
                array(
                    $header,
                    phutil_tag('div', array(), $html_fragment),
                )),
        );
        return $this;
    }

    /**
     * @param $header
     * @param $link
     * @return $this
     * @throws \Exception
     * @author 陈妙威
     */
    public function addLinkSection($header, $link)
    {
        $html = phutil_tag('a', array('href' => $link), $link);
        $this->addPlaintextSection($header, $link);
        $this->addHTMLSection($header, $html);
        return $this;
    }


    /**
     * Add an attachment.
     *
     * @param PhabricatorMetaMTAAttachment $attachment
     * @return PhabricatorMetaMTAMailBody
     * @task compose
     */
    public function addAttachment(PhabricatorMetaMTAAttachment $attachment)
    {
        $this->attachments[] = $attachment;
        return $this;
    }


    /* -(  Rendering  )---------------------------------------------------------- */


    /**
     * Render the email body.
     *
     * @return string Rendered body.
     * @task render
     */
    public function render()
    {
        return implode("\n\n", $this->sections) . "\n";
    }

    /**
     * @return string
     * @throws \Exception
     * @author 陈妙威
     */
    public function renderHTML()
    {
        $br = phutil_tag('br');
        $body = phutil_implode_html($br, $this->htmlSections);
        return (string)hsprintf('%s', array($body, $br));
    }

    /**
     * Retrieve attachments.
     *
     * @return array <PhabricatorMetaMTAAttachment> Attachments.
     * @task render
     */
    public function getAttachments()
    {
        return $this->attachments;
    }


    /**
     * Indent a block of text for rendering under a section heading.
     *
     * @param string Text to indent.
     * @return string Indented text.
     * @task render
     */
    private function indent($text)
    {
        return rtrim("  " . str_replace("\n", "\n  ", $text));
    }


    /**
     * @return PhutilRemarkupEngine
     * @author 陈妙威
     * @throws Exception
     * @throws \Exception
     */
    private function newMarkupEngine()
    {
        $engine = PhabricatorMarkupEngine::newMarkupEngine(array())
            ->setConfig('viewer', $this->getViewer())
            ->setConfig('uri.base', PhabricatorEnv::getProductionURI('/'));

        $context = $this->getContextObject();
        if ($context) {
            $engine->setConfig('contextObject', $context);
        }

        return $engine;
    }

}

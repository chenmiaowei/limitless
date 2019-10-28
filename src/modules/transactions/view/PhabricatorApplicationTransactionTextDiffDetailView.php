<?php

namespace orangins\modules\transactions\view;

use Exception;
use orangins\lib\view\AphrontView;
use orangins\lib\view\phui\PHUITabGroupView;
use orangins\lib\view\phui\PHUITabView;
use PhutilInvalidStateException;
use PhutilProseDifferenceEngine;
use PhutilSafeHTML;
use Yii;

/**
 * Class PhabricatorApplicationTransactionTextDiffDetailView
 * @package orangins\modules\transactions\view
 * @author 陈妙威
 */
final class PhabricatorApplicationTransactionTextDiffDetailView
    extends AphrontView
{

    /**
     * @var
     */
    private $oldText;
    /**
     * @var
     */
    private $newText;

    /**
     * @param $new_text
     * @return $this
     * @author 陈妙威
     */
    public function setNewText($new_text)
    {
        $this->newText = $new_text;
        return $this;
    }

    /**
     * @param $old_text
     * @return $this
     * @author 陈妙威
     */
    public function setOldText($old_text)
    {
        $this->oldText = $old_text;
        return $this;
    }

    /**
     * @return PhutilSafeHTML
     * @throws PhutilInvalidStateException
     * @throws Exception
     * @author 陈妙威
     */
    public function renderForMail()
    {
        $diff = $this->buildDiff();

        $viewer = $this->getViewer();
        $old_bright = $viewer->getCSSValue('old-bright');
        $new_bright = $viewer->getCSSValue('new-bright');

        $old_styles = array(
            'padding: 0 2px;',
            'color: #333333;',
            "background: {$old_bright};",
        );
        $old_styles = implode(' ', $old_styles);

        $new_styles = array(
            'padding: 0 2px;',
            'color: #333333;',
            "background: {$new_bright};",
        );
        $new_styles = implode(' ', $new_styles);

        $omit_styles = array(
            'padding: 8px 0;',
        );
        $omit_styles = implode(' ', $omit_styles);

        $result = array();
        foreach ($diff->getSummaryParts() as $part) {
            $type = $part['type'];
            $text = $part['text'];
            switch ($type) {
                case '.':
                    $result[] = phutil_tag(
                        'div',
                        array(
                            'style' => $omit_styles,
                        ),
                        Yii::t("app", '...'));
                    break;
                case '-':
                    $result[] = phutil_tag(
                        'span',
                        array(
                            'style' => $old_styles,
                        ),
                        $text);
                    break;
                case '+':
                    $result[] = phutil_tag(
                        'span',
                        array(
                            'style' => $new_styles,
                        ),
                        $text);
                    break;
                case '=':
                    $result[] = $text;
                    break;
            }
        }

        $styles = array(
            'white-space: pre-wrap;',
            'color: #74777D;',
        );

        // Beyond applying "pre-wrap", convert newlines to "<br />" explicitly
        // to improve behavior in clients like Airmail.
        $result = phutil_escape_html_newlines($result);

        return phutil_tag(
            'div',
            array(
                'style' => implode(' ', $styles),
            ),
            $result);
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function render()
    {
        $diff = $this->buildDiff();

//    require_celerity_resource('differential-changeset-view-css');

        $result = array();
        foreach ($diff->getParts() as $part) {
            $type = $part['type'];
            $text = $part['text'];
            switch ($type) {
                case '-':
                    $result[] = phutil_tag(
                        'span',
                        array(
                            'class' => 'old',
                        ),
                        $text);
                    break;
                case '+':
                    $result[] = phutil_tag(
                        'span',
                        array(
                            'class' => 'new',
                        ),
                        $text);
                    break;
                case '=':
                    $result[] = $text;
                    break;
            }
        }

        $diff_view = phutil_tag(
            'div',
            array(
                'class' => 'prose-diff',
            ),
            $result);

        $old_view = phutil_tag(
            'div',
            array(
                'class' => 'prose-diff',
            ),
            $this->oldText);

        $new_view = phutil_tag(
            'div',
            array(
                'class' => 'prose-diff',
            ),
            $this->newText);

        return (new PHUITabGroupView())
            ->addTab(
                (new PHUITabView())
                    ->setKey('old')
                    ->setName(Yii::t("app", 'Old'))
                    ->appendChild($old_view))
            ->addTab(
                (new PHUITabView())
                    ->setKey('new')
                    ->setName(Yii::t("app", 'New'))
                    ->appendChild($new_view))
            ->addTab(
                (new PHUITabView())
                    ->setKey('diff')
                    ->setName(Yii::t("app", 'Diff'))
                    ->appendChild($diff_view))
            ->selectTab('diff');
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    private function buildDiff()
    {
        $engine = new PhutilProseDifferenceEngine();
        return $engine->getDiff($this->oldText, $this->newText);
    }

}

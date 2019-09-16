<?php

namespace orangins\lib\response;

use orangins\lib\helpers\JavelinHtml;

/**
 * Class AphrontWebpageResponse
 * @package orangins\lib\response
 * @author 陈妙威
 */
final class AphrontWebpageResponse extends AphrontHTMLResponse
{

    /**
     * @var
     */
    private $content;
    /**
     * @var
     */
    private $unexpectedOutput;

    /**
     * @param $content
     * @return $this
     * @author 陈妙威
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param $unexpected_output
     * @return $this
     * @author 陈妙威
     */
    public function setUnexpectedOutput($unexpected_output)
    {
        $this->unexpectedOutput = $unexpected_output;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getUnexpectedOutput()
    {
        return $this->unexpectedOutput;
    }

    /**
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function buildResponseString()
    {
        $unexpected_output = $this->getUnexpectedOutput();
        if (strlen($unexpected_output)) {
            $style = array(
                'background: linear-gradient(180deg, #eeddff, #ddbbff);',
                'white-space: pre-wrap;',
                'z-index: 200000;',
                'position: relative;',
                'padding: 16px;',
                'font-family: monospace;',
                'text-shadow: 1px 1px 1px white;',
            );

            $unexpected_header = JavelinHtml::tag('div', $unexpected_output, array(
                'style' => implode(' ', $style),
            ));
        } else {
            $unexpected_header = '';
        }

        return implode("\n", [$unexpected_header, $this->content]);
    }

}

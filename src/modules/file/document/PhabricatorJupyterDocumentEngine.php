<?php

namespace orangins\modules\file\document;

use orangins\lib\markup\PhabricatorSyntaxHighlighter;
use orangins\modules\people\models\PhabricatorUser;
use PhutilJSON;
use PhutilJSONParserException;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorJupyterDocumentEngine
 * @package orangins\modules\file\document
 * @author 陈妙威
 */
final class PhabricatorJupyterDocumentEngine
    extends PhabricatorDocumentEngine
{

    /**
     *
     */
    const ENGINEKEY = 'jupyter';

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed|string
     * @author 陈妙威
     */
    public function getViewAsLabel(PhabricatorDocumentRef $ref)
    {
        return \Yii::t("app",'View as Jupyter Notebook');
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return string
     * @author 陈妙威
     */
    protected function getDocumentIconIcon(PhabricatorDocumentRef $ref)
    {
        return 'fa-sun-o';
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return string
     * @author 陈妙威
     */
    protected function getDocumentRenderingText(PhabricatorDocumentRef $ref)
    {
        return \Yii::t("app",'Rendering Jupyter Notebook...');
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool
     * @author 陈妙威
     */
    public function shouldRenderAsync(PhabricatorDocumentRef $ref)
    {
        return true;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return int
     * @author 陈妙威
     */
    protected function getContentScore(PhabricatorDocumentRef $ref)
    {
        $name = $ref->getName();

        if (preg_match('/\\.ipynb\z/i', $name)) {
            return 2000;
        }

        return 500;
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return bool|mixed
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function canRenderDocumentType(PhabricatorDocumentRef $ref)
    {
        return $ref->isProbablyJSON();
    }

    /**
     * @param PhabricatorDocumentRef $ref
     * @return mixed|\PhutilSafeHTML
     * @throws \PhutilMethodNotImplementedException
     * @author 陈妙威
     */
    protected function newDocumentContent(PhabricatorDocumentRef $ref)
    {
        $viewer = $this->getViewer();
        $content = $ref->loadData();

        try {
            $data = phutil_json_decode($content);
        } catch (PhutilJSONParserException $ex) {
            return $this->newMessage(
                \Yii::t("app",
                    'This is not a valid JSON document and can not be rendered as ' .
                    'a Jupyter notebook: %s.',
                    $ex->getMessage()));
        }

        if (!is_array($data)) {
            return $this->newMessage(
                \Yii::t("app",
                    'This document does not encode a valid JSON object and can not ' .
                    'be rendered as a Jupyter notebook.'));
        }


        $nbformat = ArrayHelper::getValue($data, 'nbformat');
        if (!strlen($nbformat)) {
            return $this->newMessage(
                \Yii::t("app",
                    'This document is missing an "nbformat" field. Jupyter notebooks ' .
                    'must have this field.'));
        }

        if ($nbformat !== 4) {
            return $this->newMessage(
                \Yii::t("app",
                    'This Jupyter notebook uses an unsupported version of the file ' .
                    'format (found version %s, expected version 4).',
                    $nbformat));
        }

        $cells = ArrayHelper::getValue($data, 'cells');
        if (!is_array($cells)) {
            return $this->newMessage(
                \Yii::t("app",
                    'This Jupyter notebook does not specify a list of "cells".'));
        }

        if (!$cells) {
            return $this->newMessage(
                \Yii::t("app",
                    'This Jupyter notebook does not specify any notebook cells.'));
        }

        $rows = array();
        foreach ($cells as $cell) {
            $rows[] = $this->renderJupyterCell($viewer, $cell);
        }

        $notebook_table = phutil_tag(
            'table',
            array(
                'class' => 'jupyter-notebook',
            ),
            $rows);

        $container = phutil_tag(
            'div',
            array(
                'class' => 'document-engine-jupyter',
            ),
            $notebook_table);

        return $container;
    }

    /**
     * @param PhabricatorUser $viewer
     * @param array $cell
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    private function renderJupyterCell(
        PhabricatorUser $viewer,
        array $cell)
    {

        list($label, $content) = $this->renderJupyterCellContent($viewer, $cell);

        $label_cell = phutil_tag(
            'th',
            array(),
            $label);

        $content_cell = phutil_tag(
            'td',
            array(),
            $content);

        return phutil_tag(
            'tr',
            array(),
            array(
                $label_cell,
                $content_cell,
            ));
    }

    /**
     * @param PhabricatorUser $viewer
     * @param array $cell
     * @return array
     * @author 陈妙威
     * @throws \Exception
     */
    private function renderJupyterCellContent(
        PhabricatorUser $viewer,
        array $cell)
    {

        $cell_type = ArrayHelper::getValue($cell, 'cell_type');
        switch ($cell_type) {
            case 'markdown':
                return $this->newMarkdownCell($cell);
            case 'code':
                return $this->newCodeCell($cell);
        }

        return $this->newRawCell((new PhutilJSON())->encodeFormatted($cell));
    }

    /**
     * @param $content
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    private function newRawCell($content)
    {
        return array(
            null,
            phutil_tag(
                'div',
                array(
                    'class' => 'jupyter-cell-raw PhabricatorMonospaced',
                ),
                $content),
        );
    }

    /**
     * @param array $cell
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    private function newMarkdownCell(array $cell)
    {
        $content = ArrayHelper::getValue($cell, 'source');
        if (!is_array($content)) {
            $content = array();
        }

        $content = implode('', $content);
        $content = phutil_escape_html_newlines($content);

        return array(
            null,
            phutil_tag(
                'div',
                array(
                    'class' => 'jupyter-cell-markdown',
                ),
                $content),
        );
    }

    /**
     * @param array $cell
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    private function newCodeCell(array $cell)
    {
        $execution_count = ArrayHelper::getValue($cell, 'execution_count');
        if ($execution_count) {
            $label = 'In [' . $execution_count . ']:';
        } else {
            $label = null;
        }

        $content = ArrayHelper::getValue($cell, 'source');
        if (!is_array($content)) {
            $content = array();
        }

        $content = implode('', $content);

        $content = PhabricatorSyntaxHighlighter::highlightWithLanguage(
            'py',
            $content);

        $outputs = array();
        $output_list = ArrayHelper::getValue($cell, 'outputs');
        if (is_array($output_list)) {
            foreach ($output_list as $output) {
                $outputs[] = $this->newOutput($output);
            }
        }

        return array(
            $label,
            array(
                phutil_tag(
                    'div',
                    array(
                        'class' => 'jupyter-cell-code PhabricatorMonospaced remarkup-code',
                    ),
                    array(
                        $content,
                    )),
                $outputs,
            ),
        );
    }

    /**
     * @param array $output
     * @return \PhutilSafeHTML|string
     * @throws \Exception
     * @author 陈妙威
     */
    private function newOutput(array $output)
    {
        if (!is_array($output)) {
            return \Yii::t("app",'<Invalid Output>');
        }

        $classes = array(
            'jupyter-output',
            'PhabricatorMonospaced',
        );

        $output_name = ArrayHelper::getValue($output, 'name');
        switch ($output_name) {
            case 'stderr':
                $classes[] = 'jupyter-output-stderr';
                break;
        }

        $output_type = ArrayHelper::getValue($output, 'output_type');
        switch ($output_type) {
            case 'execute_result':
            case 'display_data':
                $data = ArrayHelper::getValue($output, 'data');

                $image_formats = array(
                    'image/png',
                    'image/jpeg',
                    'image/jpg',
                    'image/gif',
                );

                foreach ($image_formats as $image_format) {
                    if (!isset($data[$image_format])) {
                        continue;
                    }

                    $raw_data = $data[$image_format];
                    if (!is_array($raw_data)) {
                        $raw_data = array($raw_data);
                    }
                    $raw_data = implode('', $raw_data);

                    $content = phutil_tag(
                        'img',
                        array(
                            'src' => 'data:' . $image_format . ';base64,' . $raw_data,
                        ));

                    break 2;
                }

                if (isset($data['text/html'])) {
                    $content = $data['text/html'];
                    $classes[] = 'jupyter-output-html';
                    break;
                }

                if (isset($data['application/javascript'])) {
                    $content = $data['application/javascript'];
                    $classes[] = 'jupyter-output-html';
                    break;
                }

                if (isset($data['text/plain'])) {
                    $content = $data['text/plain'];
                    break;
                }

                break;
            case 'stream':
            default:
                $content = ArrayHelper::getValue($output, 'text');
                if (!is_array($content)) {
                    $content = array();
                }
                $content = implode('', $content);
                break;
        }

        return phutil_tag(
            'div',
            array(
                'class' => implode(' ', $classes),
            ),
            $content);
    }

}

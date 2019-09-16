<?php

namespace orangins\lib\view\layout;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;
use PhutilURI;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorSourceCodeView
 * @package orangins\lib\view\layout
 * @author 陈妙威
 */
final class PhabricatorSourceCodeView extends AphrontView
{

    /**
     * @var
     */
    private $lines;
    /**
     * @var
     */
    private $uri;
    /**
     * @var array
     */
    private $highlights = array();
    /**
     * @var bool
     */
    private $canClickHighlight = true;
    /**
     * @var bool
     */
    private $truncatedFirstBytes = false;
    /**
     * @var bool
     */
    private $truncatedFirstLines = false;
    /**
     * @var
     */
    private $symbolMetadata;
    /**
     * @var
     */
    private $blameMap;
    /**
     * @var array
     */
    private $coverage = array();

    /**
     * @param array $lines
     * @return $this
     * @author 陈妙威
     */
    public function setLines(array $lines)
    {
        $this->lines = $lines;
        return $this;
    }

    /**
     * @param PhutilURI $uri
     * @return $this
     * @author 陈妙威
     */
    public function setURI(PhutilURI $uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @param array $array
     * @return $this
     * @author 陈妙威
     */
    public function setHighlights(array $array)
    {
        $this->highlights = array_fuse($array);
        return $this;
    }

    /**
     * @return $this
     * @author 陈妙威
     */
    public function disableHighlightOnClick()
    {
        $this->canClickHighlight = false;
        return $this;
    }

    /**
     * @param $truncated_first_bytes
     * @return $this
     * @author 陈妙威
     */
    public function setTruncatedFirstBytes($truncated_first_bytes)
    {
        $this->truncatedFirstBytes = $truncated_first_bytes;
        return $this;
    }

    /**
     * @param $truncated_first_lines
     * @return $this
     * @author 陈妙威
     */
    public function setTruncatedFirstLines($truncated_first_lines)
    {
        $this->truncatedFirstLines = $truncated_first_lines;
        return $this;
    }

    /**
     * @param array $symbol_metadata
     * @return $this
     * @author 陈妙威
     */
    public function setSymbolMetadata(array $symbol_metadata)
    {
        $this->symbolMetadata = $symbol_metadata;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSymbolMetadata()
    {
        return $this->symbolMetadata;
    }

    /**
     * @param array $map
     * @return $this
     * @author 陈妙威
     */
    public function setBlameMap(array $map)
    {
        $this->blameMap = $map;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getBlameMap()
    {
        return $this->blameMap;
    }

    /**
     * @param array $coverage
     * @return $this
     * @author 陈妙威
     */
    public function setCoverage(array $coverage)
    {
        $this->coverage = $coverage;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getCoverage()
    {
        return $this->coverage;
    }

    /**
     * @return mixed|string
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $blame_map = $this->getBlameMap();
        $has_blame = ($blame_map !== null);

//        require_celerity_resource('phabricator-source-code-view-css');
//        require_celerity_resource('syntax-highlighting-css');

        if ($this->canClickHighlight) {
//            Javelin::initBehavior('phabricator-line-linker');
        }

        $line_number = 1;

        $rows = array();

        $lines = $this->lines;
        if ($this->truncatedFirstLines) {
            $lines[] = JavelinHtml::phutil_tag(
                'span',
                array(
                    'class' => 'c',
                ),
                \Yii::t("app", '...'));
        } else if ($this->truncatedFirstBytes) {
            $last_key = last_key($lines);
            $lines[$last_key] = JavelinHtml::hsprintf(
                '%s%s',
                $lines[$last_key],
                JavelinHtml::phutil_tag(
                    'span',
                    array(
                        'class' => 'c',
                    ),
                    \Yii::t("app", '...')));
        }

        $base_uri = (string)$this->uri;
        $wrote_anchor = false;

        $coverage = $this->getCoverage();
        $coverage_count = count($coverage);
        $coverage_data = ipull($coverage, 'data');

        // TODO: Modularize this properly, see T13125.
        $coverage_map = array(
            'C' => 'background: #66bbff;',
            'U' => 'background: #dd8866;',
            'N' => 'background: #ddeeff;',
            'X' => 'background: #aa00aa;',
        );

        foreach ($lines as $line) {
            $row_attributes = array();
            if (isset($this->highlights[$line_number])) {
                $row_attributes['class'] = 'phabricator-source-highlight';
                if (!$wrote_anchor) {
                    $row_attributes['id'] = 'phabricator-line-linker-anchor';
                    $wrote_anchor = true;
                }
            }

            if ($this->canClickHighlight) {
                if ($base_uri) {
                    $line_href = $base_uri . '$' . $line_number;
                } else {
                    $line_href = null;
                }

                $tag_number = JavelinHtml::phutil_tag(
                    'a',
                    array(
                        'href' => $line_href,
                        'data-n' => $line_number,
                    ));
            } else {
                $tag_number = JavelinHtml::phutil_tag(
                    'span',
                    array(),
                    $line_number);
            }

            if ($has_blame) {
                $lines = ArrayHelper::getValue($blame_map, $line_number);

                if ($lines) {
                    $skip_blame = 'skip';
                    $info_blame = 'info';
                } else {
                    $skip_blame = null;
                    $info_blame = null;
                }

                $blame_cells = array(
                    JavelinHtml::phutil_tag(
                        'th',
                        array(
                            'class' => 'phabricator-source-blame-skip',
                            'data-blame' => $skip_blame,
                        )),
                    JavelinHtml::phutil_tag(
                        'th',
                        array(
                            'class' => 'phabricator-source-blame-info',
                            'data-blame' => $info_blame,
                            'data-blame-lines' => $lines,
                        )),
                );
            } else {
                $blame_cells = null;
            }

            $coverage_cells = array();
            foreach ($coverage as $coverage_idx => $coverage_spec) {
                if (isset($coverage_spec['data'][$line_number - 1])) {
                    $coverage_char = $coverage_spec['data'][$line_number - 1];
                } else {
                    $coverage_char = null;
                }

                $coverage_style = ArrayHelper::getValue($coverage_map, $coverage_char, null);

                $coverage_cells[] = JavelinHtml::phutil_tag(
                    'th',
                    array(
                        'class' => 'phabricator-source-coverage',
                        'style' => $coverage_style,
                        'data-coverage' => $coverage_idx . '/' . $coverage_char,
                    ));
            }

            $rows[] = JavelinHtml::phutil_tag(
                'tr',
                $row_attributes,
                array(
                    $blame_cells,
                    JavelinHtml::phutil_tag(
                        'th',
                        array(
                            'class' => 'phabricator-source-line',
                        ),
                        $tag_number),
                    JavelinHtml::phutil_tag(
                        'td',
                        array(
                            'class' => 'phabricator-source-code',
                        ),
                        $line),
                    $coverage_cells,
                ));

            $line_number++;
        }

        $classes = array();
        $classes[] = 'phabricator-source-code-view';
        $classes[] = 'remarkup-code';
        $classes[] = 'PhabricatorMonospaced';

        $symbol_metadata = $this->getSymbolMetadata();

        $sigils = array();
        $sigils[] = 'phabricator-source';
        $sigils[] = 'has-symbols';

//        Javelin::initBehavior('repository-crossreference');

        return JavelinHtml::phutil_tag_div(
            'phabricator-source-code-container',
            JavelinHtml::phutil_tag(
                'table',
                array(
                    'class' => implode(' ', $classes),
                    'sigil' => implode(' ', $sigils),
                    'meta' => array(
                        'uri' => (string)$this->uri,
                        'symbols' => $symbol_metadata,
                    ),
                ),
                phutil_implode_html('', $rows)));
    }

}

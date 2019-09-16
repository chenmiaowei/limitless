<?php

namespace orangins\lib\view\layout;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIBoxView;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * Class AphrontMultiColumnView
 * @package orangins\lib\view\layout
 * @author 陈妙威
 */
final class AphrontMultiColumnView extends AphrontView
{

    /**
     *
     */
    const GUTTER_SMALL = 'msr';
    /**
     *
     */
    const GUTTER_MEDIUM = 'mmr';
    /**
     *
     */
    const GUTTER_LARGE = 'mlr';

    /**
     * @var
     */
    private $id;
    /**
     * @var array
     */
    private $columns = array();
    /**
     * @var bool
     */
    private $fluidLayout = false;
    /**
     * @var bool
     */
    private $fluidishLayout = false;
    /**
     * @var
     */
    private $gutter;
    /**
     * @var
     */
    private $border;

    /**
     * @param $id
     * @return $this
     * @author 陈妙威
     */
    public function setID($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @param $column
     * @param null $class
     * @param null $sigil
     * @param null $metadata
     * @return $this
     * @author 陈妙威
     */
    public function addColumn(
        $column,
        $class = null,
        $sigil = null,
        $metadata = null)
    {
        $this->columns[] = array(
            'column' => $column,
            'class' => $class,
            'sigil' => $sigil,
            'metadata' => $metadata,
        );
        return $this;
    }

    /**
     * @param $layout
     * @return $this
     * @author 陈妙威
     */
    public function setFluidlayout($layout)
    {
        $this->fluidLayout = $layout;
        return $this;
    }

    /**
     * @param $layout
     * @return $this
     * @author 陈妙威
     */
    public function setFluidishLayout($layout)
    {
        $this->fluidLayout = true;
        $this->fluidishLayout = $layout;
        return $this;
    }

    /**
     * @param $gutter
     * @return $this
     * @author 陈妙威
     */
    public function setGutter($gutter)
    {
        $this->gutter = $gutter;
        return $this;
    }

    /**
     * @param $border
     * @return $this
     * @author 陈妙威
     */
    public function setBorder($border)
    {
        $this->border = $border;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     */
    public function render()
    {
        $classes = array();
        $classes[] = 'aphront-multi-column-inner';
        $classes[] = 'grouped';

        if ($this->fluidishLayout || $this->fluidLayout) {
            // we only support seven columns for now for fluid views; see T4054
            if (count($this->columns) > 7) {
                throw new Exception(\Yii::t("app", 'No more than 7 columns per view.'));
            }
        }

        $classes[] = 'row aphront-multi-column-' . count($this->columns) . '-up';

        $columns = array();
        $i = 0;
        foreach ($this->columns as $column_data) {
            $column_class = array('aphront-multi-column-column');
            if ($this->gutter) {
                $column_class[] = $this->gutter;
            }
            $outer_class = array('aphront-multi-column-column-outer');
            if (++$i === count($this->columns)) {
                $column_class[] = 'aphront-multi-column-column-last';
                $outer_class[] = 'aphront-multi-colum-column-outer-last';
            }
            $column = $column_data['column'];
            if ($column_data['class']) {
                $outer_class[] = $column_data['class'];
            }
            $column_sigil = ArrayHelper::getValue($column_data, 'sigil');
            $column_metadata = ArrayHelper::getValue($column_data, 'metadata');
            $column_inner = JavelinHtml::phutil_tag('div', array(
                    'class' => implode(' ', $column_class),
                    'sigil' => $column_sigil,
                    'meta' => $column_metadata,
                ),
                $column);
            $columns[] = JavelinHtml::phutil_tag('div', array(
                    'class' => implode(' ', $outer_class),
                ),
                $column_inner);
        }

        $view = JavelinHtml::phutil_tag('div', array(
                'class' => implode(' ', $classes),
            ),
            array(
                $columns,
            ));

        $classes = array();
        $classes[] = 'aphront-multi-column-outer';
        if ($this->fluidLayout) {
            $classes[] = 'aphront-multi-column-fluid';
            if ($this->fluidishLayout) {
                $classes[] = 'aphront-multi-column-fluidish';
            }
        } else {
            $classes[] = 'aphront-multi-column-fixed';
        }

        $board = JavelinHtml::phutil_tag(
            'div',
            array(
                'class' => implode(' ', $classes),
            ),
            $view);

        if ($this->border) {
            $board = (new PHUIBoxView())
                ->setBorder(true)
                ->appendChild($board)
                ->addPadding(PHUI::PADDING_MEDIUM_TOP)
                ->addPadding(PHUI::PADDING_MEDIUM_BOTTOM);
        }

        return JavelinHtml::phutil_tag('div', array(
                'class' => 'aphront-multi-column-view',
                'id' => $this->getID(),
                // TODO: It would be nice to convert this to an AphrontTagView and
                // use addSigil() from Workboards instead of hard-coding this.
                'sigil' => 'aphront-multi-column-view',
            ),
            $board);
    }
}

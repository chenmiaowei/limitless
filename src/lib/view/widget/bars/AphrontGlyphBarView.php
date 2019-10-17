<?php

namespace orangins\lib\view\widget\bars;

use PhutilSafeHTML;

/**
 * Class AphrontGlyphBarView
 * @package orangins\lib\view\widget\bars
 * @author 陈妙威
 */
final class AphrontGlyphBarView extends AphrontBarView
{

    /**
     *
     */
    const BLACK_STAR = "\xE2\x98\x85";
    /**
     *
     */
    const WHITE_STAR = "\xE2\x98\x86";

    /**
     * @var
     */
    private $value;
    /**
     * @var int
     */
    private $max = 100;
    /**
     * @var int
     */
    private $numGlyphs = 5;
    /**
     * @var
     */
    private $fgGlyph;
    /**
     * @var
     */
    private $bgGlyph;

    /**
     * @return string
     * @author 陈妙威
     */
    protected function getDefaultColor()
    {
        return parent::COLOR_AUTO_GOODNESS;
    }

    /**
     * @param $value
     * @return $this
     * @author 陈妙威
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param $max
     * @return $this
     * @author 陈妙威
     */
    public function setMax($max)
    {
        $this->max = $max;
        return $this;
    }

    /**
     * @param $nn
     * @return $this
     * @author 陈妙威
     */
    public function setNumGlyphs($nn)
    {
        $this->numGlyphs = $nn;
        return $this;
    }

    /**
     * @param PhutilSafeHTML $fg_glyph
     * @return $this
     * @author 陈妙威
     */
    public function setGlyph(PhutilSafeHTML $fg_glyph)
    {
        $this->fgGlyph = $fg_glyph;
        return $this;
    }

    /**
     * @param PhutilSafeHTML $bg_glyph
     * @return $this
     * @author 陈妙威
     */
    public function setBackgroundGlyph(PhutilSafeHTML $bg_glyph)
    {
        $this->bgGlyph = $bg_glyph;
        return $this;
    }

    /**
     * @return float|int
     * @author 陈妙威
     */
    protected function getRatio()
    {
        return min($this->value, $this->max) / $this->max;
    }

    /**
     * @return \PhutilSafeHTML
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
//        require_celerity_resource('aphront-bars');
        $ratio = $this->getRatio();
        $percentage = 100 * $ratio;

        $is_star = false;
        if ($this->fgGlyph) {
            $fg_glyph = $this->fgGlyph;
            if ($this->bgGlyph) {
                $bg_glyph = $this->bgGlyph;
            } else {
                $bg_glyph = $fg_glyph;
            }
        } else {
            $is_star = true;
            $fg_glyph = self::BLACK_STAR;
            $bg_glyph = self::WHITE_STAR;
        }

        $fg_glyphs = array_fill(0, $this->numGlyphs, $fg_glyph);
        $bg_glyphs = array_fill(0, $this->numGlyphs, $bg_glyph);

        $color = $this->getColor();

        return phutil_tag(
            'div',
            array(
                'class' => "aphront-bar glyph color-{$color}",
            ),
            array(
                phutil_tag(
                    'div',
                    array(
                        'class' => 'glyphs' . ($is_star ? ' starstar' : ''),
                    ),
                    array(
                        phutil_tag(
                            'div',
                            array(
                                'class' => 'fg',
                                'style' => "width: {$percentage}%;",
                            ),
                            $fg_glyphs),
                        phutil_tag(
                            'div',
                            array(),
                            $bg_glyphs),
                    )),
                phutil_tag(
                    'div',
                    array('class' => 'caption'),
                    $this->getCaption()),
            ));
    }

}

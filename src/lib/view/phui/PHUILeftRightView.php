<?php
namespace orangins\lib\view\phui;

use orangins\lib\view\AphrontTagView;

final class PHUILeftRightView extends AphrontTagView {

  private $left;
  private $right;
  private $verticalAlign;

  const ALIGN_TOP = 'top';
  const ALIGN_MIDDLE = 'middle';
  const ALIGN_BOTTOM = 'bottom';

  public function setLeft($left) {
    $this->left = $left;
    return $this;
  }

  public function setRight($right) {
    $this->right = $right;
    return $this;
  }

  public function setVerticalAlign($align) {
    $this->verticalAlign = $align;
    return $this;
  }

  protected function getTagAttributes() {
//    require_celerity_resource('phui-left-right-css');

    $classes = array();
    $classes[] = 'phui-left-right-view';

    if ($this->verticalAlign) {
      $classes[] = 'phui-lr-view-'.$this->verticalAlign;
    }

    return array('class' => implode(' ', $classes));
  }

  protected function getTagName() {
    return 'div';
  }

  protected function getTagContent() {
    $left = JavelinHtml::phutil_tag_div('phui-left-view', $this->left);
    $right = JavelinHtml::phutil_tag_div('phui-right-view', $this->right);

    return JavelinHtml::phutil_tag_div('phui-lr-container', array($left, $right));
  }
}

<?php
namespace orangins\lib\view\phui;

use orangins\lib\view\AphrontTagView;

final class PHUIStatusListView extends AphrontTagView {

  private $items;

  public function addItem(PHUIStatusItemView $item) {
    $this->items[] = $item;
    return $this;
  }

  protected function canAppendChild() {
    return false;
  }

  protected function getTagName() {
    return 'table';
  }

  protected function getTagAttributes() {
//    require_celerity_resource('phui-status-list-view-css');

    $classes = array();
    $classes[] = 'phui-status-list-view';

    return array(
      'class' => implode(' ', $classes),
    );
  }

  protected function getTagContent() {
    return $this->items;
  }
}

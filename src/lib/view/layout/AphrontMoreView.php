<?php
namespace orangins\lib\view\layout;

use orangins\lib\helpers\JavelinHtml;
use orangins\lib\view\AphrontView;

final class AphrontMoreView extends AphrontView {

  private $some;
  private $more;
  private $expandtext;

  public function setSome($some) {
    $this->some = $some;
    return $this;
  }

  public function setMore($more) {
    $this->more = $more;
    return $this;
  }

  public function setExpandText($text) {
    $this->expandtext = $text;
    return $this;
  }

  public function render() {

    $content = array();
    $content[] = $this->some;

    if ($this->more && $this->more != $this->some) {
      $text = "(".\Yii::t("app",'Show More')."\xE2\x80\xA6)";
      if ($this->expandtext !== null) {
        $text = $this->expandtext;
      }

      Javelin::initBehavior('aphront-more');
      $content[] = ' ';
      $content[] = JavelinHtml::phutil_tag(
        'a',
        array(
          'sigil'       => 'aphront-more-view-show-more',
          'mustcapture' => true,
          'href'        => '#',
          'meta'        => array(
            'more' => $this->more,
          ),
        ),
        $text);
    }

    return JavelinHtml::phutil_tag(
      'div',
      array(
        'sigil' => 'aphront-more-view',
      ),
      $content);
  }
}

<?php
namespace orangins\modules\celerity\postprocessor;

final class CelerityLargeFontPostprocessor
  extends CelerityPostprocessor {

  public function getPostprocessorKey() {
    return 'fontsizeplusone';
  }

  public function getPostprocessorName() {
    return \Yii::t("app",'Use Larger Font Size');
  }

  public function buildVariables() {
    return array(

      'basefont' => "14px 'Segoe UI', 'Segoe UI Web Regular', ".
        "'Segoe UI Symbol', 'Lato', 'Helvetica Neue', Helvetica, ".
        "Arial, sans-serif",

      // Font Sizes
      'biggestfontsize' => '16px',
      'biggerfontsize' => '15px',
      'normalfontsize' => '14px',
      'smallerfontsize' => '13px',
      'smallestfontsize' => '12px',

    );
  }

}

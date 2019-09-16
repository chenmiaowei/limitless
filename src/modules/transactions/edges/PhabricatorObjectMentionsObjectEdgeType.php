<?php
namespace orangins\modules\transactions\edges;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

final class PhabricatorObjectMentionsObjectEdgeType
  extends PhabricatorEdgeType {

  const EDGECONST = 52;

  public function getInverseEdgeConstant() {
    return PhabricatorObjectMentionedByObjectEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions() {
    return true;
  }

  public function getConduitKey() {
    return 'mention';
  }

  public function getConduitName() {
    return \Yii::t("app",'Mention');
  }

  public function getConduitDescription() {
    return \Yii::t("app",
      'The source object has a comment which mentions the destination object.');
  }

}

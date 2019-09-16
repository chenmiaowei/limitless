<?php
namespace orangins\modules\transactions\edges;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;

final class PhabricatorObjectMentionedByObjectEdgeType
  extends PhabricatorEdgeType {

  const EDGECONST = 51;

  public function getInverseEdgeConstant() {
    return PhabricatorObjectMentionsObjectEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions() {
    return true;
  }

  public function getTransactionAddString(
    $actor,
    $add_count,
    $add_edges) {

    return \Yii::t("app",
      '%s mentioned this in %s.',
      $actor,
      $add_edges);
  }

  public function getConduitKey() {
    return 'mentioned-in';
  }

  public function getConduitName() {
    return \Yii::t("app",'Mention In');
  }

  public function getConduitDescription() {
    return \Yii::t("app",
      'The source object is mentioned in a comment on the destination object.');
  }

}

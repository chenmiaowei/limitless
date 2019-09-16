<?php
namespace orangins\modules\auth\provider;

final class PhabricatorOAuth1SecretTemporaryTokenType
  extends PhabricatorAuthTemporaryTokenType {

  const TOKENTYPE = 'oauth1:request:secret';

  public function getTokenTypeDisplayName() {
    return \Yii::t("app",'OAuth1 Handshake Secret');
  }

  public function getTokenReadableTypeName(
    PhabricatorAuthTemporaryToken $token) {
    return \Yii::t("app",'OAuth1 Handshake Token');
  }

}

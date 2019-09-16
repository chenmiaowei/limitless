<?php
namespace orangins\modules\search\engineextension;

use orangins\lib\request\AphrontRequest;

final class PhabricatorHovercardEngineExtensionModule
  extends PhabricatorConfigModule {

  public function getModuleKey() {
    return 'hovercardengine';
  }

  public function getModuleName() {
    return \Yii::t("app",'Engine: Hovercards');
  }

  public function renderModuleStatus(AphrontRequest $request) {
    $viewer = $request->getViewer();

    $extensions = PhabricatorHovercardEngineExtension::getAllExtensions();

    $rows = array();
    foreach ($extensions as $extension) {
      $rows[] = array(
        $extension->getExtensionOrder(),
        $extension->getExtensionKey(),
        get_class($extension),
        $extension->getExtensionName(),
        $extension->isExtensionEnabled()
          ? \Yii::t("app",'Yes')
          : \Yii::t("app",'No'),
      );
    }

    return (new AphrontTableView($rows))
      ->setHeaders(
        array(
          \Yii::t("app",'Order'),
          \Yii::t("app",'Key'),
          \Yii::t("app",'Class'),
          \Yii::t("app",'Name'),
          \Yii::t("app",'Enabled'),
        ))
      ->setColumnClasses(
        array(
          null,
          null,
          null,
          'wide pri',
          null,
        ));
  }

}

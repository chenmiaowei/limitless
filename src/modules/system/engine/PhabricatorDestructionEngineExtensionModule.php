<?php
namespace orangins\modules\system\engine;

use orangins\lib\request\AphrontRequest;

final class PhabricatorDestructionEngineExtensionModule
  extends PhabricatorConfigModule {

  public function getModuleKey() {
    return 'destructionengine';
  }

  public function getModuleName() {
    return \Yii::t("app",'Engine: Destruction');
  }

  public function renderModuleStatus(AphrontRequest $request) {
    $viewer = $request->getViewer();

    $extensions = PhabricatorDestructionEngineExtension::getAllExtensions();

    $rows = array();
    foreach ($extensions as $extension) {
      $rows[] = array(
        get_class($extension),
        $extension->getExtensionName(),
      );
    }

    return (new AphrontTableView($rows))
      ->setHeaders(
        array(
          \Yii::t("app",'Class'),
          \Yii::t("app",'Name'),
        ))
      ->setColumnClasses(
        array(
          null,
          'wide pri',
        ));

  }

}

<?php
namespace orangins\modules\config\actions;

final class PhabricatorConfigPurgeCacheAction
  extends PhabricatorConfigAction {

  public function run() { $request = $this->getRequest();
    $viewer = $this->getViewer();
    $cancel_uri = $this->getApplicationURI('cache/');

    $opcode_cache = PhabricatorOpcodeCacheSpec::getActiveCacheSpec();
    $data_cache = PhabricatorDataCacheSpec::getActiveCacheSpec();

    $opcode_clearable = $opcode_cache->getClearCacheCallback();
    $data_clearable = $data_cache->getClearCacheCallback();

    if (!$opcode_clearable && !$data_clearable) {
      return $this->newDialog()
        ->setTitle(\Yii::t("app",'No Caches to Reset'))
        ->appendParagraph(
          \Yii::t("app",'None of the caches on this page can be cleared.'))
        ->addCancelButton($cancel_uri);
    }

    if ($request->isDialogFormPost()) {
      if ($opcode_clearable) {
        call_user_func($opcode_cache->getClearCacheCallback());
      }

      if ($data_clearable) {
        call_user_func($data_cache->getClearCacheCallback());
      }

      return (new AphrontRedirectResponse())->setURI($cancel_uri);
    }

    $caches = (new PHUIPropertyListView())
      ->setUser($viewer);

    if ($opcode_clearable) {
      $caches->addProperty(
        \Yii::t("app",'Opcode'),
        $opcode_cache->getName());
    }

    if ($data_clearable) {
      $caches->addProperty(
        \Yii::t("app",'Data'),
        $data_cache->getName());
    }

    return $this->newDialog()
      ->setTitle(\Yii::t("app",'Really Clear Cache?'))
      ->setShortTitle(\Yii::t("app",'Really Clear Cache'))
      ->appendParagraph(\Yii::t("app",'This will only affect the current web '.
      'frontend. Daemons and any other web frontends may continue '.
      'to use older, cached code from their opcache.'))
      ->appendParagraph(\Yii::t("app",'The following caches will be cleared:'))
      ->appendChild($caches)
      ->addSubmitButton(\Yii::t("app",'Clear Cache'))
      ->addCancelButton($cancel_uri);
  }
}

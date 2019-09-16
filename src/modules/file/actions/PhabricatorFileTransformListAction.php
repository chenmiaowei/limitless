<?php
namespace orangins\modules\file\actions;

final class PhabricatorFileTransformListAction
  extends PhabricatorFileAction {

  public function shouldAllowPublic() {
    return true;
  }

  public function run() { $request = $this->getRequest();
    $viewer = $this->getViewer();

    $file = PhabricatorFile::find()
      ->setViewer($viewer)
      ->withIDs(array($request->getURIData('id')))
      ->executeOne();
    if (!$file) {
      return new Aphront404Response();
    }

    $monogram = $file->getMonogram();

    $xdst = (new PhabricatorTransformedFile())->loadAllWhere(
      'transformedPHID = %s',
      $file->getPHID());

    $dst_rows = array();
    foreach ($xdst as $source) {
      $dst_rows[] = array(
        $source->getTransform(),
        $viewer->renderHandle($source->getOriginalPHID()),
      );
    }
    $dst_table = (new AphrontTableView($dst_rows))
      ->setHeaders(
        array(
          \Yii::t("app",'Key'),
          \Yii::t("app",'Source'),
        ))
      ->setColumnClasses(
        array(
          '',
          'wide',
        ))
      ->setNoDataString(
        \Yii::t("app",
          'This file was not created by transforming another file.'));

    $xsrc = (new PhabricatorTransformedFile())->loadAllWhere(
      'originalPHID = %s',
      $file->getPHID());
    $xsrc = mpull($xsrc, 'getTransformedPHID', 'getTransform');

    $src_rows = array();
    $xforms = PhabricatorFileTransform::getAllTransforms();
    foreach ($xforms as $xform) {
      $dst_phid = ArrayHelper::getValue($xsrc, $xform->getTransformKey());

      if ($xform->canApplyTransform($file)) {
        $can_apply = \Yii::t("app",'Yes');

        $view_href = $file->getURIForTransform($xform);
        $view_href = new PhutilURI($view_href);
        $view_href->setQueryParam('regenerate', 'true');

        $view_text = \Yii::t("app",'Regenerate');

        $view_link = phutil_tag(
          'a',
          array(
            'class' => 'small button button-grey',
            'href' => $view_href,
          ),
          $view_text);
      } else {
        $can_apply = phutil_tag('em', array(), \Yii::t("app",'No'));
        $view_link = phutil_tag('em', array(), \Yii::t("app",'None'));
      }

      if ($dst_phid) {
        $dst_link = $viewer->renderHandle($dst_phid);
      } else {
        $dst_link = phutil_tag('em', array(), \Yii::t("app",'None'));
      }

      $src_rows[] = array(
        $xform->getTransformName(),
        $xform->getTransformKey(),
        $can_apply,
        $dst_link,
        $view_link,
      );
    }

    $src_table = (new AphrontTableView($src_rows))
      ->setHeaders(
        array(
          \Yii::t("app",'Name'),
          \Yii::t("app",'Key'),
          \Yii::t("app",'Supported'),
          \Yii::t("app",'Transform'),
          \Yii::t("app",'View'),
        ))
      ->setColumnClasses(
        array(
          'wide',
          '',
          '',
          '',
          'action',
        ));

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb($monogram, '/'.$monogram);
    $crumbs->addTextCrumb(\Yii::t("app",'Transforms'));
    $crumbs->setBorder(true);

    $dst_box = (new PHUIObjectBoxView())
      ->setHeaderText(\Yii::t("app",'File Sources'))
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->setTable($dst_table);

    $src_box = (new PHUIObjectBoxView())
      ->setHeaderText(\Yii::t("app",'Available Transforms'))
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->setTable($src_table);

    $title = \Yii::t("app",'%s Transforms', $file->getName());

    $header = (new PHUIHeaderView())
      ->setHeader($title)
      ->setHeaderIcon('fa-arrows-alt');

    $view = (new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter(array(
        $dst_box,
        $src_box,
      ));

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->appendChild($view);

  }
}

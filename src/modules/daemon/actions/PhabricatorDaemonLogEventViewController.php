<?php
namespace orangins\modules\daemon\actions;

use orangins\modules\daemon\models\PhabricatorDaemonLogEvent;

final class PhabricatorDaemonLogEventViewController
  extends PhabricatorDaemonController {

  public function run() { $request = $this->getRequest();
    $id = $request->getURIData('id');

//    $event = (new PhabricatorDaemonLogEvent())->load($id);
    $event = PhabricatorDaemonLogEvent::findOne($id);
    if (!$event) {
      return new Aphront404Response();
    }

    $event_view = (new PhabricatorDaemonLogEventsView())
      ->setEvents(array($event))
      ->setUser($request->getUser())
      ->setCombinedLog(true)
      ->setShowFullMessage(true);

    $log_panel = (new PHUIObjectBoxView())
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->appendChild($event_view);

    $daemon_id = $event->getLogID();

    $crumbs = $this->buildApplicationCrumbs()
      ->addTextCrumb(
        \Yii::t("app",'Daemon %s', $daemon_id),
        $this->getApplicationURI("log/{$daemon_id}/"))
      ->addTextCrumb(\Yii::t("app",'Event %s', $event->getID()))
      ->setBorder(true);

    $header = (new PHUIHeaderView())
      ->setHeader(\Yii::t("app",'Combined Log'))
      ->setHeaderIcon('fa-file-text');

    $view = (new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter($log_panel);

    return $this->newPage()
      ->setTitle(\Yii::t("app",'Combined Daemon Log'))
      ->appendChild($view);

  }

}

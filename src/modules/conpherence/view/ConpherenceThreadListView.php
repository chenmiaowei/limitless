<?php
namespace orangins\modules\conpherence\view;

final class ConpherenceThreadListView extends AphrontView {

  const SEE_ALL_LIMIT = 16;

  private $baseURI;
  private $threads;

  public function setThreads(array $threads) {
    assert_instances_of($threads, 'ConpherenceThread');
    $this->threads = $threads;
    return $this;
  }

  public function setBaseURI($base_uri) {
    $this->baseURI = $base_uri;
    return $this;
  }

  public function render() {
//    require_celerity_resource('conpherence-menu-css');

    $menu = (new PHUIListView())
      ->addClass('conpherence-menu')
      ->setID('conpherence-menu');

    $header = $this->buildHeaderItemView();
    $menu->addMenuItem($header);

    // Blank State NUX
    if (empty($this->threads)) {
      $join_item = (new PHUIListItemView())
        ->setType(PHUIListItemView::TYPE_LINK)
        ->setHref('/conpherence/search/')
        ->setName(\Yii::t("app",'Join a Room'));
      $menu->addMenuItem($join_item);

      $create_item = (new PHUIListItemView())
        ->setType(PHUIListItemView::TYPE_LINK)
        ->setHref('/conpherence/new/')
        ->setWorkflow(true)
        ->setName(\Yii::t("app",'Create a Room'));
      $menu->addMenuItem($create_item);
    }

    $rooms = $this->buildRoomItems($this->threads);
    foreach ($rooms as $room) {
      $menu->addMenuItem($room);
    }

    $menu = phutil_tag_div('phabricator-side-menu', $menu);
    $menu = phutil_tag_div('phui-basic-nav', $menu);

    return $menu;
  }

  private function renderThreadItem(
    ConpherenceThread $thread) {

    $user = $this->getUser();
    $data = $thread->getDisplayData($user);
    $dom_id = $thread->getPHID().'-nav-item';

    return (new PHUIListItemView())
      ->setName($data['title'])
      ->setHref('/'.$thread->getMonogram())
      ->setProfileImage($data['image'])
      ->setCount($data['unread_count'])
      ->setType(PHUIListItemView::TYPE_CUSTOM)
      ->setID($thread->getPHID().'-nav-item')
      ->addSigil('conpherence-menu-click')
      ->setMetadata(
        array(
          'title' => $data['title'],
          'id' => $dom_id,
          'threadID' => $thread->getID(),
          'theme' => $data['theme'],
          ));
  }

  private function buildRoomItems(array $threads) {

    $items = array();
    $show_threads = $threads;
    $all_threads = false;
    if (count($threads) > self::SEE_ALL_LIMIT) {
      $show_threads = array_slice($threads, 0, self::SEE_ALL_LIMIT);
      $all_threads = true;
    }

    foreach ($show_threads as $thread) {
      $items[] = $this->renderThreadItem($thread);
    }

    // Send them to application search here
    if ($all_threads) {
      $items[] = (new PHUIListItemView())
        ->setType(PHUIListItemView::TYPE_LINK)
        ->setHref('/conpherence/search/query/participant/')
        ->setIcon('fa-external-link')
        ->setName(\Yii::t("app",'See All Joined'));
    }

    return $items;
  }

  private function buildHeaderItemView() {
    $rooms = phutil_tag(
      'a',
      array(
        'class' => 'room-list-href',
        'href' => '/conpherence/search/',
      ),
      \Yii::t("app",'Rooms'));

    $new_icon = (new PHUIIconView())
      ->setIcon('fa-plus-square')
      ->addSigil('has-tooltip')
      ->setHref('/conpherence/edit/')
      ->setWorkflow(true)
      ->setMetaData(array(
        'tip' => \Yii::t("app",'New Room'),
      ));

    $search_icon = (new PHUIIconView())
      ->setIcon('fa-search')
      ->addSigil('has-tooltip')
      ->setHref('/conpherence/search/')
      ->setMetaData(array(
        'tip' => \Yii::t("app",'Search Rooms'),
      ));

    $icons = phutil_tag(
      'span',
      array(
        'class' => 'room-list-icons',
      ),
      array(
        $new_icon,
        $search_icon,
      ));

    $new_icon = (new PHUIIconView())
      ->setIcon('fa-plus-square')
      ->setHref('/conpherence/new/')
      ->setWorkflow(true);

    $custom = phutil_tag_div('grouped', array($rooms, $icons));

    $item = (new PHUIListItemView())
      ->setType(PHUIListItemView::TYPE_CUSTOM)
      ->setName($custom)
      ->addClass('conpherence-room-list-header');
    return $item;
  }

  private function getNoRoomsMenuItem() {
    $message = phutil_tag(
      'div',
      array(
        'class' => 'no-conpherences-menu-item',
      ),
      \Yii::t("app",'No Rooms'));

    return (new PHUIListItemView())
      ->setType(PHUIListItemView::TYPE_CUSTOM)
      ->setName($message);
  }


}

<?php

namespace orangins\modules\conpherence\typeahead;

use orangins\lib\helpers\JavelinHtml;
use orangins\modules\conpherence\application\PhabricatorConpherenceApplication;
use orangins\modules\conpherence\models\ConpherenceThread;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;

final class ConpherenceThreadDatasource
    extends PhabricatorTypeaheadDatasource
{

    public function getBrowseTitle()
    {
        return \Yii::t("app",'Browse Room');
    }

    public function getPlaceholderText()
    {
        return \Yii::t("app",'Type a room title...');
    }

    public function getDatasourceApplicationClass()
    {
        return PhabricatorConpherenceApplication::class;
    }

    public function loadResults()
    {
        $viewer = $this->getViewer();
        $raw_query = $this->getRawQuery();

        /** @var ConpherenceThread[] $rooms */
        $rooms = ConpherenceThread::find()
            ->setViewer($viewer)
            ->withTitleNgrams($raw_query)
            ->needParticipants(true)
            ->execute();

        $results = array();
        foreach ($rooms as $room) {
            if (strlen($room->getTopic())) {
                $topic = $room->getTopic();
            } else {
                $topic = JavelinHtml::phutil_tag('em', array(), \Yii::t("app",'No topic set'));
            }

            $token = (new PhabricatorTypeaheadResult())
                ->setName($room->getTitle())
                ->setPHID($room->getPHID())
                ->addAttribute($topic);

            $results[] = $token;
        }

        return $results;
    }

}

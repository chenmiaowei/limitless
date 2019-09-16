<?php

namespace orangins\modules\conpherence\models;

use orangins\lib\infrastructure\query\PhabricatorQuery;

/**
 * Class ConpherenceParticipantCountQuery
 * @package orangins\modules\conpherence\models
 * @author 陈妙威
 */
final class ConpherenceParticipantCountQuery
    extends PhabricatorQuery
{

    /**
     * @var
     */
    private $participantPHIDs;
    /**
     * @var
     */
    private $unread;

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function withParticipantPHIDs(array $phids)
    {
        $this->participantPHIDs = $phids;
        return $this;
    }

    /**
     * @param $unread
     * @return $this
     * @author 陈妙威
     */
    public function withUnread($unread)
    {
        $this->unread = $unread;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function execute()
    {
        $this->innerJoin("conpherence_thread", "conpherence_thread.phid=conpherence_participant.conpherence_phid");
        $this->buildWhereClause();
        $this->buildGroupByClause();
        $list = $this->all();
        return ipull($list, 'count', 'participantPHID');
    }

    /**
     * @author 陈妙威
     */
    protected function buildWhereClause()
    {
        $where = array();

        if ($this->participantPHIDs !== null) {
            $this->andWhere(['IN', 'conpherence_participant.participant_phid', $this->participantPHIDs]);
        }

        if ($this->unread !== null) {
            if ($this->unread) {
                $this->andWhere('conpherence_participant.seen_message_count < conpherence_thread.message_count');
            } else {
                $this->andWhere('conpherence_participant.seen_message_count >= conpherence_thread.message_count');
            }
        }
    }

    /**
     * @author 陈妙威
     */
    private function buildGroupByClause()
    {
        $this->groupBy('participant_phid');
    }
}

<?php

namespace orangins\modules\conpherence\models;

use orangins\lib\infrastructure\query\PhabricatorQuery;

/**
 * This is the ActiveQuery class for [[ConpherenceParticipant]].
 *
 * @see ConpherenceParticipant
 */
class ConpherenceParticipantQuery extends PhabricatorQuery
{

    /**
     * @var
     */
    private $participantPHIDs;

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
     * @author 陈妙威
     */
    public function execute()
    {
        $this->innerJoin("conpherence_thread", "conpherence_thread.phid=conpherence_participant.conpherence_phid");
        $this->buildWhereClause();
        $this->buildOrderClause();
        return $this->all();
    }

    /**
     * @author 陈妙威
     */
    protected function buildWhereClause()
    {
        if ($this->participantPHIDs !== null) {
            $this->andWhere(['IN', 'participant_phid', $this->participantPHIDs]);
        }
    }

    /**
     * @author 陈妙威
     */
    private function buildOrderClause()
    {
        $this->orderBy("conpherence_thread.updated_at DESC, conpherence_thread.id DESC, conpherence_participant.id DESC");
    }
}

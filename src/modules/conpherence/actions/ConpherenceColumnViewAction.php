<?php

namespace orangins\modules\conpherence\actions;

use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;

final class ConpherenceColumnViewAction extends
    ConpherenceAction
{

    public function run()
    {
        $request = $this->getRequest();
        $user = $request->getViewer();

        $latest_conpherences = array();
        $latest_participant = ConpherenceParticipant::find()
            ->withParticipantPHIDs(array($user->getPHID()))
            ->setLimit(8)
            ->execute();
        if ($latest_participant) {
            $conpherence_phids = mpull($latest_participant, 'getConpherencePHID');
            $latest_conpherences = ConpherenceThread::find()
                ->setViewer($user)
                ->withPHIDs($conpherence_phids)
                ->needProfileImage(true)
                ->execute();
            $latest_conpherences = mpull($latest_conpherences, null, 'getPHID');
            $latest_conpherences = array_select_keys(
                $latest_conpherences,
                $conpherence_phids);
        }

        $conpherence = null;
        $should_404 = false;
        if ($request->getInt('id')) {
            $conpherence = ConpherenceThread::find()
                ->setViewer($user)
                ->withIDs(array($request->getInt('id')))
                ->needProfileImage(true)
                ->needTransactions(true)
                ->setTransactionLimit(ConpherenceThreadQuery::TRANSACTION_LIMIT)
                ->executeOne();
            $should_404 = true;
        } else if ($latest_participant) {
            $participant = head($latest_participant);
            $conpherence = ConpherenceThread::find()
                ->setViewer($user)
                ->withPHIDs(array($participant->getConpherencePHID()))
                ->needProfileImage(true)
                ->needTransactions(true)
                ->setTransactionLimit(ConpherenceThreadQuery::TRANSACTION_LIMIT)
                ->executeOne();
            $should_404 = true;
        }

        $durable_column = (new ConpherenceDurableColumnView())
            ->setUser($user)
            ->setVisible(true);
        if (!$conpherence) {
            if ($should_404) {
                return new Aphront404Response();
            }

            $conpherence_id = null;
            $conpherence_phid = null;
            $latest_transaction_id = null;
            $can_edit = false;

        } else {
            $this->setConpherence($conpherence);

            $participant = $conpherence->getParticipant($user->getPHID());
            $transactions = $conpherence->getTransactions();
            $latest_transaction = head($transactions);
            $write_guard = AphrontWriteGuard::beginScopedUnguardedWrites();
            $participant->markUpToDate($conpherence);
            unset($write_guard);

            $draft = PhabricatorDraft::newFromUserAndKey(
                $user,
                $conpherence->getPHID());

            $durable_column
                ->setDraft($draft)
                ->setSelectedConpherence($conpherence)
                ->setConpherences($latest_conpherences);
            $conpherence_id = $conpherence->getID();
            $conpherence_phid = $conpherence->getPHID();
            $latest_transaction_id = $latest_transaction->getID();
            $can_edit = PhabricatorPolicyFilter::hasCapability(
                $user,
                $conpherence,
                PhabricatorPolicyCapability::CAN_EDIT);
        }

        $dropdown_query = (new AphlictDropdownDataQuery())
            ->setViewer($user);
        $dropdown_query->execute();
        $response = array(
            'content' => hsprintf('%s', $durable_column),
            'threadID' => $conpherence_id,
            'threadPHID' => $conpherence_phid,
            'latestTransactionID' => $latest_transaction_id,
            'canEdit' => $can_edit,
            'aphlictDropdownData' => array(
                $dropdown_query->getNotificationData(),
                $dropdown_query->getConpherenceData(),
            ),
        );

        return (new AphrontAjaxResponse())->setContent($response);
    }

}

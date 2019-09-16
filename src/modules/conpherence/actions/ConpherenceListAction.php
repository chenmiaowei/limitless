<?php

namespace orangins\modules\conpherence\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontAjaxResponse;
use orangins\modules\conpherence\models\ConpherenceParticipant;
use orangins\modules\conpherence\models\ConpherenceParticipantQuery;
use orangins\modules\conpherence\models\ConpherenceThread;
use orangins\modules\conpherence\models\ConpherenceThreadQuery;
use orangins\modules\conpherence\view\ConpherenceLayoutView;
use orangins\modules\conpherence\view\ConpherenceThreadListView;
use orangins\modules\policy\models\PhabricatorPolicyQuery;

/**
 * Class ConpherenceListAction
 * @package orangins\modules\conpherence\actions
 * @author 陈妙威
 */
final class ConpherenceListAction extends ConpherenceAction
{

    /**
     *
     */
    const SELECTED_MODE = 'selected';
    /**
     *
     */
    const UNSELECTED_MODE = 'unselected';

    /**
     * Two main modes of operation...
     *
     * 1 - /conpherence/ - UNSELECTED_MODE
     * 2 - /conpherence/<id>/ - SELECTED_MODE
     *
     * UNSELECTED_MODE is not an Ajax request while the other two are Ajax
     * requests.
     */
    private function determineMode()
    {
        $request = $this->getRequest();

        $mode = self::UNSELECTED_MODE;
        if ($request->isAjax()) {
            $mode = self::SELECTED_MODE;
        }

        return $mode;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return Aphront404Response|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $user = $request->getViewer();
        $title = \Yii::t("app",'Conpherence');
        $conpherence = null;

        $limit = ConpherenceThreadListView::SEE_ALL_LIMIT + 1;
        $all_participation = array();

        $mode = $this->determineMode();
        switch ($mode) {
            case self::SELECTED_MODE:
                $conpherence_id = $request->getURIData('id');
                $conpherence = ConpherenceThread::find()
                    ->setViewer($user)
                    ->withIDs(array($conpherence_id))
                    ->executeOne();
                if (!$conpherence) {
                    return new Aphront404Response();
                }
                if ($conpherence->getTitle()) {
                    $title = $conpherence->getTitle();
                }
                $cursor = $conpherence->getParticipantIfExists($user->getPHID());
                $data = $this->loadDefaultParticipation($limit);
                $all_participation = $data['all_participation'];
                if (!$cursor) {
                    $menu_participation = (new ConpherenceParticipant())
                        ->makeEphemeral()
                        ->setConpherencePHID($conpherence->getPHID())
                        ->setParticipantPHID($user->getPHID());
                } else {
                    $menu_participation = $cursor;
                }

                // check to see if the loaded conpherence is going to show up
                // within the SEE_ALL_LIMIT amount of conpherences.
                // If its not there, then we just pre-pend it as the "first"
                // conpherence so folks have a navigation item in the menu.
                $count = 0;
                $found = false;
                foreach ($all_participation as $phid => $curr_participation) {
                    if ($conpherence->getPHID() == $phid) {
                        $found = true;
                        break;
                    }
                    $count++;
                    if ($count > ConpherenceThreadListView::SEE_ALL_LIMIT) {
                        break;
                    }
                }
                if (!$found) {
                    $all_participation =
                        array($conpherence->getPHID() => $menu_participation) +
                        $all_participation;
                }
                break;
            case self::UNSELECTED_MODE:
            default:
                $data = $this->loadDefaultParticipation($limit);
                $all_participation = $data['all_participation'];
                if ($all_participation) {
                    $conpherence_id = head($all_participation)->getConpherencePHID();
                    $conpherence = ConpherenceThread::find()
                        ->setViewer($user)
                        ->withPHIDs(array($conpherence_id))
                        ->needProfileImage(true)
                        ->executeOne();
                }
                // If $conpherence is null, NUX state will render
                break;
        }

        $threads = $this->loadConpherenceThreadData($all_participation);

        $thread_view = (new ConpherenceThreadListView())
            ->setUser($user)
            ->setBaseURI($this->getApplicationURI())
            ->setThreads($threads);

        switch ($mode) {
            case self::SELECTED_MODE:
                $response = (new AphrontAjaxResponse())->setContent($thread_view);
                break;
            case self::UNSELECTED_MODE:
            default:
                $layout = (new ConpherenceLayoutView())
                    ->setUser($user)
                    ->setBaseURI($this->getApplicationURI())
                    ->setThreadView($thread_view)
                    ->setRole('list');
                if ($conpherence) {
                    $layout->setThread($conpherence);
                } else {
                    // make a dummy conpherence so we can render something
                    $conpherence = ConpherenceThread::initializeNewRoom($user);
                    $conpherence->attachHandles(array());
                    $conpherence->attachTransactions(array());
                    $conpherence->makeEphemeral();
                }
                $policy_objects = PhabricatorPolicy::find()
                    ->setViewer($user)
                    ->setObject($conpherence)
                    ->execute();
                $layout->setHeader($this->buildHeaderPaneContent(
                    $conpherence,
                    $policy_objects));
                $response = $this->newPage()
                    ->setTitle($title)
                    ->appendChild($layout);
                break;
        }

        return $response;

    }

    /**
     * @param $limit
     * @return array
     * @author 陈妙威
     */
    private function loadDefaultParticipation($limit)
    {
        $viewer = $this->getRequest()->getViewer();

        $all_participation = ConpherenceParticipant::find()
            ->withParticipantPHIDs(array($viewer->getPHID()))
            ->setLimit($limit)
            ->execute();
        $all_participation = mpull($all_participation, null, 'getConpherencePHID');

        return array(
            'all_participation' => $all_participation,
        );
    }

    /**
     * @param $participation
     * @return array|\dict
     * @author 陈妙威
     */
    private function loadConpherenceThreadData($participation)
    {
        $user = $this->getRequest()->getViewer();
        $conpherence_phids = array_keys($participation);
        $conpherences = array();
        if ($conpherence_phids) {
            $conpherences = ConpherenceThread::find()
                ->setViewer($user)
                ->withPHIDs($conpherence_phids)
                ->needProfileImage(true)
                ->execute();

            // this will re-sort by participation data
            $conpherences = array_select_keys($conpherences, $conpherence_phids);
        }

        return $conpherences;
    }

}

<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2018/12/5
 * Time: 10:21 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\modules\conpherence\controllers;

use orangins\lib\controllers\PhabricatorController;
use orangins\modules\conpherence\actions\ConpherenceColumnViewAction;
use orangins\modules\conpherence\actions\ConpherenceListAction;
use orangins\modules\conpherence\actions\ConpherenceNotificationPanelAction;
use orangins\modules\conpherence\actions\ConpherenceParticipantAction;
use orangins\modules\conpherence\actions\ConpherenceRoomEditAction;
use orangins\modules\conpherence\actions\ConpherenceRoomListAction;
use orangins\modules\conpherence\actions\ConpherenceRoomPictureAction;
use orangins\modules\conpherence\actions\ConpherenceRoomPreferencesAction;
use orangins\modules\conpherence\actions\ConpherenceThreadSearchAction;
use orangins\modules\conpherence\actions\ConpherenceUpdateAction;

/**
 * Class IndexController
 * @package orangins\modules\notification\controllers
 * @author 陈妙威
 */
class IndexController extends PhabricatorController
{
    /**
     * @return array
     * @author 陈妙威
     */
    public function actions()
    {
        return array(
            'index' => ConpherenceListAction::class,
            'thread' => ConpherenceListAction::class,
            'threadsearch' => ConpherenceThreadSearchAction::class,
            'columnview' => ConpherenceColumnViewAction::class,
            'new' => ConpherenceRoomEditAction::class,
            'edit' => ConpherenceRoomEditAction::class,
            'picture' => ConpherenceRoomPictureAction::class,
            'search' => ConpherenceRoomListAction::class,
            'panel' => ConpherenceNotificationPanelAction::class,
            'participant' => ConpherenceParticipantAction::class,
            'preferences' => ConpherenceRoomPreferencesAction::class,
            'update' => ConpherenceUpdateAction::class,
        );
    }
}
<?php

namespace orangins\modules\phid\handles\pool;

use orangins\lib\helpers\OranginsUtil;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\query\PhabricatorHandleQuery;
use Yii;
use orangins\lib\OranginsObject;
use Exception;

/**
 * Coordinates loading object handles.
 *
 * This is a low-level piece of plumbing which code will not normally interact
 * with directly. For discussion of the handle pool mechanism, see
 * @{class:PhabricatorHandleList}.
 */
final class PhabricatorHandlePool extends OranginsObject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var array
     */
    private $handles = array();
    /**
     * @var array
     */
    private $unloadedPHIDs = array();

    /**
     * @param PhabricatorUser $user
     * @return $this
     * @author 陈妙威
     */
    public function setViewer(PhabricatorUser $user)
    {
        $this->viewer = $user;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param array $phids
     * @return PhabricatorHandleList
     * @author 陈妙威
     */
    public function newHandleList(array $phids)
    {
        // Mark any PHIDs we haven't loaded yet as unloaded. This will let us bulk
        // load them later.
        foreach ($phids as $phid) {
            if (empty($this->handles[$phid])) {
                $this->unloadedPHIDs[$phid] = true;
            }
        }

        $unique = array();
        foreach ($phids as $phid) {
            $unique[$phid] = $phid;
        }

        return (new PhabricatorHandleList())
            ->setHandlePool($this)
            ->setPHIDs(array_values($unique));
    }

    /**
     * @param array $phids
     * @return mixed
     *
     * @throws Exception
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    public function loadPHIDs(array $phids)
    {
        $need = array();
        foreach ($phids as $phid) {
            if (empty($this->handles[$phid])) {
                $need[$phid] = true;
            }
        }

        foreach ($need as $phid => $ignored) {
            if (empty($this->unloadedPHIDs[$phid])) {
                throw new Exception(
                    Yii::t("app",
                        'Attempting to load PHID "%s", but it was not requested by any ' .
                        'handle list.',
                        $phid));
            }
        }

        // If we need any handles, bulk load everything in the queue.
        if ($need) {
            // Clear the list of PHIDs that need to be loaded before performing the
            // actual fetch. This prevents us from looping if we need to reenter the
            // HandlePool while loading handles.
            $fetch_phids = array_keys($this->unloadedPHIDs);
            $this->unloadedPHIDs = array();

            $handles = (new PhabricatorHandleQuery())
                ->setViewer($this->getViewer())
                ->withPHIDs($fetch_phids)
                ->execute();
            $this->handles += $handles;
        }

        return array_select_keys($this->handles, $phids);
    }

}

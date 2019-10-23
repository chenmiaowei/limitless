<?php

namespace orangins\modules\system\engine;

use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\system\interfaces\PhabricatorDestructibleCodexInterface;
use orangins\modules\system\interfaces\PhabricatorDestructibleInterface;
use Exception;
use orangins\modules\system\models\PhabricatorSystemDestructionLog;

/**
 * Class PhabricatorDestructionEngine
 * @package orangins\modules\system\engine
 * @author 陈妙威
 */
final class PhabricatorDestructionEngine extends OranginsObject
{

    /**
     * @var
     */
    private $rootLogID;
    /**
     * @var
     */
    private $collectNotes;
    /**
     * @var array
     */
    private $notes = array();

    /**
     * @param $collect_notes
     * @return $this
     * @author 陈妙威
     */
    public function setCollectNotes($collect_notes)
    {
        $this->collectNotes = $collect_notes;
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getViewer()
    {
        return PhabricatorUser::getOmnipotentUser();
    }

    /**
     * @param PhabricatorDestructibleInterface $object
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function destroyObject(PhabricatorDestructibleInterface $object)
    {
        $log = (new PhabricatorSystemDestructionLog())
            ->setEpoch(time())
            ->setObjectClass(get_class($object));

        if ($this->rootLogID) {
            $log->setRootLogID($this->rootLogID);
        }

        $object_phid = $this->getObjectPHID($object);
        if ($object_phid) {
            $log->setObjectPHID($object_phid);
        }

        if (method_exists($object, 'getMonogram')) {
            try {
                $log->setObjectMonogram($object->getMonogram());
            } catch (Exception $ex) {
                // Ignore.
            }
        }

        $log->save();

        if (!$this->rootLogID) {
            $this->rootLogID = $log->getID();
        }

        if ($this->collectNotes) {
            if ($object instanceof PhabricatorDestructibleCodexInterface) {
                $codex = PhabricatorDestructibleCodex::newFromObject(
                    $object,
                    $this->getViewer());

                foreach ($codex->getDestructionNotes() as $note) {
                    $this->notes[] = $note;
                }
            }
        }

        $object->destroyObjectPermanently($this);

        if ($object_phid) {
            $extensions = PhabricatorDestructionEngineExtension::getAllExtensions();
            foreach ($extensions as $key => $extension) {
                if (!$extension->canDestroyObject($this, $object)) {
                    unset($extensions[$key]);
                    continue;
                }
            }

            foreach ($extensions as $key => $extension) {
                $extension->destroyObject($this, $object);
            }
        }
    }

    /**
     * @param $object
     * @return null
     * @author 陈妙威
     */
    private function getObjectPHID($object)
    {
        if (!is_object($object)) {
            return null;
        }

        if (!method_exists($object, 'getPHID')) {
            return null;
        }

        try {
            return $object->getPHID();
        } catch (Exception $ex) {
            return null;
        }
    }
}

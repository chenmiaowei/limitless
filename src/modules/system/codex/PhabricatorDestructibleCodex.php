<?php

namespace orangins\modules\system\codex;

use Exception;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\system\interfaces\PhabricatorDestructibleCodexInterface;
use orangins\modules\system\interfaces\PhabricatorDestructibleInterface;
use Phobject;

/**
 * Class PhabricatorDestructibleCodex
 * @package orangins\modules\system\codex
 * @author 陈妙威
 */
abstract class PhabricatorDestructibleCodex
    extends Phobject
{

    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $object;

    /**
     * @return array
     * @author 陈妙威
     */
    public function getDestructionNotes()
    {
        return array();
    }

    /**
     * @param PhabricatorUser $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer(PhabricatorUser $viewer)
    {
        $this->viewer = $viewer;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getViewer()
    {
        return $this->viewer;
    }

    /**
     * @param PhabricatorDestructibleCodexInterface $object
     * @return $this
     * @author 陈妙威
     */
    final public function setObject(
        PhabricatorDestructibleCodexInterface $object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getObject()
    {
        return $this->object;
    }

    /**
     * @param PhabricatorDestructibleCodexInterface $object
     * @param PhabricatorUser $viewer
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public static function newFromObject(
        PhabricatorDestructibleCodexInterface $object,
        PhabricatorUser $viewer)
    {

        if (!($object instanceof PhabricatorDestructibleInterface)) {
            throw new Exception(
                pht(
                    'Object (of class "%s") implements interface "%s", but must also ' .
                    'implement interface "%s".',
                    get_class($object),
                    'PhabricatorDestructibleCodexInterface',
                    'PhabricatorDestructibleInterface'));
        }

        $codex = $object->newDestructibleCodex();
        if (!($codex instanceof PhabricatorDestructibleCodex)) {
            throw new Exception(
                pht(
                    'Object (of class "%s") implements interface "%s", but defines ' .
                    'method "%s" incorrectly: this method must return an object of ' .
                    'class "%s".',
                    get_class($object),
                    'PhabricatorDestructibleCodexInterface',
                    'newDestructibleCodex()',
                    __CLASS__));
        }

        $codex
            ->setObject($object)
            ->setViewer($viewer);

        return $codex;
    }

}

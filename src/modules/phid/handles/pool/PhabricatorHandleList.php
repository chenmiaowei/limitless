<?php

namespace orangins\modules\phid\handles\pool;

use ArrayAccess;
use Countable;
use Iterator;
use orangins\modules\phid\view\PHUIHandleListView;
use orangins\modules\phid\view\PHUIHandleView;
use Yii;
use orangins\lib\OranginsObject;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * A list of object handles.
 *
 * This is a convenience class which behaves like an array but makes working
 * with handles more convenient, improves their caching and batching semantics,
 * and provides some utility behavior.
 *
 * Load a handle list by calling `loadHandles()` on a `$viewer`:
 *
 *   $handles = $viewer->loadHandles($phids);
 *
 * This creates a handle list object, which behaves like an array of handles.
 * However, it benefits from the viewer's internal handle cache and performs
 * just-in-time bulk loading.
 */
final class PhabricatorHandleList
    extends OranginsObject
    implements
    Iterator,
    ArrayAccess,
    Countable
{

    /**
     * @var PhabricatorHandlePool
     */
    private $handlePool;
    /**
     * @var
     */
    private $phids;
    /**
     * @var
     */
    private $count;
    /**
     * @var
     */
    private $handles;
    /**
     * @var
     */
    private $cursor;
    /**
     * @var
     */
    private $map;

    /**
     * @param PhabricatorHandlePool $pool
     * @return $this
     * @author 陈妙威
     */
    public function setHandlePool(PhabricatorHandlePool $pool)
    {
        $this->handlePool = $pool;
        return $this;
    }

    /**
     * @param array $phids
     * @return $this
     * @author 陈妙威
     */
    public function setPHIDs(array $phids)
    {
        $this->phids = $phids;
        $this->count = count($phids);
        return $this;
    }

    /**
     * @throws Exception
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     * @author 陈妙威
     */
    private function loadHandles()
    {
        $this->handles = $this->handlePool->loadPHIDs($this->phids);
    }

    /**
     * @param $phid
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     */
    private function getHandle($phid)
    {
        if ($this->handles === null) {
            $this->loadHandles();
        }

        if (empty($this->handles[$phid])) {
            throw new Exception(
                Yii::t("app",
                    'Requested handle "{0}" was not loaded.',
                    $phid));
        }

        return $this->handles[$phid];
    }


    /**
     * Get a handle from this list if it exists.
     *
     * This has similar semantics to @{function: ArrayHelper::getValue(}.
     * @param $phid
     * @param null $default
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     * @throws \PhutilInvalidStateException
     */
    public function getHandleIfExists($phid, $default = null)
    {
        if ($this->handles === null) {
            $this->loadHandles();
        }

        return  ArrayHelper::getValue($this->handles, $phid, $default);
    }


    /**
     * Create a new list with a subset of the PHIDs in this list.
     * @param array $phids
     * @return PhabricatorHandleList
     * @throws Exception
     */
    public function newSublist(array $phids)
    {
        foreach ($phids as $phid) {
            if (!isset($this[$phid])) {
                throw new Exception(
                    Yii::t("app",
                        'Trying to create a new sublist of an existing handle list, ' .
                        'but PHID "%s" does not appear in the parent list.',
                        $phid));
            }
        }

        return $this->handlePool->newHandleList($phids);
    }


    /* -(  Rendering  )---------------------------------------------------------- */


    /**
     * Return a @{class:PHUIHandleListView} which can render the handles in
     * this list.
     */
    public function renderList()
    {
        return (new PHUIHandleListView())
            ->setHandleList($this);
    }


    /**
     * Return a @{class:PHUIHandleView} which can render a specific handle.
     * @param $phid
     * @return PHUIHandleView
     * @throws Exception
     */
    public function renderHandle($phid)
    {
        if (!isset($this[$phid])) {
            throw new Exception(
                Yii::t("app", 'Trying to render a handle which does not exist!'));
        }

        return (new PHUIHandleView())
            ->setHandleList($this)
            ->setHandlePHID($phid);
    }

    /* -(  Iterator  )----------------------------------------------------------- */


    /**
     * @author 陈妙威
     */
    public function rewind()
    {
        $this->cursor = 0;
    }

    /**
     * @return mixed
     * @author 陈妙威
     * @throws Exception
     * @throws \ReflectionException
     */
    public function current()
    {
        return $this->getHandle($this->phids[$this->cursor]);
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function key()
    {
        return $this->phids[$this->cursor];
    }

    /**
     * @author 陈妙威
     */
    public function next()
    {
        ++$this->cursor;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function valid()
    {
        return ($this->cursor < $this->count);
    }


    /* -(  ArrayAccess  )-------------------------------------------------------- */


    /**
     * @param mixed $offset
     * @return bool
     * @author 陈妙威
     */
    public function offsetExists($offset)
    {
        // NOTE: We're intentionally not loading handles here so that isset()
        // checks do not trigger fetches. This gives us better bulk loading
        // behavior, particularly when invoked through methods like renderHandle().

        if ($this->map === null) {
            $this->map = array_fill_keys($this->phids, true);
        }

        return isset($this->map[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @throws Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function offsetGet($offset)
    {
        if ($this->handles === null) {
            $this->loadHandles();
        }
        return $this->handles[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @author 陈妙威
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        $this->raiseImmutableException();
    }

    /**
     * @param mixed $offset
     * @author 陈妙威
     * @throws Exception
     */
    public function offsetUnset($offset)
    {
        $this->raiseImmutableException();
    }

    /**
     * @author 陈妙威
     * @throws Exception
     */
    private function raiseImmutableException()
    {
        throw new Exception(
            Yii::t("app",
                'Trying to mutate a %s, but this is not permitted; ' .
                'handle lists are immutable.',
                __CLASS__));
    }


    /* -(  Countable  )---------------------------------------------------------- */


    /**
     * @return int
     * @author 陈妙威
     */
    public function count()
    {
        return $this->count;
    }

}

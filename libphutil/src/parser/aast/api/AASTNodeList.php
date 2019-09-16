<?php

/**
 * Class AASTNodeList
 * @author 陈妙威
 */
final class AASTNodeList
    extends Phobject
    implements Countable, Iterator
{

    /**
     * @var
     */
    protected $list;
    /**
     * @var
     */
    protected $tree;
    /**
     * @var
     */
    protected $ids;
    /**
     * @var
     */
    protected $pos;

    /**
     * AASTNodeList constructor.
     */
    protected function __construct()
    {
    }

    /**
     * @param array $nodes
     * @return AASTNodeList
     * @author 陈妙威
     */
    protected function newList(array $nodes)
    {
        return self::newFromTreeAndNodes($this->tree, $nodes);
    }

    /**
     * @param $type_name
     * @return AASTNodeList
     * @author 陈妙威
     */
    public function selectDescendantsOfType($type_name)
    {
        return $this->selectDescendantsOfTypes(array($type_name));
    }

    /**
     * @param array $type_names
     * @return AASTNodeList
     * @author 陈妙威
     */
    public function selectDescendantsOfTypes(array $type_names)
    {
        $results = array();
        foreach ($type_names as $type_name) {
            foreach ($this->list as $id => $node) {
                $results += $node->selectDescendantsOfType($type_name)->getRawNodes();
            }
        }
        return $this->newList($results);
    }

    /**
     * @param $index
     * @return AASTNodeList
     * @author 陈妙威
     */
    public function getChildrenByIndex($index)
    {
        $results = array();
        foreach ($this->list as $id => $node) {
            $child = $node->getChildByIndex($index);
            $results[$child->getID()] = $child;
        }
        return $this->newList($results);
    }

    /**
     * @param AASTNodeList $list
     * @return $this
     * @author 陈妙威
     */
    public function add(AASTNodeList $list)
    {
        foreach ($list->list as $id => $node) {
            $this->list[$id] = $node;
        }
        $this->ids = array_keys($this->list);
        return $this;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTokens()
    {
        $tokens = array();
        foreach ($this->list as $node) {
            $tokens += $node->getTokens();
        }
        return $tokens;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRawNodes()
    {
        return $this->list;
    }

    /**
     * @param AASTTree $tree
     * @param array $nodes
     * @return AASTNodeList
     * @author 陈妙威
     */
    public static function newFromTreeAndNodes(AASTTree $tree, array $nodes)
    {
        // We could do `assert_instances_of($nodes, 'AASTNode')` here, but doing
        // so imposes an observable performance penalty for linting.

        $obj = new AASTNodeList();
        $obj->tree = $tree;
        $obj->list = $nodes;
        $obj->ids = array_keys($nodes);
        return $obj;
    }

    /**
     * @param AASTTree $tree
     * @return AASTNodeList
     * @author 陈妙威
     */
    public static function newFromTree(AASTTree $tree)
    {
        $obj = new AASTNodeList();
        $obj->tree = $tree;
        $obj->list = array(0 => $tree->getRootNode());
        $obj->ids = array(0 => 0);
        return $obj;
    }


    /* -(  Countable  )---------------------------------------------------------- */

    /**
     * @return int
     * @author 陈妙威
     */
    public function count()
    {
        return count($this->ids);
    }


    /* -(  Iterator  )----------------------------------------------------------- */

    /**
     * @return mixed|void
     * @author 陈妙威
     */
    public function current()
    {
        return $this->list[$this->key()];
    }

    /**
     * @return mixed|void
     * @author 陈妙威
     */
    public function key()
    {
        return $this->ids[$this->pos];
    }

    /**
     * @author 陈妙威
     */
    public function next()
    {
        $this->pos++;
    }

    /**
     * @author 陈妙威
     */
    public function rewind()
    {
        $this->pos = 0;
    }

    /**
     * @return bool|void
     * @author 陈妙威
     */
    public function valid()
    {
        return $this->pos < count($this->ids);
    }

}

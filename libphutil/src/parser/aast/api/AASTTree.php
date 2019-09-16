<?php

/**
 * An abstract abstract syntax tree.
 */
abstract class AASTTree extends Phobject
{

    /**
     * @var array
     */
    protected $tree = array();
    /**
     * @var array
     */
    protected $stream = array();
    /**
     * @var
     */
    protected $lineMap;
    /**
     * @var
     */
    protected $rawSource;

    /**
     * @var string
     */
    private $treeType = 'Abstract';
    /**
     * @var
     */
    private $tokenConstants;
    /**
     * @var
     */
    private $tokenReverseMap;
    /**
     * @var
     */
    private $nodeConstants;
    /**
     * @var
     */
    private $nodeReverseMap;

    /**
     * @param $id
     * @param array $data
     * @param AASTTree $tree
     * @return mixed
     * @author 陈妙威
     */
    abstract public function newNode($id, array $data, AASTTree $tree);

    /**
     * @param $id
     * @param $type
     * @param $value
     * @param $offset
     * @param AASTTree $tree
     * @return mixed
     * @author 陈妙威
     */
    abstract public function newToken(
        $id,
        $type,
        $value,
        $offset,
        AASTTree $tree);

    /**
     * AASTTree constructor.
     * @param array $tree
     * @param array $stream
     * @param $source
     */
    public function __construct(array $tree, array $stream, $source)
    {
        $ii = 0;
        $offset = 0;

        foreach ($stream as $token) {
            $this->stream[$ii] = $this->newToken(
                $ii,
                $token[0],
                substr($source, $offset, $token[1]),
                $offset,
                $this);
            $offset += $token[1];
            ++$ii;
        }

        $this->rawSource = $source;
        $this->buildTree(array($tree));
    }

    /**
     * @param $description
     * @return $this
     * @author 陈妙威
     */
    final public function setTreeType($description)
    {
        $this->treeType = $description;
        return $this;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    final public function getTreeType()
    {
        return $this->treeType;
    }

    /**
     * @param array $token_map
     * @return $this
     * @author 陈妙威
     */
    final public function setTokenConstants(array $token_map)
    {
        $this->tokenConstants = $token_map;
        $this->tokenReverseMap = array_flip($token_map);
        return $this;
    }

    /**
     * @param array $node_map
     * @return $this
     * @author 陈妙威
     */
    final public function setNodeConstants(array $node_map)
    {
        $this->nodeConstants = $node_map;
        $this->nodeReverseMap = array_flip($node_map);
        return $this;
    }

    /**
     * @param $type_id
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public function getNodeTypeNameFromTypeID($type_id)
    {
        if (empty($this->nodeConstants[$type_id])) {
            $tree_type = $this->getTreeType();
            throw new Exception(
                pht(
                    "No type name for node type ID '%s' in '%s' AAST.",
                    $type_id,
                    $tree_type));
        }

        return $this->nodeConstants[$type_id];
    }

    /**
     * @param $type_name
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public function getNodeTypeIDFromTypeName($type_name)
    {
        if (empty($this->nodeReverseMap[$type_name])) {
            $tree_type = $this->getTreeType();
            throw new Exception(
                pht(
                    "No type ID for node type name '%s' in '%s' AAST.",
                    $type_name,
                    $tree_type));
        }
        return $this->nodeReverseMap[$type_name];
    }

    /**
     * @param $type_id
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public function getTokenTypeNameFromTypeID($type_id)
    {
        if (empty($this->tokenConstants[$type_id])) {
            $tree_type = $this->getTreeType();
            throw new Exception(
                pht(
                    "No type name for token type ID '%s' in '%s' AAST.",
                    $type_id,
                    $tree_type));
        }
        return $this->tokenConstants[$type_id];
    }

    /**
     * @param $type_name
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public function getTokenTypeIDFromTypeName($type_name)
    {
        if (empty($this->tokenReverseMap[$type_name])) {
            $tree_type = $this->getTreeType();
            throw new Exception(
                pht(
                    "No type ID for token type name '%s' in '%s' AAST.",
                    $type_name,
                    $tree_type));
        }
        return $this->tokenReverseMap[$type_name];
    }

    /**
     * Unlink internal datastructures so that PHP will garbage collect the tree.
     *
     * This renders the object useless.
     *
     * @return void
     */
    public function dispose()
    {
        $this->getRootNode()->dispose();
        unset($this->tree);
        unset($this->stream);
    }

    /**
     * @return AASTNode
     * @author 陈妙威
     */
    final public function getRootNode()
    {
        return $this->tree[0];
    }

    /**
     * @param array $tree
     * @return array
     * @author 陈妙威
     */
    protected function buildTree(array $tree)
    {
        $ii = count($this->tree);
        $nodes = array();
        foreach ($tree as $node) {
            $this->tree[$ii] = $this->newNode($ii, $node, $this);
            $nodes[$ii] = $node;
            ++$ii;
        }
        foreach ($nodes as $node_id => $node) {
            if (isset($node[3])) {
                $children = $this->buildTree($node[3]);
                $previous_child = null;

                foreach ($children as $ii => $child) {
                    $child->setParentNode($this->tree[$node_id]);
                    $child->setPreviousSibling($previous_child);

                    if ($previous_child) {
                        $previous_child->setNextSibling($child);
                    }

                    $previous_child = $child;
                }

                if ($previous_child) {
                    $previous_child->setNextSibling($child);
                }

                $this->tree[$node_id]->setChildren($children);
            }
        }

        $result = array();
        foreach ($nodes as $key => $node) {
            $result[$key] = $this->tree[$key];
        }

        return $result;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final public function getRawTokenStream()
    {
        return $this->stream;
    }

    /**
     * @return array|null
     * @author 陈妙威
     */
    public function getOffsetToLineNumberMap()
    {
        if ($this->lineMap === null) {
            $src = $this->rawSource;
            $len = strlen($src);
            $lno = 1;
            $map = array();
            for ($ii = 0; $ii < $len; ++$ii) {
                $map[$ii] = $lno;
                if ($src[$ii] == "\n") {
                    ++$lno;
                }
            }
            $this->lineMap = $map;
        }
        return $this->lineMap;
    }

}

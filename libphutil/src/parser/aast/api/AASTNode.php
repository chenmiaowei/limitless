<?php

/**
 * Class AASTNode
 * @author 陈妙威
 */
abstract class AASTNode extends Phobject
{

    /**
     * @var
     */
    private $id;
    /**
     * @var int
     */
    protected $l;
    /**
     * @var int
     */
    protected $r;
    /**
     * @var mixed
     */
    private $typeID;
    /**
     * @var
     */
    private $typeName;
    /**
     * @var AASTTree
     */
    protected $tree;

    /**
     * @var array
     */
    private $children = array();
    /**
     * @var null
     */
    private $parentNode = null;
    /**
     * @var null
     */
    private $previousSibling = null;
    /**
     * @var null
     */
    private $nextSibling = null;

    /**
     * @var
     */
    private $selectCache;
    /**
     * @var
     */
    private $tokenCache;

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function isStaticScalar();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getDocblockToken();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function evalStatic();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getStringLiteralValue();

    /**
     * AASTNode constructor.
     * @param $id
     * @param array $data
     * @param AASTTree $tree
     */
    public function __construct($id, array $data, AASTTree $tree)
    {
        $this->id = $id;
        $this->typeID = $data[0];
        if (isset($data[1])) {
            $this->l = $data[1];
        } else {
            $this->l = -1;
        }
        if (isset($data[2])) {
            $this->r = $data[2];
        } else {
            $this->r = -1;
        }
        $this->tree = $tree;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    final public function getParentNode()
    {
        return $this->parentNode;
    }

    /**
     * @param AASTNode|null $node
     * @return $this
     * @author 陈妙威
     */
    final public function setParentNode(AASTNode $node = null)
    {
        $this->parentNode = $node;
        return $this;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    final public function getPreviousSibling()
    {
        return $this->previousSibling;
    }

    /**
     * @param AASTNode|null $node
     * @return $this
     * @author 陈妙威
     */
    final public function setPreviousSibling(AASTNode $node = null)
    {
        $this->previousSibling = $node;
        return $this;
    }

    /**
     * @return null
     * @author 陈妙威
     */
    final public function getNextSibling()
    {
        return $this->nextSibling;
    }

    /**
     * @param AASTNode|null $node
     * @return $this
     * @author 陈妙威
     */
    final public function setNextSibling(AASTNode $node = null)
    {
        $this->nextSibling = $node;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getID()
    {
        return $this->id;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getTypeID()
    {
        return $this->typeID;
    }

    /**
     * @return AASTTree
     * @author 陈妙威
     */
    final public function getTree()
    {
        return $this->tree;
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final public function getTypeName()
    {
        if (empty($this->typeName)) {
            $this->typeName =
                $this->tree->getNodeTypeNameFromTypeID($this->getTypeID());
        }
        return $this->typeName;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    final public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param array $children
     * @return $this
     * @author 陈妙威
     */
    final public function setChildren(array $children)
    {
        // We don't call `assert_instances_of($children, 'AASTNode')` because doing
        // so would incur a significant performance penalty.
        $this->children = $children;
        return $this;
    }

    /**
     * @param $type
     * @return array
     * @author 陈妙威
     */
    public function getChildrenOfType($type)
    {
        $nodes = array();

        foreach ($this->children as $child) {
            if ($child->getTypeName() == $type) {
                $nodes[] = $child;
            }
        }

        return $nodes;
    }

    /**
     * @param $index
     * @param $type
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getChildOfType($index, $type)
    {
        $child = $this->getChildByIndex($index);
        if ($child->getTypeName() != $type) {
            throw new Exception(
                pht(
                    "Child in position '%d' is not of type '%s': %s",
                    $index,
                    $type,
                    $this->getDescription()));
        }

        return $child;
    }

    /**
     * @param $index
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getChildByIndex($index)
    {
        // NOTE: Microoptimization to avoid calls like array_values() or idx().

        $idx = 0;
        foreach ($this->children as $child) {
            if ($idx == $index) {
                return $child;
            }
            ++$idx;
        }

        throw new Exception(pht("No child with index '%d'.", $index));
    }

    /**
     * Build a cache to improve the performance of
     * @{method:selectDescendantsOfType}. This cache makes a time/memory tradeoff
     * by aggressively caching node descendants. It may improve the tree's query
     * performance substantially if you make a large number of queries, but also
     * requires a significant amount of memory.
     *
     * This builds a cache for the entire tree and improves performance of all
     * @{method:selectDescendantsOfType} calls.
     */
    public function buildSelectCache()
    {
        $cache = array();
        foreach ($this->getChildren() as $id => $child) {
            $type_id = $child->getTypeID();
            if (empty($cache[$type_id])) {
                $cache[$type_id] = array();
            }
            $cache[$type_id][$id] = $child;
            foreach ($child->buildSelectCache() as $type_id => $nodes) {
                if (empty($cache[$type_id])) {
                    $cache[$type_id] = array();
                }
                $cache[$type_id] += $nodes;
            }
        }
        $this->selectCache = $cache;
        return $this->selectCache;
    }

    /**
     * Build a cache to improve the performance of @{method:selectTokensOfType}.
     * This cache makes a time/memory tradeoff by aggressively caching token
     * types. It may improve the tree's query performance substantially if you
     * make a large number of queries, but also requires a significant amount of
     * memory.
     *
     * This builds a cache for this node only.
     */
    public function buildTokenCache()
    {
        $cache = array();
        foreach ($this->getTokens() as $id => $token) {
            $cache[$token->getTypeName()][$id] = $token;
        }
        $this->tokenCache = $cache;
        return $this->tokenCache;
    }

    /**
     * @param $type_name
     * @return array
     * @author 陈妙威
     */
    public function selectTokensOfType($type_name)
    {
        return $this->selectTokensOfTypes(array($type_name));
    }

    /**
     * Select all tokens of any given types.
     */
    public function selectTokensOfTypes(array $type_names)
    {
        $tokens = array();

        foreach ($type_names as $type_name) {
            if (isset($this->tokenCache)) {
                $cached_tokens = idx($this->tokenCache, $type_name, array());
                foreach ($cached_tokens as $id => $cached_token) {
                    $tokens[$id] = $cached_token;
                }
            } else {
                foreach ($this->getTokens() as $id => $token) {
                    if ($token->getTypeName() == $type_name) {
                        $tokens[$id] = $token;
                    }
                }
            }
        }

        return $tokens;
    }

    /**
     * @param AASTNode $node
     * @return bool
     * @author 陈妙威
     */
    final public function isDescendantOf(AASTNode $node)
    {
        for ($it = $this; $it !== null; $it = $it->getParentNode()) {
            if ($it === $node) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $type_name
     * @return AASTNode[]
     * @author 陈妙威
     */
    public function selectDescendantsOfType($type_name)
    {
        return $this->selectDescendantsOfTypes(array($type_name));
    }

    /**
     * @param array $type_names
     * @return AASTNode[]
     * @author 陈妙威
     */
    public function selectDescendantsOfTypes(array $type_names)
    {
        $nodes = array();
        foreach ($type_names as $type_name) {
            $type = $this->getTypeIDFromTypeName($type_name);

            if (isset($this->selectCache)) {
                if (isset($this->selectCache[$type])) {
                    $nodes = $nodes + $this->selectCache[$type];
                }
            } else {
                $nodes = $nodes + $this->executeSelectDescendantsOfType($this, $type);
            }
        }

        return AASTNodeList::newFromTreeAndNodes($this->tree, $nodes);
    }

    /**
     * @param $node
     * @param $type
     * @return array
     * @author 陈妙威
     */
    protected function executeSelectDescendantsOfType($node, $type)
    {
        $results = array();
        foreach ($node->getChildren() as $id => $child) {
            if ($child->getTypeID() == $type) {
                $results[$id] = $child;
            }
            $results += $this->executeSelectDescendantsOfType($child, $type);
        }
        return $results;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getTokens()
    {
        if ($this->l == -1 || $this->r == -1) {
            return array();
        }
        $tokens = $this->tree->getRawTokenStream();
        $result = array();
        foreach (range($this->l, $this->r) as $token_id) {
            $result[$token_id] = $tokens[$token_id];
        }
        return $result;
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getConcreteString()
    {
        $values = array();
        foreach ($this->getTokens() as $token) {
            $values[] = $token->getValue();
        }
        return implode('', $values);
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getSemanticString()
    {
        $tokens = $this->getTokens();
        foreach ($tokens as $id => $token) {
            if ($token->isComment()) {
                unset($tokens[$id]);
            }
        }
        return implode('', mpull($tokens, 'getValue'));
    }

    /**
     * @return null|string|string[]
     * @author 陈妙威
     */
    public function getIndentation()
    {
        $tokens = $this->getTokens();
        $left = head($tokens);

        while ($left &&
            (!$left->isAnyWhitespace() ||
                strpos($left->getValue(), "\n") === false)) {
            $left = $left->getPrevToken();
        }

        if (!$left) {
            return null;
        }

        return preg_replace("/^.*\n/s", '', $left->getValue());
    }

    /**
     * @return string
     * @throws Exception
     * @author 陈妙威
     */
    public function getDescription()
    {
        $concrete = $this->getConcreteString();
        if (strlen($concrete) > 75) {
            $concrete = substr($concrete, 0, 36) . '...' . substr($concrete, -36);
        }

        $concrete = addcslashes($concrete, "\\\n\"");

        return pht('a node of type %s: "%s"', $this->getTypeName(), $concrete);
    }

    /**
     * @param $type_name
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    final protected function getTypeIDFromTypeName($type_name)
    {
        return $this->tree->getNodeTypeIDFromTypeName($type_name);
    }

    /**
     * @return null
     * @author 陈妙威
     */
    final public function getOffset()
    {
        $stream = $this->tree->getRawTokenStream();
        if (empty($stream[$this->l])) {
            return null;
        }
        return $stream[$this->l]->getOffset();
    }

    /**
     * @return null
     * @author 陈妙威
     */
    final public function getLength()
    {
        $stream = $this->tree->getRawTokenStream();
        if (empty($stream[$this->r])) {
            return null;
        }
        return $stream[$this->r]->getOffset() - $this->getOffset();
    }


    /**
     * @return array
     * @author 陈妙威
     */
    public function getSurroundingNonsemanticTokens()
    {
        $before = array();
        $after = array();

        $tokens = $this->tree->getRawTokenStream();

        if ($this->l != -1) {
            $before = $tokens[$this->l]->getNonsemanticTokensBefore();
        }

        if ($this->r != -1) {
            $after = $tokens[$this->r]->getNonsemanticTokensAfter();
        }

        return array($before, $after);
    }

    /**
     * @return wild
     * @author 陈妙威
     */
    final public function getLineNumber()
    {
        return idx($this->tree->getOffsetToLineNumberMap(), $this->getOffset());
    }

    /**
     * @return wild
     * @author 陈妙威
     */
    final public function getEndLineNumber()
    {
        return idx(
            $this->tree->getOffsetToLineNumberMap(),
            $this->getOffset() + $this->getLength());
    }

    /**
     * Determines whether the current node appears //after// a specified node in
     * the tree.
     *
     * @param  AASTNode
     * @return bool
     */
    final public function isAfter(AASTNode $node)
    {
        return head($this->getTokens())->getOffset() >
            last($node->getTokens())->getOffset();
    }

    /**
     * Determines whether the current node appears //before// a specified node in
     * the tree.
     *
     * @param  AASTNode
     * @return bool
     */
    final public function isBefore(AASTNode $node)
    {
        return last($this->getTokens())->getOffset() <
            head($node->getTokens())->getOffset();
    }

    /**
     * Determines whether a specified node is a descendant of the current node.
     *
     * @param  AASTNode
     * @return bool
     */
    final public function containsDescendant(AASTNode $node)
    {
        return !$this->isAfter($node) && !$this->isBefore($node);
    }

    /**
     * @author 陈妙威
     */
    public function dispose()
    {
        foreach ($this->getChildren() as $child) {
            $child->dispose();
        }

        unset($this->selectCache);
    }

}

<?php

/**
 * Example implementation and test case for @{class:PhutilBufferedIterator}.
 */
final class PhutilExampleBufferedIterator extends PhutilBufferedIterator
{

    /**
     * @var
     */
    private $cursor;
    /**
     * @var
     */
    private $data;

    /**
     * @author 陈妙威
     */
    protected function didRewind()
    {
        $this->cursor = 0;
    }

    /**
     * @return array|list
     * @author 陈妙威
     */
    protected function loadPage()
    {
        $result = $this->query($this->cursor, $this->getPageSize());
        $this->cursor += count($result);
        return $result;
    }

    /**
     * @param array $data
     * @author 陈妙威
     */
    public function setExampleData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param $cursor
     * @param $limit
     * @return array
     * @author 陈妙威
     */
    private function query($cursor, $limit)
    {
        // NOTE: Normally you'd load or generate results from some external source
        // here. Since this is an example, we just use a premade dataset.

        return array_slice($this->data, $cursor, $limit);
    }

}

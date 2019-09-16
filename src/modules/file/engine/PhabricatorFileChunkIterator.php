<?php

namespace orangins\modules\file\engine;

use orangins\lib\OranginsObject;
use Iterator;
use orangins\modules\file\models\PhabricatorFileChunk;

/**
 * Class PhabricatorFileChunkIterator
 * @package orangins\modules\file\engine
 * @author 陈妙威
 */
final class PhabricatorFileChunkIterator
    extends OranginsObject
    implements Iterator
{

    /**
     * @var PhabricatorFileChunk[]
     */
    private $chunks;
    /**
     * @var
     */
    private $cursor;
    /**
     * @var null
     */
    private $begin;
    /**
     * @var null
     */
    private $end;
    /**
     * @var
     */
    private $data;

    /**
     * PhabricatorFileChunkIterator constructor.
     * @param array $chunks
     * @param null $begin
     * @param null $end
     */
    public function __construct(array $chunks, $begin = null, $end = null)
    {
        /** @var PhabricatorFileChunk[] $chunks */
        $chunks = msort($chunks, 'getByteStart');
        $this->chunks = $chunks;

        if ($begin !== null) {
            foreach ($chunks as $key => $chunk) {
                if ($chunk->getByteEnd() >= $begin) {
                    unset($chunks[$key]);
                }
                break;
            }
            $this->begin = $begin;
        }

        if ($end !== null) {
            foreach ($chunks as $key => $chunk) {
                if ($chunk->getByteStart() <= $end) {
                    unset($chunks[$key]);
                }
            }
            $this->end = $end;
        }
    }

    /**
     * @return bool|mixed|string
     * @throws \ReflectionException
     * @throws \orangins\lib\db\PhabricatorDataNotAttachedException
     * @author 陈妙威
     */
    public function current()
    {
        /** @var PhabricatorFileChunk $chunk */
        $chunk = head($this->chunks);
        $data = $chunk->getDataFile()->loadFileData();

        if ($this->end !== null) {
            if ($chunk->getByteEnd() > $this->end) {
                $data = substr($data, 0, ($this->end - $chunk->getByteStart()));
            }
        }

        if ($this->begin !== null) {
            if ($chunk->getByteStart() < $this->begin) {
                $data = substr($data, ($this->begin - $chunk->getByteStart()));
            }
        }

        return $data;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function key()
    {
        return head_key($this->chunks);
    }

    /**
     * @author 陈妙威
     */
    public function next()
    {
        unset($this->chunks[$this->key()]);
    }

    /**
     * @author 陈妙威
     */
    public function rewind()
    {
        return;
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public function valid()
    {
        return (count($this->chunks) > 0);
    }

}

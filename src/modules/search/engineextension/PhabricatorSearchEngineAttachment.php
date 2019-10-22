<?php

namespace orangins\modules\search\engineextension;

use orangins\lib\OranginsObject;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;

/**
 * Class PhabricatorSearchEngineAttachment
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
abstract class PhabricatorSearchEngineAttachment extends OranginsObject
{

    /**
     * @var
     */
    private $attachmentKey;
    /**
     * @var
     */
    private $viewer;
    /**
     * @var
     */
    private $searchEngine;

    /**
     * @param $viewer
     * @return $this
     * @author 陈妙威
     */
    final public function setViewer($viewer)
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
     * @param PhabricatorApplicationSearchEngine $engine
     * @return $this
     * @author 陈妙威
     */
    final public function setSearchEngine(
        PhabricatorApplicationSearchEngine $engine)
    {
        $this->searchEngine = $engine;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    final public function getSearchEngine()
    {
        return $this->searchEngine;
    }

    /**
     * @param $attachment_key
     * @return $this
     * @author 陈妙威
     */
    public function setAttachmentKey($attachment_key)
    {
        $this->attachmentKey = $attachment_key;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getAttachmentKey()
    {
        return $this->attachmentKey;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getAttachmentName();

    /**
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getAttachmentDescription();

    /**
     * @param $query
     * @param $spec
     * @author 陈妙威
     */
    public function willLoadAttachmentData($query, $spec)
    {
        return;
    }

    /**
     * @param array $objects
     * @param $spec
     * @return null
     * @author 陈妙威
     */
    public function loadAttachmentData(array $objects, $spec)
    {
        return null;
    }

    /**
     * @param $object
     * @param $data
     * @param $spec
     * @return mixed
     * @author 陈妙威
     */
    abstract public function getAttachmentForObject($object, $data, $spec);

}

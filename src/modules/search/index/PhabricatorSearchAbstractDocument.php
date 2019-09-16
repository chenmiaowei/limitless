<?php

namespace orangins\modules\search\index;

use orangins\lib\OranginsObject;
use orangins\modules\search\constants\PhabricatorSearchDocumentFieldType;

/**
 * Class PhabricatorSearchAbstractDocument
 * @package orangins\modules\search\index
 * @author 陈妙威
 */
final class PhabricatorSearchAbstractDocument extends OranginsObject
{

    /**
     * @var
     */
    private $phid;
    /**
     * @var
     */
    private $documentType;
    /**
     * @var
     */
    private $documentTitle;
    /**
     * @var
     */
    private $documentCreated;
    /**
     * @var
     */
    private $documentModified;
    /**
     * @var array
     */
    private $fields = array();
    /**
     * @var array
     */
    private $relationships = array();

    /**
     * @param $phid
     * @return $this
     * @author 陈妙威
     */
    public function setPHID($phid)
    {
        $this->phid = $phid;
        return $this;
    }

    /**
     * @param $document_type
     * @return $this
     * @author 陈妙威
     */
    public function setDocumentType($document_type)
    {
        $this->documentType = $document_type;
        return $this;
    }

    /**
     * @param $title
     * @return $this
     * @author 陈妙威
     */
    public function setDocumentTitle($title)
    {
        $this->documentTitle = $title;
        $this->addField(PhabricatorSearchDocumentFieldType::FIELD_TITLE, $title);
        return $this;
    }

    /**
     * @param $field
     * @param $corpus
     * @param null $aux_phid
     * @return $this
     * @author 陈妙威
     */
    public function addField($field, $corpus, $aux_phid = null)
    {
        $this->fields[] = array($field, $corpus, $aux_phid);
        return $this;
    }

    /**
     * @param $type
     * @param $related_phid
     * @param $rtype
     * @param $time
     * @return $this
     * @author 陈妙威
     */
    public function addRelationship($type, $related_phid, $rtype, $time)
    {
        $this->relationships[] = array($type, $related_phid, $rtype, $time);
        return $this;
    }

    /**
     * @param $date
     * @return $this
     * @author 陈妙威
     */
    public function setDocumentCreated($date)
    {
        $this->documentCreated = $date;
        return $this;
    }

    /**
     * @param $date
     * @return $this
     * @author 陈妙威
     */
    public function setDocumentModified($date)
    {
        $this->documentModified = $date;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getPHID()
    {
        return $this->phid;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDocumentTitle()
    {
        return $this->documentTitle;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDocumentCreated()
    {
        return $this->documentCreated;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getDocumentModified()
    {
        return $this->documentModified;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getFieldData()
    {
        return $this->fields;
    }

    /**
     * @return array
     * @author 陈妙威
     */
    public function getRelationshipData()
    {
        return $this->relationships;
    }
}

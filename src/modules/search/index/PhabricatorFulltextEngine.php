<?php

namespace orangins\modules\search\index;

use orangins\lib\db\ActiveRecordPHID;
use orangins\lib\infrastructure\cluster\search\PhabricatorSearchService;
use orangins\lib\OranginsObject;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\phid\helpers\PhabricatorPHID;
use PhutilAggregateException;
use PhutilInvalidStateException;

/**
 * Class PhabricatorFulltextEngine
 * @package orangins\modules\search\index
 * @author 陈妙威
 */
abstract class PhabricatorFulltextEngine
    extends OranginsObject
{

    /**
     * @var
     */
    private $object;

    /**
     * @param $object
     * @return $this
     * @author 陈妙威
     */
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function getViewer()
    {
        return PhabricatorUser::getOmnipotentUser();
    }

    /**
     * @param PhabricatorSearchAbstractDocument $document
     * @param $object
     * @return mixed
     * @author 陈妙威
     */
    abstract protected function buildAbstractDocument(
        PhabricatorSearchAbstractDocument $document,
        $object);

    /**
     * @throws PhutilAggregateException
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    final public function buildFulltextIndexes()
    {
        $object = $this->getObject();

        $extensions = PhabricatorFulltextEngineExtension::getAllExtensions();

        $enrich_extensions = array();
        $index_extensions = array();
        foreach ($extensions as $key => $extension) {
            if ($extension->shouldEnrichFulltextObject($object)) {
                $enrich_extensions[] = $extension;
            }

            if ($extension->shouldIndexFulltextObject($object)) {
                $index_extensions[] = $extension;
            }
        }

        $document = $this->newAbstractDocument($object);

        $this->buildAbstractDocument($document, $object);

        foreach ($enrich_extensions as $extension) {
            $extension->enrichFulltextObject($object, $document);
        }

        foreach ($index_extensions as $extension) {
            $extension->indexFulltextObject($object, $document);
        }

        PhabricatorSearchService::reindexAbstractDocument($document);
    }

    /**
     * @param ActiveRecordPHID $object
     * @return mixed
     * @author 陈妙威
     */
    protected function newAbstractDocument($object)
    {
        $phid = $object->getPHID();
        return (new PhabricatorSearchAbstractDocument())
            ->setPHID($phid)
            ->setDocumentType(PhabricatorPHID::phid_get_type($phid));
    }

}

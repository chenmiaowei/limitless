<?php

namespace orangins\modules\search\engineextension;

use orangins\lib\db\ActiveRecordPHID;
use orangins\modules\search\index\PhabricatorFulltextEngineExtension;
use orangins\modules\search\index\PhabricatorSearchAbstractDocument;

/**
 * Class PhabricatorLiskFulltextEngineExtension
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
final class PhabricatorLiskFulltextEngineExtension
    extends PhabricatorFulltextEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'lisk';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return pht('Lisk Builtin Properties');
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function shouldEnrichFulltextObject($object)
    {
        if (!($object instanceof ActiveRecordPHID)) {
            return false;
        }

//        if (!$object->getConfigOption(LiskDAO::CONFIG_TIMESTAMPS)) {
//            return false;
//        }

        return true;
    }

    /**
     * @param $object
     * @param PhabricatorSearchAbstractDocument $document
     * @author 陈妙威
     */
    public function enrichFulltextObject(
        $object,
        PhabricatorSearchAbstractDocument $document)
    {

        $document
            ->setDocumentCreated($object->created_at)
            ->setDocumentModified($object->updated_at);

    }
}

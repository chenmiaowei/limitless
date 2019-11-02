<?php

namespace orangins\lib\infrastructure\customfield\engineextension;

use Exception;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorCustomFieldInterface;
use orangins\modules\search\index\PhabricatorFulltextEngineExtension;
use orangins\modules\search\index\PhabricatorSearchAbstractDocument;

/**
 * Class PhabricatorCustomFieldFulltextEngineExtension
 * @package orangins\lib\infrastructure\customfield\engineextension
 * @author 陈妙威
 */
final class PhabricatorCustomFieldFulltextEngineExtension
    extends PhabricatorFulltextEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'customfield.fields';

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return pht('Custom Fields');
    }

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function shouldEnrichFulltextObject($object)
    {
        return ($object instanceof PhabricatorCustomFieldInterface);
    }

    /**
     * @param $object
     * @param PhabricatorSearchAbstractDocument $document
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws Exception
     * @author 陈妙威
     */
    public function enrichFulltextObject(
        $object,
        PhabricatorSearchAbstractDocument $document)
    {

        // Rebuild the ApplicationSearch indexes. These are internal and not part
        // of the fulltext search, but putting them in this workflow allows users
        // to use the same tools to rebuild the indexes, which is easy to
        // understand.

        $field_list = PhabricatorCustomField::getObjectFields(
            $object,
            PhabricatorCustomField::ROLE_DEFAULT);

        $field_list->setViewer($this->getViewer());
        $field_list->readFieldsFromStorage($object);

        // Rebuild ApplicationSearch indexes.
        $field_list->rebuildIndexes($object);

        // Rebuild global search indexes.
        $field_list->updateAbstractDocument($document);
    }

}

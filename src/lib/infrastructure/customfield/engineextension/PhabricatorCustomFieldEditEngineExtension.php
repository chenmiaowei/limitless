<?php

namespace orangins\lib\infrastructure\customfield\engineextension;

use orangins\lib\editor\PhabricatorEditEngineExtension;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorCustomFieldInterface;
use orangins\modules\transactions\bulk\PhabricatorBulkEditGroup;
use orangins\modules\transactions\editengine\PhabricatorEditEngine;
use orangins\modules\transactions\editfield\PhabricatorEditField;
use orangins\modules\transactions\interfaces\PhabricatorApplicationTransactionInterface;

/**
 * Class PhabricatorCustomFieldEditEngineExtension
 * @package orangins\lib\infrastructure\customfield\engineextension
 * @author 陈妙威
 */
final class PhabricatorCustomFieldEditEngineExtension
    extends PhabricatorEditEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'customfield.fields';

    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionPriority()
    {
        return 5000;
    }

    /**
     * @return bool|mixed
     * @author 陈妙威
     */
    public function isExtensionEnabled()
    {
        return true;
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getExtensionName()
    {
        return pht('Custom Fields');
    }

    /**
     * @param PhabricatorEditEngine $engine
     * @param PhabricatorApplicationTransactionInterface $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject(
        PhabricatorEditEngine $engine,
        PhabricatorApplicationTransactionInterface $object)
    {
        return ($object instanceof PhabricatorCustomFieldInterface);
    }

    /**
     * @param PhabricatorEditEngine $engine
     * @return array
     * @author 陈妙威
     */
    public function newBulkEditGroups(PhabricatorEditEngine $engine)
    {
        return array(
            (new PhabricatorBulkEditGroup())
                ->setKey('custom')
                ->setLabel(pht('Custom Fields')),
        );
    }

    /**
     * @param PhabricatorEditEngine $engine
     * @param PhabricatorApplicationTransactionInterface $object
     * @return array|PhabricatorEditField[]
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws \Exception
     * @author 陈妙威
     */
    public function buildCustomEditFields(
        PhabricatorEditEngine $engine,
        PhabricatorApplicationTransactionInterface $object)
    {

        $viewer = $this->getViewer();

        $field_list = PhabricatorCustomField::getObjectFields(
            $object,
            PhabricatorCustomField::ROLE_EDITENGINE);

        $field_list->setViewer($viewer);

        if ($object->getID()) {
            $field_list->readFieldsFromStorage($object);
        }

        $results = array();
        foreach ($field_list->getFields() as $field) {
            $edit_fields = $field->getEditEngineFields($engine);
            foreach ($edit_fields as $edit_field) {
                $group_key = $edit_field->getBulkEditGroupKey();
                if ($group_key === null) {
                    $edit_field->setBulkEditGroupKey('custom');
                }

                $results[] = $edit_field;
            }
        }

        return $results;
    }

}

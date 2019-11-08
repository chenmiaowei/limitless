<?php

namespace orangins\lib\export\engine;

use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorCustomFieldInterface;
use orangins\lib\infrastructure\customfield\query\PhabricatorCustomFieldStorageQuery;

/**
 * Class PhabricatorCustomFieldExportEngineExtension
 * @package orangins\lib\export\engine
 * @author 陈妙威
 */
final class PhabricatorCustomFieldExportEngineExtension
    extends PhabricatorExportEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'custom-field';

    /**
     * @var
     */
    private $object;

    /**
     * @param $object
     * @return bool
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        $this->object = $object;
        return ($object instanceof PhabricatorCustomFieldInterface);
    }

    /**
     * @return array
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function newExportFields()
    {
        $prototype = $this->object;

        $fields = $this->newCustomFields($prototype);

        $results = array();
        foreach ($fields as $field) {
            $field_key = $field->getModernFieldKey();

            $results[] = $field->newExportField()
                ->setKey($field_key);
        }

        return $results;
    }

    /**
     * @param array $objects
     * @return array
     * @author 陈妙威
     * @throws \yii\base\Exception
     */
    public function newExportData(array $objects)
    {
        $viewer = $this->getViewer();

        $field_map = array();
        foreach ($objects as $object) {
            $object_phid = $object->getPHID();

            $fields = PhabricatorCustomField::getObjectFields(
                $object,
                PhabricatorCustomField::ROLE_EXPORT);

            $fields
                ->setViewer($viewer)
                ->readFieldsFromObject($object);

            $field_map[$object_phid] = $fields;
        }

        $all_fields = array();
        foreach ($field_map as $field_list) {
            foreach ($field_list->getFields() as $field) {
                $all_fields[] = $field;
            }
        }

        (new PhabricatorCustomFieldStorageQuery())
            ->addFields($all_fields)
            ->execute();

        $results = array();
        foreach ($objects as $object) {
            $object_phid = $object->getPHID();
            $object_fields = $field_map[$object_phid];

            $map = array();
            foreach ($object_fields->getFields() as $field) {
                $key = $field->getModernFieldKey();
                $map[$key] = $field->newExportData();
            }

            $results[] = $map;
        }

        return $results;
    }

    /**
     * @param $object
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    private function newCustomFields($object)
    {
        $fields = PhabricatorCustomField::getObjectFields(
            $object,
            PhabricatorCustomField::ROLE_EXPORT);
        $fields->setViewer($this->getViewer());

        return $fields->getFields();
    }

}

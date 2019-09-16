<?php

namespace orangins\lib\infrastructure\customfield\query;

use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\OranginsObject;

/**
 * Load custom field data from storage.
 *
 * This query loads the data directly into the field objects and does not
 * return it to the caller. It can bulk load data for any list of fields,
 * even if they have different objects or object types.
 */
final class PhabricatorCustomFieldStorageQuery extends OranginsObject
{

    /**
     * @var array
     */
    private $fieldMap = array();
    /**
     * @var array
     */
    private $storageSources = array();

    /**
     * @param array $fields
     * @return $this
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function addFields(array $fields)
    {
        assert_instances_of($fields, PhabricatorCustomField::class);

        foreach ($fields as $field) {
            $this->addField($field);
        }

        return $this;
    }

    /**
     * @param PhabricatorCustomField $field
     * @return $this
     * @throws \orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function addField(PhabricatorCustomField $field)
    {
        $role_storage = PhabricatorCustomField::ROLE_STORAGE;

        if (!$field->shouldEnableForRole($role_storage)) {
            return $this;
        }

        $storage = $field->newStorageObject();
        $source_key = $storage->getStorageSourceKey();

        $this->fieldMap[$source_key][] = $field;

        if (empty($this->storageSources[$source_key])) {
            $this->storageSources[$source_key] = $storage;
        }

        return $this;
    }

    /**
     * @author 陈妙威
     */
    public function execute()
    {
        foreach ($this->storageSources as $source_key => $storage) {
            $fields = ArrayHelper::getValue($this->fieldMap, $source_key, array());
            $this->loadFieldsFromStorage($storage, $fields);
        }
    }

    /**
     * @param $storage
     * @param array $fields
     * @author 陈妙威
     */
    private function loadFieldsFromStorage($storage, array $fields)
    {
        // Only try to load fields which have a persisted object.
        $loadable = array();
        foreach ($fields as $key => $field) {
            $object = $field->getObject();
            $phid = $object->getPHID();
            if (!$phid) {
                continue;
            }

            $loadable[$key] = $field;
        }

        if ($loadable) {
            $data = $storage->loadStorageSourceData($loadable);
        } else {
            $data = array();
        }

        foreach ($fields as $key => $field) {
            if (array_key_exists($key, $data)) {
                $value = $data[$key];
                $field->setValueFromStorage($value);
                $field->didSetValueFromStorage();
            } else if (isset($loadable[$key])) {
                // NOTE: We set this only if the object exists. Otherwise, we allow
                // the field to retain any default value it may have.
                $field->setValueFromStorage(null);
                $field->didSetValueFromStorage();
            }
        }
    }

}

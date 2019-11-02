<?php

namespace orangins\lib\infrastructure\customfield\engineextension;

use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\infrastructure\customfield\interfaces\PhabricatorCustomFieldInterface;
use orangins\lib\infrastructure\customfield\query\PhabricatorCustomFieldStorageQuery;
use orangins\modules\conduit\interfaces\PhabricatorConduitSearchFieldSpecification;
use orangins\modules\search\engineextension\PhabricatorSearchEngineExtension;
use orangins\modules\search\field\PhabricatorSearchCustomFieldProxyField;
use orangins\modules\search\models\PhabricatorSavedQuery;
use yii\base\Exception;

/**
 * Class PhabricatorCustomFieldSearchEngineExtension
 * @package orangins\lib\infrastructure\customfield\engineextension
 * @author 陈妙威
 */
final class PhabricatorCustomFieldSearchEngineExtension
    extends PhabricatorSearchEngineExtension
{

    /**
     *
     */
    const EXTENSIONKEY = 'customfield';

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
        return pht('Support for Custom Fields');
    }

    /**
     * @param $object
     * @return bool|mixed
     * @author 陈妙威
     */
    public function supportsObject($object)
    {
        return ($object instanceof PhabricatorCustomFieldInterface);
    }

    /**
     * @return int
     * @author 陈妙威
     */
    public function getExtensionOrder()
    {
        return 9000;
    }

    /**
     * @param $object
     * @return array
     * @throws \Exception
     * @author 陈妙威
     */
    public function getSearchFields($object)
    {
        $engine = $this->getSearchEngine();
        $custom_fields = $this->getCustomFields($object);

        $fields = array();
        foreach ($custom_fields as $field) {
            $fields[] = (new PhabricatorSearchCustomFieldProxyField())
                ->setSearchEngine($engine)
                ->setCustomField($field);
        }

        return $fields;
    }

    /**
     * @param $object
     * @param $query
     * @param PhabricatorSavedQuery $saved
     * @param array $map
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws \Exception
     * @author 陈妙威
     */
    public function applyConstraintsToQuery(
        $object,
        $query,
        PhabricatorSavedQuery $saved,
        array $map)
    {

        $engine = $this->getSearchEngine();
        $fields = $this->getCustomFields($object);

        foreach ($fields as $field) {
            $field->applyApplicationSearchConstraintToQuery(
                $engine,
                $query,
                $saved->getParameter('custom:' . $field->getFieldIndex()));
        }
    }

    /**
     * @param $object
     * @return PhabricatorCustomField[]
     * @throws \Exception
     * @author 陈妙威
     */
    private function getCustomFields($object)
    {
        $fields = PhabricatorCustomField::getObjectFields(
            $object,
            PhabricatorCustomField::ROLE_APPLICATIONSEARCH);
        $fields->setViewer($this->getViewer());

        return $fields->getFields();
    }

    /**
     * @param $object
     * @return array
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @author 陈妙威
     */
    public function getFieldSpecificationsForConduit($object)
    {
        $fields = PhabricatorCustomField::getObjectFields(
            $object,
            PhabricatorCustomField::ROLE_CONDUIT);

        $map = array();
        foreach ($fields->getFields() as $field) {
            $key = $field->getModernFieldKey();

            // TODO: These should have proper types.
            $map[] = (new PhabricatorConduitSearchFieldSpecification())
                ->setKey($key)
                ->setType('wild')
                ->setDescription($field->getFieldDescription());
        }

        return $map;
    }

    /**
     * @param array $objects
     * @return array|null
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function loadExtensionConduitData(array $objects)
    {
        $viewer = $this->getViewer();

        $field_map = array();
        foreach ($objects as $object) {
            $object_phid = $object->getPHID();

            $fields = PhabricatorCustomField::getObjectFields(
                $object,
                PhabricatorCustomField::ROLE_CONDUIT);

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

        return array(
            'fields' => $field_map,
        );
    }

    /**
     * @param $object
     * @param $data
     * @return array
     * @author 陈妙威
     */
    public function getFieldValuesForConduit($object, $data)
    {
        $fields = $data['fields'][$object->getPHID()];

        $map = array();
        foreach ($fields->getFields() as $field) {
            $key = $field->getModernFieldKey();
            $map[$key] = $field->getConduitDictionaryValue();
        }

        return $map;
    }

}

<?php

namespace orangins\lib\infrastructure\customfield\datasource;

use Exception;
use orangins\lib\infrastructure\customfield\exception\PhabricatorCustomFieldImplementationIncompleteException;
use orangins\lib\infrastructure\customfield\field\PhabricatorCustomField;
use orangins\lib\infrastructure\customfield\standard\PhabricatorStandardCustomFieldSelect;
use orangins\modules\typeahead\datasource\PhabricatorTypeaheadDatasource;
use orangins\modules\typeahead\model\PhabricatorTypeaheadResult;
use ReflectionClass;
use ReflectionException;

/**
 * Class PhabricatorStandardSelectCustomFieldDatasource
 * @package orangins\lib\infrastructure\customfield\datasource
 * @author 陈妙威
 */
final class PhabricatorStandardSelectCustomFieldDatasource
    extends PhabricatorTypeaheadDatasource
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getBrowseTitle()
    {
        return pht('Browse Values');
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getPlaceholderText()
    {
        return pht('Type a field value...');
    }

    /**
     * @return mixed|null
     * @author 陈妙威
     */
    public function getDatasourceApplicationClass()
    {
        return null;
    }

    /**
     * @return mixed|PhabricatorTypeaheadResult[]
     * @throws ReflectionException
     * @throws PhabricatorCustomFieldImplementationIncompleteException
     * @throws Exception
     * @author 陈妙威
     */
    public function loadResults()
    {
        $viewer = $this->getViewer();

        $class = $this->getParameter('object');
        if (!class_exists($class)) {
            throw new Exception(
                pht(
                    'Custom field class "%s" does not exist.',
                    $class));
        }

        $reflection = new ReflectionClass($class);
        $interface = 'PhabricatorCustomFieldInterface';
        if (!$reflection->implementsInterface($interface)) {
            throw new Exception(
                pht(
                    'Custom field class "%s" does not implement interface "%s".',
                    $class,
                    $interface));
        }

        $role = $this->getParameter('role');
        if (!strlen($role)) {
            throw new Exception(pht('No custom field role specified.'));
        }

        $object = newv($class, array());
        $field_list = PhabricatorCustomField::getObjectFields($object, $role);

        $field_key = $this->getParameter('key');
        if (!strlen($field_key)) {
            throw new Exception(pht('No custom field key specified.'));
        }

        $field = null;
        foreach ($field_list->getFields() as $candidate_field) {
            if ($candidate_field->getFieldKey() == $field_key) {
                $field = $candidate_field;
                break;
            }
        }

        if ($field === null) {
            throw new Exception(
                pht(
                    'No field with field key "%s" exists for objects of class "%s" with ' .
                    'custom field role "%s".',
                    $field_key,
                    $class,
                    $role));
        }

        if (!($field instanceof PhabricatorStandardCustomFieldSelect)) {
            $field = $field->getProxy();
            if (!($field instanceof PhabricatorStandardCustomFieldSelect)) {
                throw new Exception(
                    pht(
                        'Field "%s" is not a standard select field, nor a proxy of a ' .
                        'standard select field.',
                        $field_key));
            }
        }

        $options = $field->getOptions();

        $results = array();
        foreach ($options as $key => $option) {
            $results[] = (new PhabricatorTypeaheadResult())
                ->setName($option)
                ->setPHID($key);
        }

        return $this->filterResultsAgainstTokens($results);
    }

}

<?php

namespace orangins\lib\infrastructure\standard;

use Exception;

/**
 * Class PhabricatorStandardCustomFieldDatasource
 * @package orangins\lib\infrastructure\standard
 * @author 陈妙威
 */
final class PhabricatorStandardCustomFieldDatasource
    extends PhabricatorStandardCustomFieldTokenizer
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getFieldType()
    {
        return 'datasource';
    }

    /**
     * @return mixed
     * @throws Exception
     * @author 陈妙威
     */
    public function getDatasource()
    {
        $parameters = $this->getFieldConfigValue('datasource.parameters', array());

        $class = $this->getFieldConfigValue('datasource.class');
        $parent = 'PhabricatorTypeaheadDatasource';
        if (!is_subclass_of($class, $parent)) {
            throw new Exception(
                \Yii::t("app",
                    'Configured datasource class "{0}" must be a valid subclass of ' .
                    '"{1}".',
                   [
                       $class,
                       $parent
                   ]));
        }

        return newv($class, array())
            ->setParameters($parameters);
    }
}

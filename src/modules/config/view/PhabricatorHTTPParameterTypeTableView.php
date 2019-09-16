<?php

namespace orangins\modules\config\view;

use orangins\lib\request\httpparametertype\AphrontHTTPParameterType;
use orangins\lib\view\AphrontView;
use orangins\lib\view\control\AphrontTableView;

/**
 * Class PhabricatorHTTPParameterTypeTableView
 * @package orangins\modules\config\view
 * @author 陈妙威
 */
final class PhabricatorHTTPParameterTypeTableView
    extends AphrontView
{

    /**
     * @var
     */
    private $types;

    /**
     * @param array $types
     * @return $this
     * @author 陈妙威
     */
    public function setHTTPParameterTypes(array $types)
    {
        assert_instances_of($types, AphrontHTTPParameterType::class);
        $this->types = $types;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getHTTPParameterTypes()
    {
        return $this->types;
    }

    /**
     * @return mixed
     * @throws \Exception
     * @author 陈妙威
     */
    public function render()
    {
        $types = $this->getHTTPParameterTypes();
        $types = mpull($types, null, 'getTypeName');

        $br = phutil_tag('br');

        $rows = array();
        foreach ($types as $name => $type) {
            $formats = $type->getFormatDescriptions();
            $formats = phutil_implode_html($br, $formats);

            $examples = $type->getExamples();
            $examples = phutil_implode_html($br, $examples);

            $rows[] = array(
                $name,
                $formats,
                $examples,
            );
        }

        $table = (new AphrontTableView($rows))
            ->setHeaders(
                array(
                    \Yii::t("app",'Type'),
                    \Yii::t("app",'Formats'),
                    \Yii::t("app",'Examples'),
                ))
            ->setColumnClasses(
                array(
                    'pri top',
                    'top',
                    'wide top prewrap',
                ));

        return $table;
    }

}

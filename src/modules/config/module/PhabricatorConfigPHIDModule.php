<?php

namespace orangins\modules\config\module;

use orangins\lib\PhabricatorApplication;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\modules\phid\PhabricatorPHIDType;

/**
 * Class PhabricatorConfigPHIDModule
 * @package orangins\modules\config\module
 * @author 陈妙威
 */
final class PhabricatorConfigPHIDModule extends PhabricatorConfigModule
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleKey()
    {
        return 'phid';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleName()
    {
        return \Yii::t("app", 'PHID Types');
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @author 陈妙威
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        /** @var PhabricatorPHIDType[] $types */
        $types = PhabricatorPHIDType::getAllTypes();
        $types = msort($types, 'getTypeConstant');

        $rows = array();
        foreach ($types as $key => $type) {
            $class_name = $type->getPHIDTypeApplicationClass();
            if ($class_name !== null) {
                $app = PhabricatorApplication::getByClass($class_name);
                $app_name = $app->getName();

                $icon = $app->getIcon();
                if ($icon) {
                    $app_icon = (new PHUIIconView())->setIcon($icon);
                } else {
                    $app_icon = null;
                }
            } else {
                $app_name = null;
                $app_icon = null;
            }

            $icon = $type->getTypeIcon();
            if ($icon) {
                $type_icon = (new PHUIIconView())->setIcon($icon);
            } else {
                $type_icon = null;
            }

            $rows[] = array(
                $type->getTypeConstant(),
                $type->getClassShortName(),
                $app_icon,
                $app_name,
                $type_icon,
                $type->getTypeName(),
            );
        }

        return (new AphrontTableView($rows))
            ->setHeaders(
                array(
                    \Yii::t("app", 'Constant'),
                    \Yii::t("app", 'Class'),
                    null,
                    \Yii::t("app", 'Application'),
                    null,
                    \Yii::t("app", 'Name'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    'pri',
                    'icon',
                    null,
                    'icon',
                    'wide',
                ));
    }

}

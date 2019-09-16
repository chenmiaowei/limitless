<?php

namespace orangins\modules\config\module;

use orangins\lib\infrastructure\edges\type\PhabricatorEdgeType;
use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;

/**
 * Class PhabricatorConfigEdgeModule
 * @package orangins\modules\config\module
 * @author 陈妙威
 */
final class PhabricatorConfigEdgeModule extends PhabricatorConfigModule
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleKey()
    {
        return 'edge';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleName()
    {
        return \Yii::t("app", 'Edge Types');
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @throws \yii\base\Exception
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        /** @var PhabricatorEdgeType[] $types */
        $types = PhabricatorEdgeType::getAllTypes();
        $types = msort($types, 'getEdgeConstant');

        $rows = array();
        foreach ($types as $key => $type) {
            $rows[] = array(
                $type->getEdgeConstant(),
                $type->getInverseEdgeConstant(),
                $type->getClassShortName(),
            );
        }

        return (new AphrontTableView($rows))
            ->setHeaders(
                array(
                    \Yii::t("app", 'Constant'),
                    \Yii::t("app", 'Inverse'),
                    \Yii::t("app", 'Class'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    null,
                    'pri wide',
                ));
    }

}

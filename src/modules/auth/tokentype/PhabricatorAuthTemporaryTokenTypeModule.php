<?php

namespace orangins\modules\auth\tokentype;

use orangins\lib\request\AphrontRequest;

final class PhabricatorAuthTemporaryTokenTypeModule
    extends PhabricatorConfigModule
{

    public function getModuleKey()
    {
        return 'temporarytoken';
    }

    public function getModuleName()
    {
        return \Yii::t("app", 'Temporary Token Types');
    }

    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $types = PhabricatorAuthTemporaryTokenType::getAllTypes();

        $rows = array();
        foreach ($types as $type) {
            $rows[] = array(
                get_class($type),
                $type->getTokenTypeConstant(),
                $type->getTokenTypeDisplayName(),
            );
        }

        return (new AphrontTableView($rows))
            ->setHeaders(
                array(
                    \Yii::t("app", 'Class'),
                    \Yii::t("app", 'Key'),
                    \Yii::t("app", 'Name'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    null,
                    'wide pri',
                ));

    }

}

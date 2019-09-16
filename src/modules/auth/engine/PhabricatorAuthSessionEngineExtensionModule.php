<?php

namespace orangins\modules\auth\engine;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use orangins\modules\config\module\PhabricatorConfigModule;

/**
 * Class PhabricatorAuthSessionEngineExtensionModule
 * @package orangins\modules\auth\engine
 * @author 陈妙威
 */
final class PhabricatorAuthSessionEngineExtensionModule
    extends PhabricatorConfigModule
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleKey()
    {
        return 'sessionengine';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleName()
    {
        return \Yii::t("app", 'Engine: Session');
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        /** @var PhabricatorAuthSessionEngineExtension[] $extensions */
        $extensions = PhabricatorAuthSessionEngineExtension::getAllExtensions();

        $rows = array();
        foreach ($extensions as $extension) {
            $rows[] = array(
                $extension->getClassShortName(),
                $extension->getExtensionKey(),
                $extension->getExtensionName(),
            );
        }

        return (new AphrontTableView($rows))
            ->setNoDataString(
                \Yii::t("app", 'There are no registered session engine extensions.'))
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

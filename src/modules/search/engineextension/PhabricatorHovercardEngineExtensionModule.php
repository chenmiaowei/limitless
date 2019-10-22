<?php

namespace orangins\modules\search\engineextension;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use orangins\modules\config\module\PhabricatorConfigModule;
use Yii;

/**
 * Class PhabricatorHovercardEngineExtensionModule
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
final class PhabricatorHovercardEngineExtensionModule
    extends PhabricatorConfigModule
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleKey()
    {
        return 'hovercardengine';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleName()
    {
        return Yii::t("app", 'Engine: Hovercards');
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $extensions = PhabricatorHovercardEngineExtension::getAllExtensions();

        $rows = array();
        foreach ($extensions as $extension) {
            $rows[] = array(
                $extension->getExtensionOrder(),
                $extension->getExtensionKey(),
                get_class($extension),
                $extension->getExtensionName(),
                $extension->isExtensionEnabled()
                    ? Yii::t("app", 'Yes')
                    : Yii::t("app", 'No'),
            );
        }

        return (new AphrontTableView($rows))
            ->setHeaders(
                array(
                    Yii::t("app", 'Order'),
                    Yii::t("app", 'Key'),
                    Yii::t("app", 'Class'),
                    Yii::t("app", 'Name'),
                    Yii::t("app", 'Enabled'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    null,
                    null,
                    'wide pri',
                    null,
                ));
    }

}

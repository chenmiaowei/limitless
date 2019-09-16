<?php

namespace orangins\modules\search\index;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use orangins\modules\config\module\PhabricatorConfigModule;

/**
 * Class PhabricatorIndexEngineExtensionModule
 * @package orangins\modules\search\index
 * @author 陈妙威
 */
final class PhabricatorIndexEngineExtensionModule
    extends PhabricatorConfigModule
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getModuleKey()
    {
        return 'indexengine';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getModuleName()
    {
        return \Yii::t("app",'Engine: Index');
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @author 陈妙威
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $extensions = PhabricatorIndexEngineExtension::getAllExtensions();

        $rows = array();
        foreach ($extensions as $extension) {
            $rows[] = array(
                get_class($extension),
                $extension->getExtensionName(),
            );
        }

        return (new AphrontTableView($rows))
            ->setHeaders(
                array(
                    \Yii::t("app",'Class'),
                    \Yii::t("app",'Name'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    'wide pri',
                ));

    }
}

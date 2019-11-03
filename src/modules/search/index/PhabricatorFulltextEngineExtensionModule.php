<?php

namespace orangins\modules\search\index;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use orangins\modules\config\module\PhabricatorConfigModule;
use PhutilInvalidStateException;
use Yii;

/**
 * Class PhabricatorFulltextEngineExtensionModule
 * @package orangins\modules\search\index
 * @author 陈妙威
 */
final class PhabricatorFulltextEngineExtensionModule
    extends PhabricatorConfigModule
{

    /**
     * @return string
     * @author 陈妙威
     */
    public function getModuleKey()
    {
        return 'fulltextengine';
    }

    /**
     * @return string
     * @author 陈妙威
     */
    public function getModuleName()
    {
        return Yii::t("app",'Engine: Fulltext');
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @throws PhutilInvalidStateException
     * @author 陈妙威
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $extensions = PhabricatorFulltextEngineExtension::getAllExtensions();

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
                    Yii::t("app",'Class'),
                    Yii::t("app",'Name'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    'wide pri',
                ));

    }

}

<?php

namespace orangins\modules\search\engineextension;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use orangins\modules\config\module\PhabricatorConfigModule;

/**
 * Class PhabricatorSearchEngineExtensionModule
 * @package orangins\modules\search\engineextension
 * @author 陈妙威
 */
final class PhabricatorSearchEngineExtensionModule
    extends PhabricatorConfigModule
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleKey()
    {
        return 'searchengine';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleName()
    {
        return pht('Engine: Search');
    }

    /**
     * @param AphrontRequest $request
     * @return mixed|AphrontTableView
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $extensions = PhabricatorSearchEngineExtension::getAllExtensions();

        $rows = array();
        foreach ($extensions as $extension) {
            $rows[] = array(
                $extension->getExtensionOrder(),
                $extension->getExtensionKey(),
                get_class($extension),
                $extension->getExtensionName(),
                $extension->isExtensionEnabled()
                    ? pht('Yes')
                    : pht('No'),
            );
        }

        return (new AphrontTableView($rows))
            ->setHeaders(
                array(
                    pht('Order'),
                    pht('Key'),
                    pht('Class'),
                    pht('Name'),
                    pht('Enabled'),
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

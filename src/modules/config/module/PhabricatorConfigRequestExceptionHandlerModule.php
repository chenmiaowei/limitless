<?php

namespace orangins\modules\config\module;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;

/**
 * Class PhabricatorConfigRequestExceptionHandlerModule
 * @package orangins\modules\config\module
 * @author 陈妙威
 */
final class PhabricatorConfigRequestExceptionHandlerModule
    extends PhabricatorConfigModule
{

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleKey()
    {
        return 'exception-handler';
    }

    /**
     * @return mixed|string
     * @author 陈妙威
     */
    public function getModuleName()
    {
        return \Yii::t("app", 'Exception Handlers');
    }

    /**
     * @param AphrontRequest $request
     * @return mixed
     * @author 陈妙威
     */
    public function renderModuleStatus(AphrontRequest $request)
    {
        $viewer = $request->getViewer();

        $handlers = AphrontRequestExceptionHandler::getAllHandlers();

        $rows = array();
        foreach ($handlers as $key => $handler) {
            $rows[] = array(
                $handler->getRequestExceptionHandlerPriority(),
                $key,
                $handler->getRequestExceptionHandlerDescription(),
            );
        }

        return (new AphrontTableView($rows))
            ->setHeaders(
                array(
                    \Yii::t("app", 'Priority'),
                    \Yii::t("app", 'Class'),
                    \Yii::t("app", 'Description'),
                ))
            ->setColumnClasses(
                array(
                    null,
                    'pri',
                    'wide',
                ));
    }

}

<?php

namespace orangins\modules\search\actions;

use orangins\lib\actions\PhabricatorAction;
use PhutilClassMapQuery;
use orangins\lib\response\Aphront400Response;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\response\AphrontResponse;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorNamedQuery;
use orangins\modules\search\models\PhabricatorNamedQueryConfig;

/**
 * Class PhabricatorSearchDefaultController
 * @package orangins\modules\search\actions
 * @author 陈妙威
 */
final class PhabricatorSearchDefaultAction extends PhabricatorAction
{

    /**
     * @return AphrontResponse|\orangins\lib\view\AphrontDialogView
     * @throws \yii\base\Exception
     * @throws \ReflectionException

     * @author 陈妙威
     */
    public function run()
    {
        $viewer = $this->getViewer();
        $request = $this->getRequest();
        $engine_class = $request->getURIData('engine');

        $classes = (new PhutilClassMapQuery())
            ->setAncestorClass(PhabricatorApplicationSearchEngine::class)
            ->execute();

        if (!isset($classes, $engine_class) || !$classes[$engine_class] instanceof PhabricatorApplicationSearchEngine) {
            return new Aphront400Response();
        }
        /** @var PhabricatorApplicationSearchEngine $engine */
        $engine = $classes[$engine_class];
        $engine->setViewer($viewer);

        $key = $request->getURIData('queryKey');

        $named_query = PhabricatorNamedQuery::find()
            ->setViewer($viewer)
            ->withEngineClassNames(array($engine_class))
            ->withQueryKeys(array($key))
            ->withUserPHIDs(
                array(
                    $viewer->getPHID(),
                    PhabricatorNamedQuery::SCOPE_GLOBAL,
                ))
            ->executeOne();

        if (!$named_query && $engine->isBuiltinQuery($key)) {
            $named_query = $engine->getBuiltinQuery($key);
        }

        if (!$named_query) {
            return new Aphront404Response();
        }

        $return_uri = $engine->getQueryManagementURI();

        $builtin = null;
        if ($engine->isBuiltinQuery($key)) {
            $builtin = $engine->getBuiltinQuery($key);
        }

        if ($request->isFormPost()) {
            $config = PhabricatorNamedQueryConfig::find()
                ->setViewer($viewer)
                ->withEngineClassNames(array($engine_class))
                ->withScopePHIDs(array($viewer->getPHID()))
                ->executeOne();
            if (!$config) {
                $config = PhabricatorNamedQueryConfig::initializeNewQueryConfig()
                    ->setEngineClassName($engine_class)
                    ->setScopePHID($viewer->getPHID());
            }

            $config->setConfigProperty(
                PhabricatorNamedQueryConfig::PROPERTY_PINNED,
                $key);

            $config->save();

            return (new AphrontRedirectResponse())->setURI($return_uri);
        }

        if ($named_query->getIsBuiltin()) {
            $query_name = $builtin->getQueryName();
        } else {
            $query_name = $named_query->getQueryName();
        }

        $title = \Yii::t("app",'Set Default Query');
        $body = \Yii::t("app",
            'This query will become your default query in the current application.');
        $button = \Yii::t("app",'Set Default Query');

        return $this->newDialog()
            ->setTitle($title)
            ->appendChild($body)
            ->addCancelButton($return_uri)
            ->addSubmitButton($button);
    }

}

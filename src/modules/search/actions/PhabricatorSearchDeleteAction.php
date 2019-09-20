<?php

namespace orangins\modules\search\actions;


use PhutilClassMapQuery;
use orangins\lib\response\Aphront400Response;
use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontDialogResponse;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\AphrontDialogView;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\search\engine\PhabricatorApplicationSearchEngine;
use orangins\modules\search\models\PhabricatorNamedQuery;

/**
 * Class PhabricatorSearchDeleteController
 * @package orangins\modules\search\actions
 * @author 陈妙威
 */
final class PhabricatorSearchDeleteAction
    extends PhabricatorSearchBaseAction
{

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \Throwable
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $id = $request->getURIData('id');
        if ($id) {
            /** @var PhabricatorNamedQuery $named_query */
            $named_query = PhabricatorNamedQuery::find()
                ->setViewer($viewer)
                ->withIDs(array($id))
                ->requireCapabilities(
                    array(
                        PhabricatorPolicyCapability::CAN_VIEW,
                        PhabricatorPolicyCapability::CAN_EDIT,
                    ))
                ->executeOne();
            if (!$named_query) {
                return new Aphront404Response();
            }

            $engine = newv($named_query->getEngineFullClassName(), array());
            $engine->setViewer($viewer);

            $key = $named_query->getQueryKey();
        } else {
            $key = $request->getURIData('queryKey');
            $engine_class = $request->getURIData('engine');

            $classes = (new PhutilClassMapQuery())
                ->setUniqueMethod("getClassShortName")
                ->setAncestorClass(PhabricatorApplicationSearchEngine::class)
                ->execute();

            if (!isset($classes, $engine_class) || !$classes[$engine_class] instanceof PhabricatorApplicationSearchEngine) {
                return new Aphront400Response();
            }
            /** @var PhabricatorApplicationSearchEngine $engine */
            $engine = $classes[$engine_class];
            $engine->setViewer($viewer);

            if (!$engine->isBuiltinQuery($key)) {
                return new Aphront404Response();
            }
            $named_query = $engine->getBuiltinQuery($key);
        }

        $builtin = null;
        if ($engine->isBuiltinQuery($key)) {
            $builtin = $engine->getBuiltinQuery($key);
        }

        $return_uri = $engine->getQueryManagementURI();

        if ($request->isDialogFormPost()) {
            if ($named_query->getIsBuiltin()) {
                $named_query->setIsDisabled((int)(!$named_query->getIsDisabled()));
                $named_query->save();
            } else {
                $named_query->delete();
            }
            return (new AphrontRedirectResponse())->setURI($return_uri);
        }

        if ($named_query->getIsBuiltin()) {
            if ($named_query->getIsDisabled()) {
                $title = \Yii::t("app",'Enable Query?');
                $desc = \Yii::t("app",
                    'Enable the built-in query "%s"? It will appear in your menu again.',
                    $builtin->getQueryName());
                $button = \Yii::t("app",'Enable Query');
            } else {
                $title = \Yii::t("app",'Disable Query?');
                $desc = \Yii::t("app",
                    'This built-in query can not be deleted, but you can disable it so ' .
                    'it does not appear in your query menu. You can enable it again ' .
                    'later. Disable built-in query "%s"?',
                    $builtin->getQueryName());
                $button = \Yii::t("app",'Disable Query');
            }
        } else {
            $title = \Yii::t("app",'Really Delete Query?');
            $desc = \Yii::t("app",
                'Really delete the query "{0}"? You can not undo this. Remember ' .
                'all the great times you had filtering results together?', [
                    $named_query->getQueryName()
                ]);
            $button = \Yii::t("app",'Delete Query');
        }

        $dialog = (new AphrontDialogView())
            ->setUser($viewer)
            ->setTitle($title)
            ->appendChild($desc)
            ->addCancelButton($return_uri)
            ->addSubmitButton($button);

        return (new AphrontDialogResponse())->setDialog($dialog);
    }

}

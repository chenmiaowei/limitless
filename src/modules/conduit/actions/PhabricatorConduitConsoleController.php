<?php

namespace orangins\modules\conduit\actions;

use orangins\lib\markup\view\PHUIRemarkupView;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormSelectControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUI;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIIconView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\conduit\method\ConduitAPIMethod;
use orangins\modules\conduit\query\PhabricatorConduitMethodQuery;
use yii\helpers\Url;

/**
 * Class PhabricatorConduitConsoleController
 * @package orangins\modules\conduit\actions
 * @author 陈妙威
 */
final class PhabricatorConduitConsoleController
    extends PhabricatorConduitController
{

    /**
     * @return bool
     * @author 陈妙威
     */
    public function shouldAllowPublic()
    {
        return true;
    }

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView|Aphront404Response
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $this->getViewer();
        $method_name = $request->getURIData('method');

        $method = (new PhabricatorConduitMethodQuery())
            ->setViewer($viewer)
            ->withMethods(array($method_name))
            ->executeOne();
        if (!$method) {
            return new Aphront404Response();
        }

        $method->setViewer($viewer);

        $call_uri = Url::to(['/conduit/api/index', 'method' => $method->getAPIMethodName()]);

        $errors = array();

        $form = (new AphrontFormView())
            ->setAction($call_uri)
            ->setUser($request->getViewer())
            ->appendRemarkupInstructions(
                \Yii::t("app",
                    'Enter parameters using **JSON**. For instance, to enter a ' .
                    'list, type: `{0}`',[
                        '["apple", "banana", "cherry"]'
                    ]));

        $params = $method->getParamTypes();
        foreach ($params as $param => $desc) {
            $form->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel($param)
                    ->setName("params[{$param}]")
                    ->setCaption($desc));
        }

        $must_login = !$viewer->isLoggedIn() &&
            $method->shouldRequireAuthentication();
        if ($must_login) {
            $errors[] = \Yii::t("app",
                'Login Required: This method requires authentication. You must ' .
                'log in before you can make calls to it.');
        } else {
            $form
                ->appendChild(
                    (new AphrontFormSelectControl())
                        ->setLabel(\Yii::t("app",'Output Format'))
                        ->setName('output')
                        ->setOptions(
                            array(
                                'human' => \Yii::t("app",'Human Readable'),
                                'json' => \Yii::t("app",'JSON'),
                            )))
                ->appendChild(
                    (new AphrontFormSubmitControl())
                        ->addCancelButton($this->getApplicationURI())
                        ->setValue(\Yii::t("app",'Call Method')));
        }

        $header = (new PHUIHeaderView())
            ->setUser($viewer)
            ->setHeader($method->getAPIMethodName())
            ->setHeaderIcon('fa-tty');

        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app",'Call Method'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($form);

        $properties = $this->buildMethodProperties($method);

        $info_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app",'API Method: {0}',[
                $method->getAPIMethodName()
            ]))
            ->setFormErrors($errors)
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->appendChild($properties);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($method->getAPIMethodName());
        $crumbs->setBorder(true);

        $view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setFooter(array(
                $info_box,
                $method->getMethodDocumentation(),
                $form_box,
                $this->renderExampleBox($method, null),
            ));

        $title = $method->getAPIMethodName();

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

    /**
     * @param ConduitAPIMethod $method
     * @return PHUIPropertyListView
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildMethodProperties(ConduitAPIMethod $method)
    {
        $viewer = $this->getViewer();

        $view = (new PHUIPropertyListView());

        $status = $method->getMethodStatus();
        $reason = $method->getMethodStatusDescription();

        switch ($status) {
            case ConduitAPIMethod::METHOD_STATUS_UNSTABLE:
                $stability_icon = 'fa-exclamation-triangle yellow';
                $stability_label = \Yii::t("app",'Unstable Method');
                $stability_info = nonempty(
                    $reason,
                    \Yii::t("app",
                        'This method is new and unstable. Its interface is subject ' .
                        'to change.'));
                break;
            case ConduitAPIMethod::METHOD_STATUS_DEPRECATED:
                $stability_icon = 'fa-exclamation-triangle red';
                $stability_label = \Yii::t("app",'Deprecated Method');
                $stability_info = nonempty($reason, \Yii::t("app",'This method is deprecated.'));
                break;
            case ConduitAPIMethod::METHOD_STATUS_FROZEN:
                $stability_icon = 'fa-archive grey';
                $stability_label = \Yii::t("app",'Frozen Method');
                $stability_info = nonempty(
                    $reason,
                    \Yii::t("app",'This method is frozen and will eventually be deprecated.'));
                break;
            default:
                $stability_label = null;
                break;
        }

        if ($stability_label) {
            $view->addProperty(
                \Yii::t("app",'Stability'),
                array(
                    (new PHUIIconView())->setIcon($stability_icon),
                    ' ',
                    phutil_tag('strong', array(), $stability_label . ':'),
                    ' ',
                    $stability_info,
                ));
        }

        $view->addProperty(
            \Yii::t("app",'Returns'),
            $method->getReturnType());

        $error_types = $method->getErrorTypes();
        $error_types['ERR-CONDUIT-CORE'] = \Yii::t("app",'See error message for details.');
        $error_description = array();
        foreach ($error_types as $error => $meaning) {
            $error_description[] = hsprintf(
                '<li><strong>%s:</strong> %s</li>',
                $error,
                $meaning);
        }
        $error_description = phutil_tag('ul', array(), $error_description);

        $view->addProperty(
            \Yii::t("app",'Errors'),
            $error_description);


        $scope = $method->getRequiredScope();
        switch ($scope) {
            case ConduitAPIMethod::SCOPE_ALWAYS:
                $oauth_icon = 'fa-globe green';
                $oauth_description = \Yii::t("app",
                    'OAuth clients may always call this method.');
                break;
            case ConduitAPIMethod::SCOPE_NEVER:
                $oauth_icon = 'fa-ban red';
                $oauth_description = \Yii::t("app",
                    'OAuth clients may never call this method.');
                break;
            default:
                $oauth_icon = 'fa-unlock-alt blue';
                $oauth_description = \Yii::t("app",
                    'OAuth clients may call this method after requesting access to ' .
                    'the "{0}" scope.', [
                        $scope
                    ]);
                break;
        }

        $view->addProperty(
            \Yii::t("app",'OAuth Scope'),
            array(
                (new PHUIIconView())->addClass(PHUI::MARGIN_MEDIUM_RIGHT)->setIcon($oauth_icon),
                ' ',
                $oauth_description,
            ));

        $view->addSectionHeader(
            \Yii::t("app",'Description'), PHUIPropertyListView::ICON_SUMMARY);
        $view->addTextContent(
            new PHUIRemarkupView($viewer, $method->getMethodDescription()));

        return $view;
    }


}

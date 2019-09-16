<?php

namespace orangins\modules\search\actions;

use orangins\lib\response\Aphront404Response;
use orangins\lib\response\AphrontRedirectResponse;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormCheckboxControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPageHeaderView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\search\models\PhabricatorNamedQuery;
use orangins\modules\search\models\PhabricatorSavedQuery;

/**
 * Class PhabricatorSearchEditAction
 * @package orangins\modules\search\actions
 * @author 陈妙威
 */
final class PhabricatorSearchEditAction
    extends SearchAction
{

    /**
     * @return mixed
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->controller->getRequest();
        $viewer = $this->controller->getViewer();

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

            $query_key = $named_query->getQueryKey();
        } else {
            $query_key = $request->getURIData('queryKey');
            $named_query = null;
        }

        /** @var PhabricatorSavedQuery $saved_query */
        $saved_query = PhabricatorSavedQuery::find()
            ->setViewer($viewer)
            ->withQueryKeys(array($query_key))
            ->executeOne();
        if (!$saved_query) {
            return new Aphront404Response();
        }

        $engine = $saved_query->newEngine()->setViewer($viewer);

        $complete_uri = $engine->getQueryManagementURI();
        $cancel_uri = $complete_uri;

        if (!$named_query) {
            $named_query = (new PhabricatorNamedQuery())
                ->setUserPHID($viewer->getPHID())
                ->setQueryKey($saved_query->getQueryKey())
                ->setEngineClassName($saved_query->getEngineClassName());

            // If we haven't saved the query yet, this is a "Save..." operation, so
            // take the user back to the query if they cancel instead of back to the
            // management interface.
            $cancel_uri = $engine->getQueryResultsPageURI(
                $saved_query->getQueryKey());

            $is_new = true;
        } else {
            $is_new = false;
        }

        $can_global = ($viewer->getIsAdmin() && $is_new);

        $v_global = false;

        $e_name = true;
        $errors = array();

        if ($request->isFormPost()) {
            if ($can_global) {
                $v_global = $request->getBool('global');
                if ($v_global) {
                    $named_query->setUserPHID(PhabricatorNamedQuery::SCOPE_GLOBAL);
                }
            }

            $named_query->setQueryName($request->getStr('name'));
            if (!strlen($named_query->getQueryName())) {
                $e_name = \Yii::t("app", 'Required');
                $errors[] = \Yii::t("app", 'You must name the query.');
            } else {
                $e_name = null;
            }

            if (!$errors) {
                $named_query->save();
                return (new AphrontRedirectResponse())->setURI($complete_uri);
            }
        }

        $form = (new AphrontFormView())
            ->setViewer($viewer);

        $form->appendChild(
            (new AphrontFormTextControl())
                ->setName('name')
                ->setLabel(\Yii::t("app", 'Query Name'))
                ->setValue($named_query->getQueryName())
                ->setError($e_name));

        if ($can_global) {
            $form->appendChild(
                (new AphrontFormCheckboxControl())
                    ->addCheckbox(
                        'global',
                        '1',
                        \Yii::t("app", 'Save this query as a global query, making it visible to all users.'),
                        $v_global
                    ));
        }

        $form->appendChild(
            (new AphrontFormSubmitControl())
                ->setValue(\Yii::t("app", 'Save Query'))
                ->addCancelButton($cancel_uri));

        if ($named_query->getID()) {
            $title = \Yii::t("app", 'Edit Saved Query');
            $header_icon = 'fa-pencil';
        } else {
            $title = \Yii::t("app", 'Save Query');
            $header_icon = 'fa-search';
        }

        $form_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Query'))
            ->setFormErrors($errors)
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($form);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($title);
        $crumbs->setBorder(true);

        $header = (new PHUIPageHeaderView())
            ->setHeader($title)
            ->setHeaderIcon($header_icon);

        $view = (new PHUITwoColumnView())
            ->setFooter($form_box);

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);

    }
}

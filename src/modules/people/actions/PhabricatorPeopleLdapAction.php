<?php

namespace orangins\modules\people\actions;

use orangins\lib\request\AphrontRequest;
use orangins\lib\view\control\AphrontTableView;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormPasswordControl;
use orangins\lib\view\form\control\AphrontFormSubmitControl;
use orangins\lib\view\form\control\AphrontFormTextControl;
use orangins\lib\view\phui\PHUIBoxView;
use orangins\lib\view\phui\PHUIInfoView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIObjectItemListView;
use orangins\lib\view\phui\PHUIObjectItemView;
use orangins\modules\auth\provider\PhabricatorLDAPAuthProvider;
use orangins\modules\people\capability\PeopleCreateUsersCapability;
use orangins\modules\people\editors\PhabricatorUserEditor;
use orangins\modules\people\models\PhabricatorExternalAccount;
use orangins\modules\people\models\PhabricatorUser;
use orangins\modules\people\models\PhabricatorUserEmail;
use PhutilOpaqueEnvelope;
use Exception;

/**
 * Class PhabricatorPeopleLdapAction
 * @package orangins\modules\people\actions
 * @author 陈妙威
 */
final class PhabricatorPeopleLdapAction
    extends PhabricatorPeopleAction
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $this->requireApplicationCapability(
            PeopleCreateUsersCapability::CAPABILITY);
        $admin = $request->getViewer();

        $content = array();

        $form = (new AphrontFormView())
            ->setAction($request->getRequestURI()
                ->alter('search', 'true')->alter('import', null))
            ->setUser($admin)
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'LDAP username'))
                    ->setName('username'))
            ->appendChild(
                (new AphrontFormPasswordControl())
                    ->setDisableAutocomplete(true)
                    ->setLabel(\Yii::t("app", 'Password'))
                    ->setName('password'))
            ->appendChild(
                (new AphrontFormTextControl())
                    ->setLabel(\Yii::t("app", 'LDAP query'))
                    ->setCaption(\Yii::t("app", 'A filter such as %s.', '(objectClass=*)'))
                    ->setName('query'))
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->setValue(\Yii::t("app", 'Search')));

        $panel = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Import LDAP Users'))
            ->setForm($form);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(
            \Yii::t("app", 'Import LDAP Users'),
            $this->getApplicationURI('/ldap/'));

        $nav = $this->buildSideNavView();
        $nav->selectFilter('ldap');
        $nav->appendChild($content);

        if ($request->getStr('import')) {
            $nav->appendChild($this->processImportRequest($request));
        }

        $nav->appendChild($panel);

        if ($request->getStr('search')) {
            $nav->appendChild($this->processSearchRequest($request));
        }

        return $this->newPage()
            ->setTitle(\Yii::t("app", 'Import LDAP Users'))
            ->setCrumbs($crumbs)
            ->setNavigation($nav);
    }

    /**
     * @param $request
     * @return array
     * @author 陈妙威
     */
    private function processImportRequest(AphrontRequest $request)
    {
        $admin = $request->getViewer();
        $usernames = $request->getArr('usernames');
        $emails = $request->getArr('email');
        $names = $request->getArr('name');

        $notice_view = new PHUIInfoView();
        $notice_view->setSeverity(PHUIInfoView::SEVERITY_NOTICE);
        $notice_view->setTitle(\Yii::t("app", 'Import Successful'));
        $notice_view->setErrors(array(
            \Yii::t("app", 'Successfully imported users from LDAP'),
        ));

        $list = new PHUIObjectItemListView();
        $list->setNoDataString(\Yii::t("app", 'No users imported?'));

        foreach ($usernames as $username) {
            $user = new PhabricatorUser();
            $user->setUsername($username);
            $user->setRealname($names[$username]);

            $email_obj = (new PhabricatorUserEmail())
                ->setAddress($emails[$username])
                ->setIsVerified(1);
            try {
                (new PhabricatorUserEditor())
                    ->setActor($admin)
                    ->createNewUser($user, $email_obj);

                (new PhabricatorExternalAccount())
                    ->setUserPHID($user->getPHID())
                    ->setAccountType('ldap')
                    ->setAccountDomain('self')
                    ->setAccountID($username)
                    ->save();

                $header = \Yii::t("app", 'Successfully added %s', $username);
                $attribute = null;
                $color = 'fa-check green';
            } catch (Exception $ex) {
                $header = \Yii::t("app", 'Failed to add %s', $username);
                $attribute = $ex->getMessage();
                $color = 'fa-times red';
            }

            $item = (new PHUIObjectItemView())
                ->setHeader($header)
                ->addAttribute($attribute)
                ->setStatusIcon($color);

            $list->addItem($item);
        }

        return array(
            $notice_view,
            $list,
        );

    }

    /**
     * @param $request
     * @return PHUIBoxView
     * @throws \PhutilInvalidStateException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws Exception
     * @author 陈妙威
     */
    private function processSearchRequest(AphrontRequest $request)
    {
        $panel = new PHUIBoxView();
        $admin = $request->getViewer();

        $search = $request->getStr('query');

        $ldap_provider = PhabricatorLDAPAuthProvider::getLDAPProvider();
        if (!$ldap_provider) {
            throw new Exception(\Yii::t("app", 'No LDAP provider enabled!'));
        }

        $ldap_adapter = $ldap_provider->getAdapter();
        $ldap_adapter->setLoginUsername($request->getStr('username'));
        $ldap_adapter->setLoginPassword(
            new PhutilOpaqueEnvelope($request->getStr('password')));

        // This causes us to connect and bind.
        // TODO: Clean up this discard mode stuff.
        DarkConsoleErrorLogPluginAPI::enableDiscardMode();
        $ldap_adapter->getAccountID();
        DarkConsoleErrorLogPluginAPI::disableDiscardMode();

        $results = $ldap_adapter->searchLDAP('%Q', $search);

        foreach ($results as $key => $record) {
            $account_id = $ldap_adapter->readLDAPRecordAccountID($record);
            if (!$account_id) {
                unset($results[$key]);
                continue;
            }

            $info = array(
                $account_id,
                $ldap_adapter->readLDAPRecordEmail($record),
                $ldap_adapter->readLDAPRecordRealName($record),
            );
            $results[$key] = $info;
            $results[$key][] = $this->renderUserInputs($info);
        }

        $form = (new AphrontFormView())
            ->setUser($admin);

        $table = new AphrontTableView($results);
        $table->setHeaders(
            array(
                \Yii::t("app", 'Username'),
                \Yii::t("app", 'Email'),
                \Yii::t("app", 'Real Name'),
                \Yii::t("app", 'Import?'),
            ));
        $form->appendChild($table);
        $form->setAction($request->getRequestURI()
            ->alter('import', 'true')->alter('search', null))
            ->appendChild(
                (new AphrontFormSubmitControl())
                    ->setValue(\Yii::t("app", 'Import')));

        $panel->appendChild($form);

        return $panel;
    }

    /**
     * @param $user
     * @return \PhutilSafeHTML
     * @throws Exception
     * @author 陈妙威
     */
    private function renderUserInputs($user)
    {
        $username = $user[0];
        return hsprintf(
            '%s%s%s',
            phutil_tag(
                'input',
                array(
                    'type' => 'checkbox',
                    'name' => 'usernames[]',
                    'value' => $username,
                )),
            phutil_tag(
                'input',
                array(
                    'type' => 'hidden',
                    'name' => "email[$username]",
                    'value' => $user[1],
                )),
            phutil_tag(
                'input',
                array(
                    'type' => 'hidden',
                    'name' => "name[$username]",
                    'value' => $user[2],
                )));
    }

}

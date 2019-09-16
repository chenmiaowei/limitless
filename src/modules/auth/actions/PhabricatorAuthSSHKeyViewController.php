<?php

namespace orangins\modules\auth\actions;

use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\request\AphrontRequest;
use orangins\lib\response\Aphront404Response;
use orangins\lib\view\AphrontView;
use orangins\lib\view\layout\PhabricatorActionView;
use orangins\lib\view\phui\PHUIButtonView;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\auth\models\PhabricatorAuthSSHKey;
use orangins\modules\auth\models\PhabricatorAuthSSHKeyTransaction;
use orangins\modules\auth\query\PhabricatorAuthSSHKeyQuery;
use orangins\modules\policy\capability\PhabricatorPolicyCapability;
use orangins\modules\policy\filter\PhabricatorPolicyFilter;

/**
 * Class PhabricatorAuthSSHKeyViewController
 * @package orangins\modules\auth\actions
 * @author 陈妙威
 */
final class PhabricatorAuthSSHKeyViewController
    extends PhabricatorAuthSSHKeyAction
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
     * @return Aphront404Response|\orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilMethodNotImplementedException
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $id = $request->getURIData('id');

        $ssh_key = PhabricatorAuthSSHKey::find()
            ->setViewer($viewer)
            ->withIDs(array($id))
            ->executeOne();
        if (!$ssh_key) {
            return new Aphront404Response();
        }

        $this->setSSHKeyObject($ssh_key->getObject());

        $title = \Yii::t("app", 'SSH Key %d', $ssh_key->getID());

        $curtain = $this->buildCurtain($ssh_key);
        $details = $this->buildPropertySection($ssh_key);

        $header = (new PHUIHeaderView())
            ->setUser($viewer)
            ->setHeader($ssh_key->getName())
            ->setHeaderIcon('fa-key');

        if ($ssh_key->getIsActive()) {
            $header->setStatus('fa-check', AphrontView::COLOR_SUCCESS, \Yii::t("app", 'Active'));
        } else {
            $header->setStatus('fa-ban', 'dark', \Yii::t("app", 'Revoked'));
        }

        $header->addActionLink(
            (new PHUIButtonView())
                ->setTag('a')
                ->setText(\Yii::t("app", 'View Active Keys'))
                ->setHref($ssh_key->getObject()->getSSHPublicKeyManagementURI($viewer))
                ->setIcon('fa-list-ul'));

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb($title);
        $crumbs->setBorder(true);

        $timeline = $this->buildTransactionTimeline(
            $ssh_key, PhabricatorAuthSSHKeyTransaction::find());
        $timeline->setShouldTerminate(true);

        $view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setCurtain($curtain)
            ->setMainColumn(
                array(
                    $details,
                    $timeline,
                ));

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

    /**
     * @param PhabricatorAuthSSHKey $ssh_key
     * @return mixed
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @author 陈妙威
     */
    private function buildCurtain(PhabricatorAuthSSHKey $ssh_key)
    {
        $viewer = $this->getViewer();

        $can_edit = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $ssh_key,
            PhabricatorPolicyCapability::CAN_EDIT);

        $id = $ssh_key->getID();

        $edit_uri = $this->getApplicationURI("sshkey/edit/{$id}/");
        $revoke_uri = $this->getApplicationURI("sshkey/revoke/{$id}/");

        $curtain = $this->newCurtainView($ssh_key);

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setIcon('fa-pencil')
                ->setName(\Yii::t("app", 'Edit SSH Key'))
                ->setHref($edit_uri)
                ->setWorkflow(true)
                ->setDisabled(!$can_edit));

        $curtain->addAction(
            (new PhabricatorActionView())
                ->setIcon('fa-times')
                ->setName(\Yii::t("app", 'Revoke SSH Key'))
                ->setHref($revoke_uri)
                ->setWorkflow(true)
                ->setDisabled(!$can_edit));

        return $curtain;
    }

    /**
     * @param PhabricatorAuthSSHKey $ssh_key
     * @return PHUIObjectBoxView
     * @throws \Exception
     * @author 陈妙威
     */
    private function buildPropertySection(
        PhabricatorAuthSSHKey $ssh_key)
    {
        $viewer = $this->getViewer();

        $properties = (new PHUIPropertyListView())
            ->setUser($viewer);

        $properties->addProperty(\Yii::t("app", 'SSH Key Type'), $ssh_key->getKeyType());
        $properties->addProperty(
            \Yii::t("app", 'Created'),
            OranginsViewUtil::phabricator_datetime($ssh_key->created_at, $viewer));

        return (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Details'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->appendChild($properties);
    }

}

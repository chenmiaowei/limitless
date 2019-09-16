<?php

namespace orangins\modules\conduit\actions;

use AphrontWriteGuard;
use Filesystem;
use orangins\lib\view\form\AphrontFormView;
use orangins\lib\view\form\control\AphrontFormTextAreaControl;
use orangins\lib\view\phui\PHUIHeaderView;
use orangins\lib\view\phui\PHUIObjectBoxView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\auth\engine\PhabricatorAuthSessionEngine;
use orangins\modules\conduit\models\PhabricatorConduitCertificateToken;

/**
 * Class PhabricatorConduitTokenController
 * @package orangins\modules\conduit\actions
 * @author 陈妙威
 */
final class PhabricatorConduitTokenController
    extends PhabricatorConduitController
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \PhutilInvalidStateException
     * @throws \PhutilMethodNotImplementedException
     * @throws \yii\base\Exception
     * @throws \Throwable
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        (new PhabricatorAuthSessionEngine())->requireHighSecuritySession(
            $viewer,
            $this->getRequest(),
            '/');

        // Ideally we'd like to verify this, but it's fine to leave it unguarded
        // for now and verifying it would need some Ajax junk or for the user to
        // click a button or similar.
        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();


        $old_token = PhabricatorConduitCertificateToken::find()->andWhere(['user_phid' => $viewer->getPHID()])->one();
        if ($old_token) {
            $old_token->delete();
        }

        $token = (new PhabricatorConduitCertificateToken())
            ->setUserPHID($viewer->getPHID())
            ->setToken(Filesystem::readRandomCharacters(40))
            ->save();

        unset($unguarded);

        $pre_instructions = \Yii::t("app",
            'Copy and paste this token into the prompt given to you by ' .
            '`arc install-certificate`');

        $post_instructions = \Yii::t("app",
            'After you copy and paste this token, `arc` will complete ' .
            'the certificate install process for you.');

        Javelin::initBehavior('select-on-click');

        $form = (new AphrontFormView())
            ->setUser($viewer)
            ->appendRemarkupInstructions($pre_instructions)
            ->appendChild(
                (new AphrontFormTextAreaControl())
                    ->setLabel(\Yii::t("app", 'Token'))
                    ->setHeight(AphrontFormTextAreaControl::HEIGHT_VERY_SHORT)
                    ->setReadonly(true)
                    ->setSigil('select-on-click')
                    ->setValue($token->getToken()))
            ->appendRemarkupInstructions($post_instructions);

        $crumbs = $this->buildApplicationCrumbs();
        $crumbs->addTextCrumb(\Yii::t("app", 'Install Certificate'));
        $crumbs->setBorder(true);

        $object_box = (new PHUIObjectBoxView())
            ->setHeaderText(\Yii::t("app", 'Certificate Token'))
            ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
            ->setForm($form);

        $title = \Yii::t("app", 'Certificate Install Token');

        $header = (new PHUIHeaderView())
            ->setHeader($title);

        $view = (new PHUITwoColumnView())
            ->setHeader($header)
            ->setFooter($object_box);

        return $this->newPage()
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($view);
    }

}

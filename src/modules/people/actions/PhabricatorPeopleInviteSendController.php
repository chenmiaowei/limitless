<?php
namespace orangins\modules\people\actions;

final class PhabricatorPeopleInviteSendController
  extends PhabricatorPeopleInviteAction {

  public function run() {$request = $this->getRequest();
    $viewer = $this->getViewer();

    $this->requireApplicationCapability(
      PeopleCreateUsersCapability::CAPABILITY);

    $is_confirm = false;
    $errors = array();
    $confirm_errors = array();
    $e_emails = true;

    $message = $request->getStr('message');
    $emails = $request->getStr('emails');
    $severity = PHUIInfoView::SEVERITY_ERROR;
    if ($request->isFormPost()) {
      // NOTE: We aren't using spaces as a delimiter here because email
      // addresses with names often include spaces.
      $email_list = preg_split('/[,;\n]+/', $emails);
      foreach ($email_list as $key => $email) {
        if (!strlen(trim($email))) {
          unset($email_list[$key]);
        }
      }

      if ($email_list) {
        $e_emails = null;
      } else {
        $e_emails = \Yii::t("app",'Required');
        $errors[] = \Yii::t("app",
          'To send invites, you must enter at least one email address.');
      }

      if (!$errors) {
        $is_confirm = true;

        $actions = PhabricatorAuthInviteAction::newActionListFromAddresses(
          $viewer,
          $email_list);

        $any_valid = false;
        $all_valid = true;
        foreach ($actions as $action) {
          if ($action->willSend()) {
            $any_valid = true;
          } else {
            $all_valid = false;
          }
        }

        if (!$any_valid) {
          $confirm_errors[] = \Yii::t("app",
            'None of the provided addresses are valid invite recipients. '.
            'Review the table below for details. Revise the address list '.
            'to continue.');
        } else if ($all_valid) {
          $confirm_errors[] = \Yii::t("app",
            'All of the addresses appear to be valid invite recipients. '.
            'Confirm the actions below to continue.');
          $severity = PHUIInfoView::SEVERITY_NOTICE;
        } else {
          $confirm_errors[] = \Yii::t("app",
            'Some of the addresses you entered do not appear to be '.
            'valid recipients. Review the table below. You can revise '.
            'the address list, or ignore these errors and continue.');
          $severity = PHUIInfoView::SEVERITY_WARNING;
        }

        if ($any_valid && $request->getBool('confirm')) {

          // TODO: The copywriting on this mail could probably be more
          // engaging and we could have a fancy HTML version.

          $template = array();
          $template[] = \Yii::t("app",
            '%s has invited you to join Phabricator.',
            $viewer->getFullName());

          if (strlen(trim($message))) {
            $template[] = $message;
          }

          $template[] = \Yii::t("app",
            'To register an account and get started, follow this link:');

          // This isn't a variable; it will be replaced later on in the
          // daemons once they generate the URI.
          $template[] = '{$INVITE_URI}';

          $template[] = \Yii::t("app",
            'If you already have an account, you can follow the link to '.
            'quickly verify this email address.');

          $template = implode("\n\n", $template);

          foreach ($actions as $action) {
            if ($action->willSend()) {
              $action->sendInvite($viewer, $template);
            }
          }

          // TODO: This is a bit anticlimactic. We don't really have anything
          // to show the user because the action is happening in the background
          // and the invites won't exist yet. After T5166 we can show a
          // better progress bar.
          return (new AphrontRedirectResponse())
            ->setURI($this->getApplicationURI());
        }
      }
    }

    if ($is_confirm) {
      $title = \Yii::t("app",'Confirm Invites');
    } else {
      $title = \Yii::t("app",'Invite Users');
    }

    $crumbs = $this->buildApplicationCrumbs();
    if ($is_confirm) {
      $crumbs->addTextCrumb(\Yii::t("app",'Confirm'));
    } else {
      $crumbs->addTextCrumb(\Yii::t("app",'Invite Users'));
    }
    $crumbs->setBorder(true);

    $confirm_box = null;
    $info_view = null;
    if ($is_confirm) {

      $handles = array();
      if ($actions) {
        $handles = $this->loadViewerHandles(mpull($actions, 'getUserPHID'));
      }

      $invite_table = (new PhabricatorAuthInviteActionTableView())
        ->setUser($viewer)
        ->setInviteActions($actions)
        ->setHandles($handles);

      $confirm_form = null;
      if ($any_valid) {
        $confirm_form = (new AphrontFormView())
          ->setUser($viewer)
          ->addHiddenInput('message', $message)
          ->addHiddenInput('emails', $emails)
          ->addHiddenInput('confirm', true)
          ->appendRemarkupInstructions(
            \Yii::t("app",
              'If everything looks good, click **Send Invitations** to '.
              'deliver email invitations these users. Otherwise, edit the '.
              'email list or personal message at the bottom of the page to '.
              'revise the invitations.'))
          ->appendChild(
            (new AphrontFormSubmitControl())
              ->setValue(\Yii::t("app",'Send Invitations')));
      }

      $info_view = (new PHUIInfoView())
        ->setErrors($confirm_errors)
        ->setSeverity($severity);

      $confirm_box = (new PHUIObjectBoxView())
        ->setHeaderText(\Yii::t("app",'Confirm Invites'))
        ->setTable($invite_table)
        ->appendChild($confirm_form)
        ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY);
    }

    $form = (new AphrontFormView())
      ->setUser($viewer)
      ->appendRemarkupInstructions(
        \Yii::t("app",
          'To invite users to Phabricator, enter their email addresses below. '.
          'Separate addresses with commas or newlines.'))
      ->appendChild(
        (new AphrontFormTextAreaControl())
          ->setLabel(\Yii::t("app",'Email Addresses'))
          ->setName(\Yii::t("app",'emails'))
          ->setValue($emails)
          ->setError($e_emails)
          ->setHeight(AphrontFormTextAreaControl::HEIGHT_VERY_TALL))
      ->appendRemarkupInstructions(
        \Yii::t("app",
          'You can optionally include a heartfelt personal message in '.
          'the email.'))
      ->appendChild(
        (new AphrontFormTextAreaControl())
          ->setLabel(\Yii::t("app",'Message'))
          ->setName(\Yii::t("app",'message'))
          ->setValue($message))
      ->appendChild(
        (new AphrontFormSubmitControl())
          ->setValue(
            $is_confirm
              ? \Yii::t("app",'Update Preview')
              : \Yii::t("app",'Continue'))
          ->addCancelButton($this->getApplicationURI('invite/')));

    $header = (new PHUIHeaderView())
      ->setHeader($title)
      ->setHeaderIcon('fa-group');

    $box = (new PHUIObjectBoxView())
      ->setHeaderText(
        $is_confirm
          ? \Yii::t("app",'Revise Invites')
          : \Yii::t("app",'Invite Users'))
      ->setFormErrors($errors)
      ->setForm($form)
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY);

    $view = (new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter(array(
        $info_view,
        $confirm_box,
        $box,
      ));

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->appendChild($view);

  }

}

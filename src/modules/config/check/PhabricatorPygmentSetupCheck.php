<?php
namespace orangins\modules\config\check;

final class PhabricatorPygmentSetupCheck extends PhabricatorSetupCheck {

  public function getDefaultGroup() {
    return self::GROUP_OTHER;
  }

  protected function executeChecks() {
    $pygment = PhabricatorEnv::getEnvConfig('pygments.enabled');

    if ($pygment) {
      if (!Filesystem::binaryExists('pygmentize')) {
        $summary = \Yii::t("app",
          'You enabled pygments but the %s script is not '.
          'actually available, your %s is probably broken.',
          'pygmentize',
          '$PATH');

        $message = \Yii::t("app",
          'The environmental variable %s does not contain %s. '.
          'You have enabled pygments, which requires '.
          '%s to be available in your %s variable.',
          '$PATH',
          'pygmentize',
          'pygmentize',
          '$PATH');

        $this
          ->newIssue('pygments.enabled')
          ->setName(\Yii::t("app",'%s Not Found', 'pygmentize'))
          ->setSummary($summary)
          ->setMessage($message)
          ->addRelatedPhabricatorConfig('pygments.enabled')
          ->addPhabricatorConfig('environment.append-paths');
      } else {
        list($err) = exec_manual('pygmentize -h');
        if ($err) {
          $summary = \Yii::t("app",
            'You have enabled pygments and the %s script is '.
            'available, but does not seem to work.',
            'pygmentize');

          $message = \Yii::t("app",
            'Phabricator has %s available in %s, but the binary '.
            'exited with an error code when run as %s. Check that it is '.
            'installed correctly.',
            phutil_tag('tt', array(), 'pygmentize'),
            phutil_tag('tt', array(), '$PATH'),
            phutil_tag('tt', array(), 'pygmentize -h'));

          $this
            ->newIssue('pygments.failed')
            ->setName(\Yii::t("app",'%s Not Working', 'pygmentize'))
            ->setSummary($summary)
            ->setMessage($message)
            ->addRelatedPhabricatorConfig('pygments.enabled')
            ->addPhabricatorConfig('environment.append-paths');
        }
      }
    } else {
      $summary = \Yii::t("app",
        'Pygments should be installed and enabled '.
        'to provide advanced syntax highlighting.');

      $message = \Yii::t("app",
        'Phabricator can highlight a few languages by default, '.
        'but installing and enabling Pygments (a third-party highlighting '.
        "tool) will add syntax highlighting for many more languages. \n\n".
        'For instructions on installing and enabling Pygments, see the '.
        '%s configuration option.'."\n\n".
        'If you do not want to install Pygments, you can ignore this issue.',
        phutil_tag('tt', array(), 'pygments.enabled'));

      $this
        ->newIssue('pygments.noenabled')
        ->setName(\Yii::t("app",'Install Pygments to Improve Syntax Highlighting'))
        ->setSummary($summary)
        ->setMessage($message)
        ->addRelatedPhabricatorConfig('pygments.enabled');
    }
  }
}

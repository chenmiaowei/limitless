<?php
namespace orangins\modules\config\option;

final class PhabricatorAccessLogConfigOptions
  extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return \Yii::t("app", 'Access Logs');
  }

  public function getDescription() {
    return \Yii::t("app", 'Configure the access logs, which log HTTP/SSH requests.');
  }

  public function getIcon() {
    return 'icon-list';
  }

  public function getGroup() {
    return 'core';
  }

  public function getOptions() {
    $common_map = array(
      'C' => \Yii::t("app", 'The controller or workflow which handled the request.'),
      'c' => \Yii::t("app", 'The HTTP response code or process exit code.'),
      'D' => \Yii::t("app", 'The request date.'),
      'e' => \Yii::t("app", 'Epoch timestamp.'),
      'h' => \Yii::t("app", "The webserver's host name."),
      'p' => \Yii::t("app", 'The PID of the server process.'),
      'r' => \Yii::t("app", 'The remote IP.'),
      'T' => \Yii::t("app", 'The request duration, in microseconds.'),
      'U' => \Yii::t("app", 'The request path, or request target.'),
      'm' => \Yii::t("app", 'For conduit, the Conduit method which was invoked.'),
      'a' => \Yii::t("app", 'For conduit, the api tokens which was used.'),
      'u' => \Yii::t("app", 'The logged-in username, if one is logged in.'),
      'P' => \Yii::t("app", 'The logged-in user PHID, if one is logged in.'),
      'i' => \Yii::t("app", 'Request input, in bytes.'),
      'o' => \Yii::t("app", 'Request output, in bytes.'),
      'I' => \Yii::t("app", 'Cluster instance name, if configured.'),
    );

    $http_map = $common_map + array(
      'R' => \Yii::t("app", 'The HTTP referrer.'),
      'M' => \Yii::t("app", 'The HTTP method.'),
    );

    $ssh_map = $common_map + array(
      's' => \Yii::t("app", 'The system user.'),
      'S' => \Yii::t("app", 'The system sudo user.'),
      'k' => \Yii::t("app", 'ID of the SSH key used to authenticate the request.'),
    );

    $http_desc = \Yii::t("app",
      'Format for the HTTP access log. Use `{0}` to set the path. '.
      'Available variables are:',[
            'log.access.path'
        ]);
    $http_desc .= "\n\n";
    $http_desc .= $this->renderMapHelp($http_map);

    $ssh_desc = \Yii::t("app",
      'Format for the SSH access log. Use {0} to set the path. '.
      'Available variables are:', [
            'log.ssh.path'
        ]);
    $ssh_desc .= "\n\n";
    $ssh_desc .= $this->renderMapHelp($ssh_map);

    return array(
      $this->newOption('log.access.path', 'string', null)
        ->setLocked(true)
        ->setSummary(\Yii::t("app", 'Access log location.'))
        ->setDescription(
          \Yii::t("app",
            "To enable the Phabricator access log, specify a path. The ".
            "Phabricator access than normal HTTP access logs (for instance, ".
            "it can show logged-in users, controllers, and other application ".
            "data).\n\n".
            "If not set, no log will be written."))
        ->addExample(
          null,
          \Yii::t("app", 'Disable access log.'))
        ->addExample(
          '/var/log/orangins/access.log',
          \Yii::t("app", 'Write access log here.')),
      $this->newOption(
        'log.access.format',
        // NOTE: This is 'wild' intead of 'string' so "\t" and such can be
        // specified.
        'wild',
        "[%D]\t%p\t%h\t%r\t%u\t%C\t%m\t%a\t%U\t%R\t%c\t%T")
        ->setLocked(true)
        ->setSummary(\Yii::t("app", 'Access log format.'))
        ->setDescription($http_desc),
      $this->newOption('log.ssh.path', 'string', null)
        ->setLocked(true)
        ->setSummary(\Yii::t("app", 'SSH log location.'))
        ->setDescription(
          \Yii::t("app",
            "To enable the Phabricator SSH log, specify a path. The ".
            "access log can provide more detailed information about SSH ".
            "access than a normal SSH log (for instance, it can show ".
            "logged-in users, commands, and other application data).\n\n".
            "If not set, no log will be written."))
        ->addExample(
          null,
          \Yii::t("app", 'Disable SSH log.'))
        ->addExample(
          '/var/log/orangins/ssh.log',
          \Yii::t("app", 'Write SSH log here.')),
      $this->newOption(
        'log.ssh.format',
        'wild',
        "[%D]\t%p\t%h\t%r\t%s\t%S\t%u\t%C\t%U\t%c\t%T\t%i\t%o")
        ->setLocked(true)
        ->setSummary(\Yii::t("app", 'SSH log format.'))
        ->setDescription($ssh_desc),
    );
  }

  private function renderMapHelp(array $map) {
    $desc = '';
    foreach ($map as $key => $kdesc) {
      $desc .= "  - `%".$key."` ".$kdesc."\n";
    }
    $desc .= "\n";
    $desc .= \Yii::t("app",
      "If a variable isn't available (for example, %%m appears in the file ".
      "format but the request is not a Conduit request), it will be rendered ".
      "as '-'");
    $desc .= "\n\n";
    $desc .= \Yii::t("app",
      "Note that the default format is subject to change in the future, so ".
      "if you rely on the log's format, specify it explicitly.");

    return $desc;
  }

}

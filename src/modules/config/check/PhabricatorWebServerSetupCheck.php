<?php
namespace orangins\modules\config\check;

final class PhabricatorWebServerSetupCheck extends PhabricatorSetupCheck {

  public function getDefaultGroup() {
    return self::GROUP_OTHER;
  }

  protected function executeChecks() {
    // The documentation says these headers exist, but it's not clear if they
    // are entirely reliable in practice.
    if (isset($_SERVER['HTTP_X_MOD_PAGESPEED']) ||
        isset($_SERVER['HTTP_X_PAGE_SPEED'])) {
      $this->newIssue('webserver.pagespeed')
        ->setName(\Yii::t("app",'Disable Pagespeed'))
        ->setSummary(\Yii::t("app",'Pagespeed is enabled, but should be disabled.'))
        ->setMessage(
          \Yii::t("app",
            'Phabricator received an "X-Mod-Pagespeed" or "X-Page-Speed" '.
            'HTTP header on this request, which indicates that you have '.
            'enabled "mod_pagespeed" on this server. This module is not '.
            'compatible with Phabricator. You should disable it.'));
    }

    $base_uri = PhabricatorEnv::getEnvConfig('orangins.base-uri');
    if (!strlen($base_uri)) {
      // If `orangins.base-uri` is not set then we can't really do
      // anything.
      return;
    }

    $expect_user = 'alincoln';
    $expect_pass = 'hunter2';

    $send_path = '/test-%252A/';
    $expect_path = '/test-%2A/';

    $expect_key = 'duck-sound';
    $expect_value = 'quack';

    $base_uri = (new PhutilURI($base_uri))
      ->setPath($send_path)
      ->setQueryParam($expect_key, $expect_value);

    $self_future = (new HTTPSFuture($base_uri))
      ->addHeader('X-Phabricator-SelfCheck', 1)
      ->addHeader('Accept-Encoding', 'gzip')
      ->setHTTPBasicAuthCredentials(
        $expect_user,
        new PhutilOpaqueEnvelope($expect_pass))
      ->setTimeout(5);

    // Make a request to the metadata service available on EC2 instances,
    // to test if we're running on a T2 instance in AWS so we can warn that
    // this is a bad idea. Outside of AWS, this request will just fail.
    $ec2_uri = 'http://169.254.169.254/latest/meta-data/instance-type';
    $ec2_future = (new HTTPSFuture($ec2_uri))
      ->setTimeout(1);

    $futures = array(
      $self_future,
      $ec2_future,
    );
    $futures = new FutureIterator($futures);
    foreach ($futures as $future) {
      // Just resolve the futures here.
    }


    try {
      list($body) = $ec2_future->resolvex();
      $body = trim($body);
      if (preg_match('/^t2/', $body)) {
        $message = \Yii::t("app",
          'Phabricator appears to be installed on a very small EC2 instance '.
          '(of class "%s") with burstable CPU. This is strongly discouraged. '.
          'Phabricator regularly needs CPU, and these instances are often '.
          'choked to death by CPU throttling. Use an instance with a normal '.
          'CPU instead.',
          $body);

        $this->newIssue('ec2.burstable')
          ->setName(\Yii::t("app",'Installed on Burstable CPU Instance'))
          ->setSummary(
            \Yii::t("app",
              'Do not install Phabricator on an instance class with '.
              'burstable CPU.'))
          ->setMessage($message);
      }
    } catch (Exception $ex) {
      // If this fails, just continue. We're probably not running in EC2.
    }

    try {
      list($body, $headers) = $self_future->resolvex();
    } catch (Exception $ex) {
      // If this fails for whatever reason, just ignore it. Hopefully, the
      // error is obvious and the user can correct it on their own, but we
      // can't do much to offer diagnostic advice.
      return;
    }

    if (BaseHTTPFuture::getHeader($headers, 'Content-Encoding') != 'gzip') {
      $message = \Yii::t("app",
        'Phabricator sent itself a request with "Accept-Encoding: gzip", '.
        'but received an uncompressed response.'.
        "\n\n".
        'This may indicate that your webserver is not configured to '.
        'compress responses. If so, you should enable compression. '.
        'Compression can dramatically improve performance, especially '.
        'for clients with less bandwidth.');

      $this->newIssue('webserver.gzip')
        ->setName(\Yii::t("app",'GZip Compression May Not Be Enabled'))
        ->setSummary(\Yii::t("app",'Your webserver may have compression disabled.'))
        ->setMessage($message);
    } else {
      if (function_exists('gzdecode')) {
        $body = gzdecode($body);
      } else {
        $body = null;
      }
      if (!$body) {
        // For now, just bail if we can't decode the response.
        // This might need to use the stronger magic in "AphrontRequestStream"
        // to decode more reliably.
        return;
      }
    }

    $structure = null;
    $caught = null;
    $extra_whitespace = ($body !== trim($body));

    if (!$extra_whitespace) {
      try {
        $structure = phutil_json_decode($body);
      } catch (Exception $ex) {
        $caught = $ex;
      }
    }

    if (!$structure) {
      if ($extra_whitespace) {
        $message = \Yii::t("app",
          'Phabricator sent itself a test request and expected to get a bare '.
          'JSON response back, but the response had extra whitespace at '.
          'the beginning or end.'.
          "\n\n".
          'This usually means you have edited a file and left whitespace '.
          'characters before the opening %s tag, or after a closing %s tag. '.
          'Remove any leading whitespace, and prefer to omit closing tags.',
          phutil_tag('tt', array(), '<?php'),
          phutil_tag('tt', array(), '?>'));
      } else {
        $short = (new PhutilUTF8StringTruncator())
          ->setMaximumGlyphs(1024)
          ->truncateString($body);

        $message = \Yii::t("app",
          'Phabricator sent itself a test request with the '.
          '"X-Phabricator-SelfCheck" header and expected to get a valid JSON '.
          'response back. Instead, the response begins:'.
          "\n\n".
          '%s'.
          "\n\n".
          'Something is misconfigured or otherwise mangling responses.',
          phutil_tag('pre', array(), $short));
      }

      $this->newIssue('webserver.mangle')
        ->setName(\Yii::t("app",'Mangled Webserver Response'))
        ->setSummary(\Yii::t("app",'Your webserver produced an unexpected response.'))
        ->setMessage($message);

      // We can't run the other checks if we could not decode the response.
      return;
    }

    $actual_user = ArrayHelper::getValue($structure, 'user');
    $actual_pass = ArrayHelper::getValue($structure, 'pass');
    if (($expect_user != $actual_user) || ($actual_pass != $expect_pass)) {
      $message = \Yii::t("app",
        'Phabricator sent itself a test request with an "Authorization" HTTP '.
        'header, and expected those credentials to be transmitted. However, '.
        'they were absent or incorrect when received. Phabricator sent '.
        'username "%s" with password "%s"; received username "%s" and '.
        'password "%s".'.
        "\n\n".
        'Your webserver may not be configured to forward HTTP basic '.
        'authentication. If you plan to use basic authentication (for '.
        'example, to access repositories) you should reconfigure it.',
        $expect_user,
        $expect_pass,
        $actual_user,
        $actual_pass);

      $this->newIssue('webserver.basic-auth')
        ->setName(\Yii::t("app",'HTTP Basic Auth Not Configured'))
        ->setSummary(\Yii::t("app",'Your webserver is not forwarding credentials.'))
        ->setMessage($message);
    }

    $actual_path = ArrayHelper::getValue($structure, 'path');
    if ($expect_path != $actual_path) {
      $message = \Yii::t("app",
        'Phabricator sent itself a test request with an unusual path, to '.
        'test if your webserver is rewriting paths correctly. The path was '.
        'not transmitted correctly.'.
        "\n\n".
        'Phabricator sent a request to path "%s", and expected the webserver '.
        'to decode and rewrite that path so that it received a request for '.
        '"%s". However, it received a request for "%s" instead.'.
        "\n\n".
        'Verify that your rewrite rules are configured correctly, following '.
        'the instructions in the documentation. If path encoding is not '.
        'working properly you will be unable to access files with unusual '.
        'names in repositories, among other issues.'.
        "\n\n".
        '(This problem can be caused by a missing "B" in your RewriteRule.)',
        $send_path,
        $expect_path,
        $actual_path);

      $this->newIssue('webserver.rewrites')
        ->setName(\Yii::t("app",'HTTP Path Rewriting Incorrect'))
        ->setSummary(\Yii::t("app",'Your webserver is rewriting paths improperly.'))
        ->setMessage($message);
    }

    $actual_key = \Yii::t("app",'<none>');
    $actual_value = \Yii::t("app",'<none>');
    foreach (ArrayHelper::getValue($structure, 'params', array()) as $pair) {
      if (ArrayHelper::getValue($pair, 'name') == $expect_key) {
        $actual_key = ArrayHelper::getValue($pair, 'name');
        $actual_value = ArrayHelper::getValue($pair, 'value');
        break;
      }
    }

    if (($expect_key !== $actual_key) || ($expect_value !== $actual_value)) {
      $message = \Yii::t("app",
        'Phabricator sent itself a test request with an HTTP GET parameter, '.
        'but the parameter was not transmitted. Sent "%s" with value "%s", '.
        'got "%s" with value "%s".'.
        "\n\n".
        'Your webserver is configured incorrectly and large parts of '.
        'Phabricator will not work until this issue is corrected.'.
        "\n\n".
        '(This problem can be caused by a missing "QSA" in your RewriteRule.)',
        $expect_key,
        $expect_value,
        $actual_key,
        $actual_value);

      $this->newIssue('webserver.parameters')
        ->setName(\Yii::t("app",'HTTP Parameters Not Transmitting'))
        ->setSummary(
          \Yii::t("app",'Your webserver is not handling GET parameters properly.'))
        ->setMessage($message);
    }

  }

}

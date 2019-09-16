<?php
namespace orangins\modules\config\option;

final class PhabricatorDeveloperConfigOptions
  extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return \Yii::t("app", 'Developer / Debugging');
  }

  public function getDescription() {
    return \Yii::t("app", 'Options for Phabricator developers, including debugging.');
  }

  public function getIcon() {
    return 'icon-make-group';
  }

  public function getGroup() {
    return 'core';
  }

  public function getOptions() {
    return array(
      $this->newOption('darkconsole.enabled', 'bool', false)
        ->setBoolOptions(
          array(
            \Yii::t("app", 'Enable DarkConsole'),
            \Yii::t("app", 'Disable DarkConsole'),
          ))
        ->setSummary(\Yii::t("app", "Enable Phabricator's debugging console."))
        ->setDescription(
          \Yii::t("app",
            "DarkConsole is a development and profiling tool built into ".
            "Phabricator's web interface. You should leave it disabled unless ".
            "you are developing or debugging Phabricator.\n\n".
            "Once you activate DarkConsole for the install, **you need to ".
            "enable it for your account before it will actually appear on ".
            "pages.** You can do this in Settings > Developer Settings.\n\n".
            "DarkConsole exposes potentially sensitive data (like queries, ".
            "stack traces, and configuration) so you generally should not ".
            "turn it on in production.")),
      $this->newOption('darkconsole.always-on', 'bool', false)
        ->setBoolOptions(
          array(
            \Yii::t("app", 'Always Activate DarkConsole'),
            \Yii::t("app", 'Require DarkConsole Activation'),
          ))
        ->setSummary(\Yii::t("app", 'Activate DarkConsole on every page.'))
        ->setDescription(
          \Yii::t("app",
            "This option allows you to enable DarkConsole on every page, ".
            "even for logged-out users. This is only really useful if you ".
            "need to debug something on a logged-out page. You should not ".
            "enable this option in production.\n\n".
            "You must enable DarkConsole by setting '%s' ".
            "before this option will have any effect.",
            'darkconsole.enabled')),
      $this->newOption('debug.time-limit', 'int', null)
        ->setSummary(
          \Yii::t("app",
            'Limit page execution time to debug hangs.'))
        ->setDescription(
          \Yii::t("app",
            "This option can help debug pages which are taking a very ".
            "long time (more than 30 seconds) to render.\n\n".
            "If a page is slow to render (but taking less than 30 seconds), ".
            "the best tools to use to figure out why it is slow are usually ".
            "the DarkConsole service call profiler and XHProf.\n\n".
            "However, if a request takes a very long time to return, some ".
            "components (like Apache, nginx, or PHP itself) may abort the ".
            "request before it finishes. This can prevent you from using ".
            "profiling tools to understand page performance in detail.\n\n".
            "In these cases, you can use this option to force the page to ".
            "abort after a smaller number of seconds (for example, 10), and ".
            "dump a useful stack trace. This can provide useful information ".
            "about why a page is hanging.\n\n".
            "To use this option, set it to a small number (like 10), and ".
            "reload a hanging page. The page should exit after 10 seconds ".
            "and give you a stack trace.\n\n".
            "You should turn this option off (set it to 0) when you are ".
            "done with it. Leaving it on creates a small amount of overhead ".
            "for all requests, even if they do not hit the time limit.")),
      $this->newOption('debug.stop-on-redirect', 'bool', false)
        ->setBoolOptions(
          array(
            \Yii::t("app", 'Stop Before HTTP Redirect'),
            \Yii::t("app", 'Use Normal HTTP Redirects'),
          ))
        ->setSummary(
          \Yii::t("app",
            'Confirm before redirecting so DarkConsole can be examined.'))
        ->setDescription(
          \Yii::t("app",
            'Normally, Phabricator issues HTTP redirects after a successful '.
            'POST. This can make it difficult to debug things which happen '.
            'while processing the POST, because service and profiling '.
            'information are lost. By setting this configuration option, '.
            'Phabricator will show a page instead of automatically '.
            'redirecting, allowing you to examine service and profiling '.
            'information. It also makes the UX awful, so you should only '.
            'enable it when debugging.')),
      $this->newOption('debug.profile-rate', 'int', 0)
        ->addExample(0,     \Yii::t("app", 'No profiling'))
        ->addExample(1,     \Yii::t("app", 'Profile every request (slow)'))
        ->addExample(1000,  \Yii::t("app", 'Profile 0.1%% of all requests'))
        ->setSummary(\Yii::t("app", 'Automatically profile some percentage of pages.'))
        ->setDescription(
          \Yii::t("app",
            "Normally, Phabricator profiles pages only when explicitly ".
            "requested via DarkConsole. However, it may be useful to profile ".
            "some pages automatically.\n\n".
            "Set this option to a positive integer N to profile 1 / N pages ".
            "automatically. For example, setting it to 1 will profile every ".
            "page, while setting it to 1000 will profile 1 page per 1000 ".
            "requests (i.e., 0.1%% of requests).\n\n".
            "Since profiling is slow and generates a lot of data, you should ".
            "set this to 0 in production (to disable it) or to a large number ".
            "(to collect a few samples, if you're interested in having some ".
            "data to look at eventually). In development, it may be useful to ".
            "set it to 1 in order to debug performance problems.\n\n".
            "NOTE: You must install XHProf for profiling to work.")),
      $this->newOption('debug.sample-rate', 'int', 1000)
        ->setLocked(true)
        ->addExample(0, \Yii::t("app", 'No performance sampling.'))
        ->addExample(1, \Yii::t("app", 'Sample every request (slow).'))
        ->addExample(1000, \Yii::t("app", 'Sample 0.1%% of requests.'))
        ->setSummary(\Yii::t("app", 'Automatically sample some fraction of requests.'))
        ->setDescription(
          \Yii::t("app",
            "The Multimeter application collects performance samples. You ".
            "can use this data to help you understand what Phabricator is ".
            "spending time and resources doing, and to identify problematic ".
            "access patterns.".
            "\n\n".
            "This option controls how frequently sampling activates. Set it ".
            "to some positive integer N to sample every 1 / N pages.".
            "\n\n".
            "For most installs, the default value (1 sample per 1000 pages) ".
            "should collect enough data to be useful without requiring much ".
            "storage or meaningfully impacting performance. If you're ".
            "investigating performance issues, you can adjust the rate ".
            "in order to collect more data.")),
      $this->newOption('phabricator.developer-mode', 'bool', false)
        ->setBoolOptions(
          array(
            \Yii::t("app", 'Enable developer mode'),
            \Yii::t("app", 'Disable developer mode'),
          ))
          ->setSummary(\Yii::t("app", 'Enable verbose error reporting and disk reads.'))
          ->setDescription(
            \Yii::t("app",
              'This option enables verbose error reporting (stack traces, '.
              'error callouts) and forces disk reads of static assets on '.
              'every reload.')),
      $this->newOption('celerity.minify', 'bool', true)
        ->setBoolOptions(
          array(
            \Yii::t("app", 'Minify static resources.'),
            \Yii::t("app", "Don't minify static resources."),
          ))
        ->setSummary(\Yii::t("app", 'Minify static Celerity resources.'))
        ->setDescription(
          \Yii::t("app",
            'Minify static resources by removing whitespace and comments. You '.
            'should enable this in production, but disable it in '.
            'development.')),
      $this->newOption('cache.enable-deflate', 'bool', true)
        ->setBoolOptions(
          array(
            \Yii::t("app", 'Enable deflate compression'),
            \Yii::t("app", 'Disable deflate compression'),
          ))
        ->setSummary(
          \Yii::t("app", 'Toggle %s-based compression for some caches.', 'gzdeflate()'))
        ->setDescription(
          \Yii::t("app",
            'Set this to false to disable the use of %s-based '.
            'compression in some caches. This may give you less performant '.
            '(but more debuggable) caching.',
            'gzdeflate()')),
    );
  }
}

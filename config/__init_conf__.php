<?php

function orangins_read_config_file($original_config)
{
    $root = dirname(dirname(__FILE__));

    // Accept either "myconfig" (preferred) or "myconfig.conf.php".
    $config = preg_replace('/\.conf\.php$/', '', $original_config);
    $full_config_path = $root . '/config/' . $config . '.conf.php';

    if (!Filesystem::pathExists($full_config_path)) {
        // These are very old configuration files which we used to ship with
        // by default. File based configuration was de-emphasized once web-based
        // configuration was built. The actual files were removed to reduce
        // user confusion over how to configure Phabricator.

        switch ($config) {
            case 'default':
            case 'production':
                return array();
            case 'development':
                return array(
                    'phabricator.developer-mode' => true,
                    'darkconsole.enabled' => true,
                    'celerity.minify' => false,
                );
        }

        $files = (new FileFinder($root . '/config/'))
            ->withType('f')
            ->withSuffix('conf.php')
            ->withFollowSymlinks(true)
            ->find();

        foreach ($files as $key => $file) {
            $file = trim($file, './');
            $files[$key] = preg_replace('/\.conf\.php$/', '', $file);
        }
        $files = '    ' . implode("\n    ", $files);

        throw new Exception(
            \Yii::t("app",
                "CONFIGURATION ERROR\n" .
                "Config file '{0}' does not exist. Valid config files are:\n\n{1}",
                [
                    $original_config,
                    $files
                ]));
    }

    // Make sure config file errors are reported.
    $old_error_level = error_reporting(E_ALL | E_STRICT);
    $old_display_errors = ini_get('display_errors');
    ini_set('display_errors', 1);

    ob_start();
    $conf = include $full_config_path;
    $errors = ob_get_clean();

    error_reporting($old_error_level);
    ini_set('display_errors', $old_display_errors);

    if ($conf === false) {
        throw new Exception(
            \Yii::t("app",
                "Failed to read config file '{0}': {1}",
                [
                    $config,
                    $errors
                ]));
    }

    return $conf;
}

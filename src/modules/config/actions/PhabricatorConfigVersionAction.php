<?php

namespace orangins\modules\config\actions;

use ExecFuture;
use Filesystem;
use FutureIterator;
use orangins\lib\helpers\OranginsViewUtil;
use orangins\lib\view\phui\PHUIPropertyListView;
use orangins\lib\view\phui\PHUITwoColumnView;
use orangins\modules\people\models\PhabricatorUser;
use PhutilBinaryAnalyzer;
use PhutilBootloader;
use yii\helpers\ArrayHelper;

/**
 * Class PhabricatorConfigVersionAction
 * @package orangins\modules\config\actions
 * @author 陈妙威
 */
final class PhabricatorConfigVersionAction
    extends PhabricatorConfigAction
{

    /**
     * @return \orangins\lib\view\page\PhabricatorStandardPageView
     * @throws \FilesystemException
     * @throws \PhutilMethodNotImplementedException
     * @throws \PhutilProxyException
     * @throws \ReflectionException
     * @throws \yii\base\Exception
     * @throws \Exception
     * @author 陈妙威
     */
    public function run()
    {
        $request = $this->getRequest();
        $viewer = $request->getViewer();

        $title = \Yii::t("app", 'Version Information');
        $versions = $this->renderModuleStatus($viewer);

        $nav = $this->buildSideNavView();
        $nav->selectFilter('version/');
        $header = $this->buildHeaderView($title);

        $view = $this->buildConfigBoxView(\Yii::t("app", 'Installed Versions'), $versions);

        $crumbs = $this->buildApplicationCrumbs()
            ->addTextCrumb($title)
            ->setBorder(true);

        $content = (new PHUITwoColumnView())
            ->setNavigation($nav)
            ->setFixed(true)
            ->setMainColumn($view);

        return $this->newPage()
            ->setHeader($header)
            ->setTitle($title)
            ->setCrumbs($crumbs)
            ->appendChild($content);

    }

    /**
     * @param $viewer
     * @return PHUIPropertyListView
     * @throws \FilesystemException
     * @throws \PhutilProxyException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    public function renderModuleStatus($viewer)
    {
        $versions = $this->loadVersions($viewer);

        $version_property_list = (new PHUIPropertyListView());
        $version_property_list->addClass("m-3");
        foreach ($versions as $name => $info) {
            $version = $info['version'];

            if ($info['branchpoint']) {
                $display = \Yii::t("app",
                    '{0} (branched from {1} on {2})',
                    [
                        $version,
                        $info['branchpoint'],
                        $info['upstream']
                    ]);
            } else {
                $display = $version;
            }

            $version_property_list->addProperty($name, $display);
        }

        $version_path = \Yii::getAlias(\Yii::$app->configPath) . '/local/VERSION';
        if (Filesystem::pathExists($version_path)) {
            $version_from_file = Filesystem::readFile($version_path);
            $version_property_list->addProperty(
                \Yii::t("app", 'Local Version'),
                $version_from_file);
        }

        /** @var PhutilBinaryAnalyzer[] $binaries */
        $binaries = PhutilBinaryAnalyzer::getAllBinaries();
        foreach ($binaries as $binary) {
            if (!$binary->isBinaryAvailable()) {
                $binary_info = \Yii::t("app", 'Not Available');
            } else {
                $version = $binary->getBinaryVersion();
                $path = $binary->getBinaryPath();
                if ($path === null && $version === null) {
                    $binary_info = \Yii::t("app", '-');
                } else if ($path === null) {
                    $binary_info = $version;
                } else if ($version === null) {
                    $binary_info = \Yii::t("app", '- at {0}', [
                        $path
                    ]);
                } else {
                    $binary_info = \Yii::t("app", '{0} at {1}', [
                        $version, $path
                    ]);
                }
            }

            $version_property_list->addProperty(
                $binary->getBinaryName(),
                $binary_info);
        }

        return $version_property_list;
    }

    /**
     * @param PhabricatorUser $viewer
     * @return array
     * @throws \PhutilProxyException
     * @throws \ReflectionException
     * @author 陈妙威
     */
    private function loadVersions(PhabricatorUser $viewer)
    {
        $specs = array(
            'orangins',
//            'arcanist',
            'phutil',
        );

        $all_libraries = PhutilBootloader::getInstance()->getAllLibraries();
        // This puts the core libraries at the top:
        $other_libraries = array_diff($all_libraries, $specs);
        $specs = array_merge($specs, $other_libraries);

        $log_futures = array();
        $remote_futures = array();

        foreach ($specs as $lib) {
            $root = dirname(phutil_get_library_root($lib));

            $log_command = csprintf(
                'git log --format=%s -n 1 --',
                '%H %ct');

            $remote_command = csprintf(
                'git remote -v');

            $log_futures[$lib] = (new ExecFuture('%C', $log_command))
                ->setCWD($root);

            $remote_futures[$lib] = (new ExecFuture('%C', $remote_command))
                ->setCWD($root);
        }

        $all_futures = array_merge($log_futures, $remote_futures);

        (new FutureIterator($all_futures))
            ->resolveAll();

        // A repository may have a bunch of remotes, but we're only going to look
        // for remotes we host to try to figure out where this repository branched.
        $upstream_pattern = '(github\.com/phacility/|secure\.phabricator\.com/)';

        $upstream_futures = array();
        $lib_upstreams = array();
        foreach ($specs as $lib) {
            $remote_future = $remote_futures[$lib];

            list($err, $stdout) = $remote_future->resolve();
            if ($err) {
                // If this fails for whatever reason, just move on.
                continue;
            }

            // These look like this, with a tab separating the first two fields:
            // remote-name     http://remote.uri/ (push)

            $upstreams = array();

            $remotes = phutil_split_lines($stdout, false);
            foreach ($remotes as $remote) {
                $remote_pattern = '/^([^\t]+)\t([^ ]+) \(([^)]+)\)\z/';
                $matches = null;
                if (!preg_match($remote_pattern, $remote, $matches)) {
                    continue;
                }

                // Remote URIs are either "push" or "fetch": we only care about "fetch"
                // URIs.
                $type = $matches[3];
                if ($type != 'fetch') {
                    continue;
                }

                $uri = $matches[2];
                $is_upstream = preg_match($upstream_pattern, $uri);
                if (!$is_upstream) {
                    continue;
                }

                $name = $matches[1];
                $upstreams[$name] = $name;
            }

            // If we have several suitable upstreams, try to pick the one named
            // "origin", if it exists. Otherwise, just pick the first one.
            if (isset($upstreams['origin'])) {
                $upstream = $upstreams['origin'];
            } else if ($upstreams) {
                $upstream = head($upstreams);
            } else {
                $upstream = null;
            }

            if (!$upstream) {
                continue;
            }

            $lib_upstreams[$lib] = $upstream;

            $merge_base_command = csprintf(
                'git merge-base HEAD %s/master --',
                $upstream);

            $root = dirname(phutil_get_library_root($lib));

            $upstream_futures[$lib] = (new ExecFuture('%C', $merge_base_command))
                ->setCWD($root);
        }

        if ($upstream_futures) {
            (new FutureIterator($upstream_futures))
                ->resolveAll();
        }

        $results = array();
        foreach ($log_futures as $lib => $future) {
            list($err, $stdout) = $future->resolve();
            if (!$err) {
                list($hash, $epoch) = explode(' ', $stdout);
                $version = \Yii::t("app", '{0} ({1})', [
                    $hash, OranginsViewUtil::phabricator_date($epoch, $viewer)
                ]);
            } else {
                $version = \Yii::t("app", 'Unknown');
            }

            $result = array(
                'version' => $version,
                'upstream' => null,
                'branchpoint' => null,
            );

            $upstream_future = ArrayHelper::getValue($upstream_futures, $lib);
            if ($upstream_future) {
                list($err, $stdout) = $upstream_future->resolve();
                if (!$err) {
                    $branchpoint = trim($stdout);
                    if (strlen($branchpoint)) {
                        // We only list a branchpoint if it differs from HEAD.
                        if ($branchpoint != $hash) {
                            $result['upstream'] = $lib_upstreams[$lib];
                            $result['branchpoint'] = trim($stdout);
                        }
                    }
                }
            }

            $results[$lib] = $result;
        }

        return $results;
    }

}

/**
 * @provides javelin-behavior-phui-file-upload
 * @requires javelin-behavior
 *           javelin-stratcom
 *           javelin-dom
 *           phuix-dropdown-menu
 */

JX.behavior('phui-file-upload-ajax', function (config) {
    (new JX.PhabricatorFileUploadControl(config))
        .show();
});

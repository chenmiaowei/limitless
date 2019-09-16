/**
 * @provides javelin-behavior-phabricator-tooltips
 * @requires javelin-behavior
 *           javelin-behavior-device
 *           javelin-stratcom
 *           phabricator-tooltip
 * @javelin
 */

JX.behavior('phabricator-daterangepicker', function (config) {
    $(function () {
        $("#" + config.id).daterangepicker(config.options ? config.options : {});
    })
});

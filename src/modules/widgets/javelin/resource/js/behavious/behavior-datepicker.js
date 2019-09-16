/**
 * @provides javelin-behavior-phabricator-tooltips
 * @requires javelin-behavior
 *           javelin-behavior-device
 *           javelin-stratcom
 *           phabricator-tooltip
 * @javelin
 */

JX.behavior('phabricator-datepicker', function (config) {
    $("#" + config.id).pickadate({
        weekdaysShort: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
        showMonthsShort: true,
        format: 'yyyy-mm-dd',
        formatSubmit: 'yyyy-mm-dd'
    })
});

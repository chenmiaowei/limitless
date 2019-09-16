/**
 * @provides javelin-behavior-choose-control
 * @requires javelin-behavior
 *           javelin-stratcom
 *           javelin-dom
 *           javelin-workflow
 */

JX.behavior('uniform-group-control', function (config) {
    if (!$().uniform) {
        console.warn('Warning - uniform.min.js is not loaded.');
        return;
    }
    var options = config.options ? config.options : {};
    $('#' + config.id).uniform(options);

    var root;
    var ontoggle = function (e) {
        var box = e.getTarget();
        root = e.getNode('phabricator-uniform-group-control');
        var find = JX.DOM.find(root, 'input', 'date-input');
        find.disabled = !box.checked;
    };

    JX.Stratcom.listen('change', 'calendar-enable', ontoggle);
});

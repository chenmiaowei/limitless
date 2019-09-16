/**
 * @provides javelin-behavior-choose-control
 * @requires javelin-behavior
 *           javelin-stratcom
 *           javelin-dom
 *           javelin-workflow
 */

JX.behavior('uniform-control', function(config) {
    if (!$().uniform) {
        console.warn('Warning - uniform.min.js is not loaded.');
        return;
    }
    var options = config.options ?  config.options : {};
    $('#' + config.id).uniform(options);
});

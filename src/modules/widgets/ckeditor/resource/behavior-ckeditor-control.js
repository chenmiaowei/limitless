/**
 * @provides javelin-behavior-choose-control
 * @requires javelin-behavior
 *           javelin-stratcom
 *           javelin-dom
 *           javelin-workflow
 */

JX.behavior('ckeditor-control', function (config) {
    CKEDITOR.tools.getCsrfToken = function() {
        return  $('meta[name=csrf-token]').attr('content');
    };
    CKEDITOR.replace( config.name, config.options);
});

/**
 * @provides javelin-behavior-aphront-basic-tokenizer
 * @requires javelin-behavior
 *           phabricator-prefab
 */

JX.behavior('select2', function (config) {
    $("#" + config.id).select2(config.options);
});

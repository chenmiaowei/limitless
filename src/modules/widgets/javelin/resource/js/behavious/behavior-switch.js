/**
 * @provides javelin-behavior-aphront-basic-tokenizer
 * @requires javelin-behavior
 *           phabricator-prefab
 */

JX.behavior('phabricator-switch', function (config) {
    var switchery = new Switchery($("#" + config.id + ' .form-check-input-switchery').get(0), config.options);
});

/**
 * @provides javelin-behavior-phabricator-tooltips
 * @requires javelin-behavior
 *           javelin-behavior-device
 *           javelin-stratcom
 *           phabricator-tooltip
 * @javelin
 */

JX.behavior('phabricator-sidebar-toggle', function (config) {
    JX.Stratcom.listen(
        ['click'],
        'sidebar-main-toggle',
        function (e) {
            var has = $('body').hasClass('sidebar-xs');
            var data;
            if(has) {
                data = {sidebar_toggle: 1};
            } else {
                data = {sidebar_toggle: 0};
            }
            data[$('meta[name=csrf-param]').attr('content')] = $('meta[name=csrf-token]').attr('content');
            new JX.Request(config.update_uri, JX.bag)
                .setData(data)
                .send();
        });
});

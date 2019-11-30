/**
 * @provides javelin-behavior-aphront-basic-tokenizer
 * @requires javelin-behavior
 *           phabricator-prefab
 */

JX.behavior('text-captcha', function (config) {
    let countdowns = {};
    let button = JX.$(config.buttonId);
    let text = JX.$(config.textId);


    let liftOff = function () {
        countdowns[config.buttonId] = false;
        $('#' + config.buttonId + ' .phui-button-text').text(config.buttonText);
    }
    let onresposne = function () {
        if (!/[\d]{11}/.test(text.value.trim())) {
            new JX.Notification()
                .alterClassName('jx-notification-alert', true)
                .setContent('请输入正确的手机号')
                .setDuration(2000)
                .show();
        } else {
            if (countdowns[config.buttonId]) {
                return;
            } else {
                countdowns[config.buttonId] = true;
                $('#' + config.buttonId + ' .phui-button-text').countdown('destroy').countdown({
                    until: 60, format: 'S',
                    onExpiry: liftOff
                    // onTick: ctrl.everyOne, onExpiry: ctrl.liftOff, tickInterval: 1
                });
            }
        }

    }
    JX.DOM.listen(button, 'click', null, function (e) {
        var request = new JX.Request(config.url, onresposne);

        var list_of_pairs = [];
        list_of_pairs.push([$('meta[name=csrf-param]').attr('content'), $('meta[name=csrf-token]').attr('content')]);
        request.setDataWithListOfPairs(list_of_pairs);
        request.send();
    });
});

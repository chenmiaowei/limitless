/**
 * @provides javelin-behavior-device
 * @requires javelin-behavior
 *           javelin-stratcom
 *           javelin-dom
 *           javelin-vector
 *           javelin-install
 */

JX.install('Device', {
    statics: {
        _device: null,
        _tabletBreakpoint: 920,

        setTabletBreakpoint: function (width) {
            var self = JX.Device;
            self._tabletBreakpoint = width;
            self.recalculate();
        },

        getTabletBreakpoint: function () {
            return JX.Device._tabletBreakpoint;
        },

        recalculate: function () {
            var v = JX.Vector.getViewport();
            var self = JX.Device;

            // Even when we emit a '<meta name="viewport" ... />' tag which tells
            // devices to fit the content to the screen width, we'll sometimes measure
            // a viewport dimension which is larger than the available screen width,
            // particularly if we check too early.

            // If the device provides a screen width and the screen width is smaller
            // than the viewport width, use the screen width.

            var screen_width = (window.screen && window.screen.availWidth);
            if (screen_width) {
                v.x = Math.min(v.x, screen_width);
            }

            var device = 'desktop';
            if (v.x <= self._tabletBreakpoint) {
                device = 'tablet';
            }
            if (v.x <= 480) {
                device = 'phone';
            }

            if (device == self._device) {
                return;
            }

            self._device = device;

            var e = document.body;
            this.alterClass(e, 'device-phone', (device == 'phone'));
            this.alterClass(e, 'device-tablet', (device == 'tablet'));
            this.alterClass(e, 'device-desktop', (device == 'desktop'));
            this.alterClass(e, 'device', (device != 'desktop'));

            JX.Stratcom.invoke('phabricator-device-change', null, device);
        },


        alterClass: function (node, className, add) {
            if (__DEV__) {
                if (add !== false && add !== true) {
                    JX.$E(
                        'JX.DOM.alterClass(...): ' +
                        'expects the third parameter to be Boolean: ' +
                        add + ' was provided');
                }
            }

            var has = ((' ' + node.className + ' ').indexOf(' ' + className + ' ') > -1);
            if (add && !has) {
                node.className += ' ' + className;
            } else if (has && !add) {
                node.className = node.className.replace(
                    new RegExp('(^|\\s)' + className + '(?:\\s|$)', 'g'), ' ').trim();
            }
        },

        isDesktop: function () {
            var self = JX.Device;
            return (self.getDevice() == 'desktop');
        },

        getDevice: function () {
            var self = JX.Device;
            if (self._device === null) {
                self.recalculate();
            }
            return self._device;
        }
    }
});

JX.behavior('device', function () {
    JX.Stratcom.listen('resize', null, JX.Device.recalculate);
    JX.Device.recalculate();
});

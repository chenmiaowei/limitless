/**
 * @provides javelin-behavior-dashboard-query-panel-select
 * @requires javelin-behavior
 *           javelin-dom
 */

/**
 * When editing a "Query" panel on dashboards, make the "Query" selector control
 * dynamically update in response to changes to the "Engine" selector control.
 */


JX.behavior('dashboard-chart-panel-select', function (config) {
    var app_control = JX.$(config.inputID);
    var query_control = JX.$(config.chartID);

    // When the user changes the selected search engine, update the query
    // control to show available queries for that engine.
    function update() {
        var app = app_control.value;
        if(app !== config.type) {
            query_control.style.display = 'none';
        } else {
            query_control.style.display = 'flex';
        }
    }
    JX.DOM.listen(app_control, 'change', null, function () {
        update();
    });
    update();

});

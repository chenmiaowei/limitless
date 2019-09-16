/**
 * @provides javelin-behavior-aphront-basic-tokenizer
 * @requires javelin-behavior
 *           phabricator-prefab
 */

JX.behavior('orangins-echart', function (config) {

    if (typeof echarts == 'undefined') {
        console.warn('Warning - echarts.min.js is not loaded.');
        return;
    }
    var line_zoom_element = document.getElementById(config.id);

    // Initialize chart
    var line_zoom = echarts.init(line_zoom_element);
    //
    // Chart config
    //

    var series = [];
    for(var i = 0; i < config.data.length; i++) {
        series.push({
            name: config.titles[i],
            type: 'line',
            smooth: true,
            symbolSize: 6,
            itemStyle: {
                normal: {
                    borderWidth: 2
                }
            },
            data: config.data[i]
        })
    }

    // Options
    line_zoom.setOption({

        // Define colors
        color: config.colors,

        // Global text styles
        textStyle: {
            fontFamily: 'Roboto, Arial, Verdana, sans-serif',
            fontSize: 13
        },

        // Chart animation duration
        animationDuration: 750,

        // Setup grid
        grid: {
            left: 0,
            right: 40,
            top: 35,
            bottom: 60,
            containLabel: true
        },

        // Add legend
        legend: {
            data: config.titles,
            itemHeight: 8,
            itemGap: 20
        },

        // Add tooltip
        tooltip: {
            trigger: 'axis',
            backgroundColor: 'rgba(0,0,0,0.75)',
            padding: [10, 15],
            textStyle: {
                fontSize: 13,
                fontFamily: 'Roboto, sans-serif'
            }
        },

        // Horizontal axis
        xAxis: [{
            type: 'category',
            boundaryGap: false,
            axisLabel: {
                color: '#333'
            },
            axisLine: {
                lineStyle: {
                    color: '#999'
                }
            },
            data: config.keys
        }],

        // Vertical axis
        yAxis: [{
            type: 'value',
            axisLabel: {
                formatter: '{value} ',
                color: '#333'
            },
            axisLine: {
                lineStyle: {
                    color: '#999'
                }
            },
            splitLine: {
                lineStyle: {
                    color: ['#eee']
                }
            },
            splitArea: {
                show: true,
                areaStyle: {
                    color: ['rgba(250,250,250,0.1)', 'rgba(0,0,0,0.01)']
                }
            }
        }],

        // Zoom control
        dataZoom: [
            {
                type: 'inside',
                start: 30,
                end: 70
            },
            {
                show: true,
                type: 'slider',
                start: 30,
                end: 70,
                height: 40,
                bottom: 0,
                borderColor: '#ccc',
                fillerColor: 'rgba(0,0,0,0.05)',
                handleStyle: {
                    color: '#585f63'
                }
            }
        ],

        // Add series
        series: series
    });
});

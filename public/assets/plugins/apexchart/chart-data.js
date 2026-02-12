'use strict';

$(document).ready(function () {

    function generateData(baseval, count, yrange) {
        var i = 0;
        var series = [];
        while (i < count) {
            var x = Math.floor(Math.random() * (750 - 1 + 1)) + 1;;
            var y = Math.floor(Math.random() * (yrange.max - yrange.min + 1)) + yrange.min;
            var z = Math.floor(Math.random() * (75 - 15 + 1)) + 15;

            series.push([x, y, z]);
            baseval += 86400000;
            i++;
        }
        return series;
    }


    // Column chart
    if ($('#sales_chart').length > 0) {
        var columnCtx = document.getElementById("sales_chart"),
            columnConfig = {
                colors: ['#7638ff', '#fda600'],
                series: [
                    {
                        name: "Received",
                        type: "column",
                        data: [70, 150, 80, 180, 150, 175, 201, 60, 200, 120, 190, 160, 50]
                    },
                    {
                        name: "Pending",
                        type: "column",
                        data: [23, 42, 35, 27, 43, 22, 17, 31, 22, 22, 12, 16, 80]
                    }
                ],
                chart: {
                    type: 'bar',
                    fontFamily: 'Poppins, sans-serif',
                    height: 350,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '60%',
                        endingShape: 'rounded'
                    },
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                },
                yaxis: {
                    title: {
                        text: '$ (thousands)'
                    }
                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return "$ " + val + " thousands"
                        }
                    }
                }
            };
        var columnChart = new ApexCharts(columnCtx, columnConfig);
        columnChart.render();
    }

    //Pie Chart
    if ($('#invoice_chart').length > 0) {
        var pieCtx = document.getElementById("invoice_chart"),
            pieConfig = {
                colors: ['#03C95A', '#E70D0D', '#AB47BC', '#FFC107'],
                series: [45, 15, 21, 5],
                chart: {
                    fontFamily: 'Poppins, sans-serif',
                    height: 150,
                    type: 'donut',
                    offsetX: -30,
                },
                labels: ['Paid', 'Overdue', 'Pending', 'Draft'],
                legend: { show: true },
                dataLabels: {
                    enabled: false // Disable the data labels
                },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '2px',
                                },
                                value: {
                                    show: true,
                                    fontSize: '12px',
                                    formatter: function (val) {
                                        return val + "%";
                                    }
                                },
                                total: {
                                    show: true,
                                    showAlways: true,
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => {
                                            return 45;
                                        }, 0);
                                    },
                                    label: 'Paid'
                                }
                            }
                        }
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 300
                        },
                        legend: {
                            position: 'right'
                        }
                    }
                }]
            };
        var pieChart = new ApexCharts(pieCtx, pieConfig);
        pieChart.render();
    }


    // Simple Line
    if ($('#s-line').length > 0) {
        var sline = {
            chart: {
                height: 350,
                type: 'line',
                zoom: {
                    enabled: false
                },
                toolbar: {
                    show: false,
                }
            },
            colors: ['#F26522'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'straight'
            },
            series: [{
                name: "Desktops",
                data: [10, 41, 35, 51, 49, 62, 69, 91, 148]
            }],
            title: {
                text: 'Product Trends by Month',
                align: 'left'
            },
            grid: {
                row: {
                    colors: ['#f1f2f3', 'transparent'], // takes an array which will be repeated on columns
                    opacity: 0.5
                },
            },
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
            }
        }

        var chart = new ApexCharts(
            document.querySelector("#s-line"),
            sline
        );

        chart.render();
    }


    // Simple Line Area
    if ($('#s-line-area').length > 0) {
        var sLineArea = {
            chart: {
                height: 350,
                type: 'area',
                toolbar: {
                    show: false,
                }
            },
            colors: ['#F26522', '#888ea8'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth'
            },
            series: [{
                name: 'series1',
                data: [31, 40, 28, 51, 42, 109, 100]
            }, {
                name: 'series2',
                data: [11, 32, 45, 32, 34, 52, 41]
            }],

            xaxis: {
                type: 'datetime',
                categories: ["2018-09-19T00:00:00", "2018-09-19T01:30:00", "2018-09-19T02:30:00", "2018-09-19T03:30:00", "2018-09-19T04:30:00", "2018-09-19T05:30:00", "2018-09-19T06:30:00"],
            },
            tooltip: {
                x: {
                    format: 'dd/MM/yy HH:mm'
                },
            }
        }

        var chart = new ApexCharts(
            document.querySelector("#s-line-area"),
            sLineArea
        );

        chart.render();
    }

    // Simple Column
    if ($('#s-col').length > 0) {
        var sCol = {
            chart: {
                height: 350,
                type: 'bar',
                toolbar: {
                    show: false,
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                },
            },
            colors: ['#F26522', '#4361ee'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            series: [{
                name: 'Net Profit',
                data: [44, 55, 57, 56, 61, 58, 63, 60, 66]
            }, {
                name: 'Revenue',
                data: [76, 85, 101, 98, 87, 105, 91, 114, 94]
            }],
            xaxis: {
                categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
            },
            yaxis: {
                title: {
                    text: '$ (thousands)'
                }
            },
            fill: {
                opacity: 1

            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "$ " + val + " thousands"
                    }
                }
            }
        }

        var chart = new ApexCharts(
            document.querySelector("#s-col"),
            sCol
        );

        chart.render();
    }


    // Simple Column Stacked
    if ($('#s-col-stacked').length > 0) {
        var sColStacked = {
            chart: {
                height: 350,
                type: 'bar',
                stacked: true,
                toolbar: {
                    show: false,
                }
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    legend: {
                        position: 'bottom',
                        offsetX: -10,
                        offsetY: 0
                    }
                }
            }],
            plotOptions: {
                bar: {
                    horizontal: false,
                },
            },
            colors: ['#F26522', '#E70D0D', '#03C95A', '#1B84FF'],
            series: [{
                name: 'PRODUCT A',
                data: [44, 55, 41, 67, 22, 43]
            }, {
                name: 'PRODUCT B',
                data: [13, 23, 20, 8, 13, 27]
            }, {
                name: 'PRODUCT C',
                data: [11, 17, 15, 15, 21, 14]
            }, {
                name: 'PRODUCT D',
                data: [21, 7, 25, 13, 22, 8]
            }],
            xaxis: {
                type: 'datetime',
                categories: ['01/01/2011 GMT', '01/02/2011 GMT', '01/03/2011 GMT', '01/04/2011 GMT', '01/05/2011 GMT', '01/06/2011 GMT'],
            },
            legend: {
                position: 'right',
                offsetY: 40
            },
            fill: {
                opacity: 1
            },
        }

        var chart = new ApexCharts(
            document.querySelector("#s-col-stacked"),
            sColStacked
        );

        chart.render();
    }

    // Simple Bar
    if ($('#s-bar').length > 0) {
        var sBar = {
            chart: {
                height: 350,
                type: 'bar',
                toolbar: {
                    show: false,
                }
            },
            colors: ['#F26522'],
            plotOptions: {
                bar: {
                    horizontal: true,
                }
            },
            dataLabels: {
                enabled: false
            },
            series: [{
                data: [400, 430, 448, 470, 540, 580, 690, 1100, 1200, 1380]
            }],
            xaxis: {
                categories: ['South Korea', 'Canada', 'United Kingdom', 'Netherlands', 'Italy', 'France', 'Japan', 'United States', 'China', 'Germany'],
            }
        }

        var chart = new ApexCharts(
            document.querySelector("#s-bar"),
            sBar
        );

        chart.render();
    }

    // Mixed Chart
    if ($('#mixed-chart').length > 0) {
        var options = {
            chart: {
                height: 350,
                type: 'line',
                toolbar: {
                    show: false,
                }
            },
            colors: ['#F26522', '#888ea8'],
            series: [{
                name: 'Website Blog',
                type: 'column',
                data: [440, 505, 414, 671, 227, 413, 201, 352, 752, 320, 257, 160]
            }, {
                name: 'Social Media',
                type: 'line',
                data: [23, 42, 35, 27, 43, 22, 17, 31, 22, 22, 12, 16]
            }],
            stroke: {
                width: [0, 4]
            },
            title: {
                text: 'Traffic Sources'
            },
            labels: ['01 Jan 2001', '02 Jan 2001', '03 Jan 2001', '04 Jan 2001', '05 Jan 2001', '06 Jan 2001', '07 Jan 2001', '08 Jan 2001', '09 Jan 2001', '10 Jan 2001', '11 Jan 2001', '12 Jan 2001'],
            xaxis: {
                type: 'datetime'
            },
            yaxis: [{
                title: {
                    text: 'Website Blog',
                },

            }, {
                opposite: true,
                title: {
                    text: 'Social Media'
                }
            }]

        }

        var chart = new ApexCharts(
            document.querySelector("#mixed-chart"),
            options
        );

        chart.render();
    }

    // Donut Chart

    if ($('#donut-chart').length > 0) {
        var donutChart = {
            chart: {
                height: 350,
                type: 'donut',
                toolbar: {
                    show: false,
                }
            },
            // colors: ['#4361ee', '#888ea8', '#e3e4eb', '#d3d3d3'],
            series: [44, 55, 41, 17],
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        }

        var donut = new ApexCharts(
            document.querySelector("#donut-chart"),
            donutChart
        );

        donut.render();
    }

    // Radial Chart
    if ($('#radial-chart').length > 0) {
        var radialChart = {
            chart: {
                height: 350,
                type: 'radialBar',
                toolbar: {
                    show: false,
                }
            },
            // colors: ['#4361ee', '#888ea8', '#e3e4eb', '#d3d3d3'],
            plotOptions: {
                radialBar: {
                    dataLabels: {
                        name: {
                            fontSize: '22px',
                        },
                        value: {
                            fontSize: '16px',
                        },
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: function (w) {
                                return 249
                            }
                        }
                    }
                }
            },
            series: [44, 55, 67, 83],
            labels: ['Apples', 'Oranges', 'Bananas', 'Berries'],
        }

        var chart = new ApexCharts(
            document.querySelector("#radial-chart"),
            radialChart
        );

        chart.render();
    }

    if ($('#sales_charts').length > 0) {
        var options = {
            series: [{
                name: 'Sales',
                data: [130, 210, 300, 290, 150, 50, 210, 280, 105],
            }, {
                name: 'Purchase',
                data: [-150, -90, -50, -180, -50, -70, -100, -90, -105]
            }],
            colors: ['#28C76F', '#EA5455'],
            chart: {
                type: 'bar',
                height: 320,
                stacked: true,

                zoom: {
                    enabled: true
                }
            },
            responsive: [{
                breakpoint: 280,
                options: {
                    legend: {
                        position: 'bottom',
                        offsetY: 0
                    }
                }
            }],
            plotOptions: {
                bar: {
                    horizontal: false,
                    borderRadius: 4,
                    borderRadiusApplication: "end", // "around" / "end"
                    borderRadiusWhenStacked: "all", // "all"/"last"
                    columnWidth: '20%',
                },
            },
            dataLabels: {
                enabled: false
            },
            yaxis: {
                min: -200,
                max: 300,
                tickAmount: 5,
            },
            xaxis: {
                categories: [' Jan ', 'Feb', 'Mar', 'Apr',
                    'May', 'Jun', 'Jul', 'Aug', 'Sep'
                ],
            },
            legend: { show: false },
            fill: {
                opacity: 1
            }
        };

        var chart = new ApexCharts(document.querySelector("#sales_charts"), options);
        chart.render();
    }

    if ($('#sales-analysis').length > 0) {
        var options = {
            series: [{
                name: "Sales Analysis",
                data: [25, 30, 18, 15, 22, 20, 30, 20, 22, 18, 15, 20]
            }],
            chart: {
                height: 273,
                type: 'area',
                zoom: {
                    enabled: false
                }
            },
            colors: ['#FF9F43'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'straight'
            },
            title: {
                text: '',
                align: 'left'
            },
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
            },
            yaxis: {
                min: 10,
                max: 60,
                tickAmount: 5,
                labels: {
                    formatter: (val) => {
                        return val / 1 + 'K'
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            }
        };

        var chart = new ApexCharts(document.querySelector("#sales-analysis"), options);
        chart.render();
    }

    // Student Chart

    if ($('#teacher-chart').length > 0) {
        var donutChart = {
            chart: {
                height: 260,
                type: 'donut',
                toolbar: {
                    show: false,
                }
            },
            colors: ['#3D5EE1', '#6FCCD8'],
            series: [346, 54],
            labels: ['Present', 'Absent'],
            legend: { show: false },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        height: 180,
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        }

        var donut = new ApexCharts(
            document.querySelector("#teacher-chart"),
            donutChart
        );

        donut.render();
    }


    // Student Chart

    if ($('#staff-chart').length > 0) {
        var donutChart = {
            chart: {
                height: 260,
                type: 'donut',
                toolbar: {
                    show: false,
                }
            },
            colors: ['#3D5EE1', '#6FCCD8'],
            series: [620, 80],
            labels: ['Present', 'Absent'],
            legend: { show: false },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        height: 180,
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        }

        var donut = new ApexCharts(
            document.querySelector("#staff-chart"),
            donutChart
        );

        donut.render();
    }


    // Class Chart

    if ($('#class-chart').length > 0) {
        var donutChart = {
            chart: {
                height: 130,
                type: 'donut',
                toolbar: {
                    show: false,
                },
                sparkline: {
                    enabled: true
                }
            },
            colors: ['#3D5EE1', '#EAB300', '#E82646'],
            series: [45, 11, 2],
            labels: ['Good', 'Average', 'Below Average'],
            legend: { show: false },
            dataLabels: {
                enabled: false
            },
            yaxis: {
                tickAmount: 3,
                labels: {
                    offsetX: -15,
                },
            },
            grid: {
                padding: {
                    left: -8,
                },
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        }

        var donut = new ApexCharts(
            document.querySelector("#class-chart"),
            donutChart
        );

        donut.render();
    }

    // Leaves Chart

    if ($('#leaves_chart').length > 0) {
        var donutChart = {
            chart: {
                height: 185,
                type: 'donut',
                toolbar: {
                    show: false,
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '50%'
                },
            },
            dataLabels: {
                enabled: false
            },
            grid: {
                padding: {
                    // Set padding to 0 or a small positive number instead of negative
                    right: 0,
                    left: 10
                },
            },
            yaxis: {
                labels: {
                    // Use offsetX to move labels without breaking the grid container
                    offsetX: -10,
                    style: {
                        colors: '#6B7280',
                        fontSize: '13px',
                    }
                }
            },
            series: [15, 10, 5, 10, 60],
            colors: ['#F26522', '#FFC107', '#E70D0D', '#03C95A', '#0C4B5E'],
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        show: false
                    }
                }
            }],
            legend: {
                show: false
            },
            // ADD THIS SECTION FOR MOBILE RESPONSIVENESS
            responsive: [{
                breakpoint: 576, // Targeted for mobile devices (Bootstrap 'sm' breakpoint)
                options: {
                    grid: {
                        padding: {
                            right: 0,// Reset to 0 on mobile
                        },
                        offsetX: -200
                    },
                    // You can also adjust columnWidth for mobile if needed
                    plotOptions: {
                        bar: {
                            columnWidth: '60%'
                        }
                    }
                }
            }]
        }

        var donut = new ApexCharts(
            document.querySelector("#leaves_chart"),
            donutChart
        );

        donut.render();
    }
    // Tenant Chart

    if ($('#tenant_chart').length > 0) {
        var donutChart = {
            series: [0.62, 2.48, 4.34, 1.86, 1.86],
            chart: {
                type: 'donut',
                height: 200,
            },
            labels: ['Database', 'Images', 'Videos', 'Documents', 'Audio'],
            colors: ['#FFC107', '#00C2FF', '#0D3C4A', '#1B84FF', '#F26522'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Database',
                                formatter: function (w) {
                                    return '35%';
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            legend: {
                show: false
            },
            responsive: [
                {
                    breakpoint: 768,
                    options: {
                        legend: {
                            position: 'bottom',
                            radius: 2.03
                        }
                    }
                }
            ]
        };

        var donut = new ApexCharts(
            document.querySelector("#tenant_chart"),
            donutChart
        );

        donut.render();
    }


    // Fees Chart

    if ($('#fees-chart').length > 0) {
        var sCol = {
            chart: {
                height: 275,
                type: 'bar',
                stacked: true,
                toolbar: {
                    show: false,
                }
            },
            legend: {
                show: true,
                horizontalAlign: 'left',
                position: 'top',
                fontSize: '14px',
                labels: {
                    colors: '#5D6369',
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '50%',
                    endingShape: 'rounded'
                },
            },
            colors: ['#3D5EE1', '#E9EDF4'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            grid: {
                padding: {
                    left: -8,
                },
            },
            series: [{
                name: 'Collected Fee',
                data: [30, 40, 38, 40, 38, 30, 35, 38, 40]
            }, {
                name: 'Total Fee',
                data: [45, 50, 48, 50, 48, 40, 40, 50, 55]
            }],
            xaxis: {
                categories: ['Q1: 2023', 'Q1: 2023', 'Q1: 2023', 'Q1: 2023', 'Q1: 2023', 'uQ1: 2023l', 'Q1: 2023', 'Q1: 2023', 'Q1: 2023'],
            },
            yaxis: {
            },
            yaxis: {
                tickAmount: 3,
                labels: {
                    offsetX: -15
                },
            },
            fill: {
                opacity: 1

            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "$ " + val + " thousands"
                    }
                }
            }
        }

        var chart = new ApexCharts(
            document.querySelector("#fees-chart"),
            sCol
        );

        chart.render();
    }

    if ($('#exam-result-chart').length > 0) {
        var options = {
            chart: {
                type: 'bar',
                height: 310
            },
            series: [{
                name: 'Marks',
                data: [100, 92, 90, 82, 90] // Corresponding scores for Maths, Physics, Chemistry, English, Spanish
            }],
            xaxis: {
                categories: ['Mat', 'Phy', 'Che', 'Eng', 'Sci']
            },
            plotOptions: {
                bar: {
                    distributed: true,
                    columnWidth: '50%',
                    colors: {
                        backgroundBarColors: ['#E9EDF4', '#fff'],
                        backgroundBarOpacity: 1,
                        backgroundBarRadius: 5,
                    },
                    dataLabels: {
                        position: 'top'
                    },
                }
            },
            colors: ['#E9EDF4', '#3D5EE1', '#E9EDF4', '#E9EDF4', '#E9EDF4'], // Set specific colors for each bar
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + "%"
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val + "%";
                },
                offsetY: -20,
                style: {
                    fontSize: '14px',
                    colors: ["#304758"]
                }
            },
            grid: {
                yaxis: {
                    lines: {
                        show: false
                    }
                },
            },

            legend: {
                show: false
            }
        }

        var chart = new ApexCharts(document.querySelector("#exam-result-chart"), options);
        chart.render();
    }

    if ($('#performance_chart').length > 0) {
        var options = {
            chart: {
                type: 'area',
                height: 355
            },
            series: [{
                name: 'Avg. Exam Score',
                data: [75, 68, 65, 68, 75] // Sample data
            }, {
                name: 'Avg. Attendance',
                data: [85, 78, 75, 78, 85] // Sample data
            }],
            xaxis: {
                categories: ['Quarter 1', 'Quarter 2', 'Half yearly', 'Model', 'Final']
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + "%";
                    }
                },
                shared: true,
                intersect: false,
                custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                    return `<div class="apexcharts-tooltip">${w.globals.labels[dataPointIndex]}<br>Exam Score: <span style="color: #1E90FF;">${series[0][dataPointIndex]}%</span><br>Attendance: <span style="color: #00BFFF;">${series[1][dataPointIndex]}%</span></div>`;
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth'
            },
            grid: {
                padding: {
                    left: -15,
                    right: 0,
                },
            },
            grid: {
                yaxis: {
                    axisTicks: {
                        show: true,
                        borderType: 'solid',
                        color: '#78909C',
                        width: 6,
                        offsetX: 0,
                        offsetY: 0
                    },

                },
            },
            yaxis: {
                labels: {
                    offsetX: -15
                },
            },
            markers: {
                size: 5,
                colors: ['#1E90FF', '#00BFFF'],
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: {
                    size: 7
                }
            },
            colors: ['#3D5EE1', '#6FCCD8'], // Color for the lines
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.9,
                    stops: [0, 90, 100]
                }
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center'
            }
        }
        var chart = new ApexCharts(document.querySelector("#performance_chart"), options);
        chart.render();
    }

    // Plan Chart

    if ($('#plan_chart').length > 0) {
        var donutChart = {
            chart: {
                height: 90,
                type: 'donut',
                toolbar: {
                    show: false,
                },
                sparkline: {
                    enabled: true
                }
            },
            grid: {
                show: false,
                padding: {
                    left: 0,
                    right: 0
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '50%'
                },
            },
            dataLabels: {
                enabled: false
            },

            series: [95, 5],
            labels: [
                'Completed',
                'Pending'

            ],
            legend: { show: false },
            colors: ['#3D5EE1', '#E82646'],
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 100
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }],
            legend: {
                position: 'bottom'
            }
        }

        var donut = new ApexCharts(
            document.querySelector("#plan_chart"),
            donutChart
        );

        donut.render();
    }

    if ($('#statistic_chart').length > 0) {
        var options = {
            chart: {
                type: 'line',
                height: 345,
            },
            series: [{
                name: 'Avg. Exam Score',
                data: [0, 32, 40, 50, 60, 52, 50, 44, 40, 60, 75, 70] // Sample data
            }, {
                name: 'Avg. Attendance',
                data: [0, 35, 43, 34, 30, 28, 25, 50, 60, 75, 77, 80] // Sample data
            }],
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + "%";
                    }
                },
                shared: true,
                intersect: false,
                custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                    return `<div class="apexcharts-tooltip">${w.globals.labels[dataPointIndex]}<br>Exam Score: <span style="color: #1E90FF;">${series[0][dataPointIndex]}%</span><br>Attendance: <span style="color: #00BFFF;">${series[1][dataPointIndex]}%</span></div>`;
                }
            },
            dataLabels: {
                enabled: false
            },
            grid: {
                yaxis: {
                    lines: {
                        show: true
                    }
                },
            },
            yaxis: {
                labels: {
                    offsetX: -15
                },
            },
            grid: {
                padding: {
                    left: -8,
                },
            },
            markers: {
                size: 0,
                colors: ['#1E90FF', '#00BFFF'],
                strokeColors: '#fff',
                strokeWidth: 1,
                hover: {
                    size: 7
                }
            },
            colors: ['#3D5EE1', '#6FCCD8'], // Color for the lines
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            }
        }
        var chart = new ApexCharts(document.querySelector("#statistic_chart"), options);
        chart.render();
    }

    if ($('#attendance_chart2').length > 0) {
        var donutChart = {
            chart: {
                height: 290,
                type: 'donut',
                toolbar: {
                    show: false,
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '50%'
                },
            },
            dataLabels: {
                enabled: false
            },

            series: [60, 5, 15, 20],
            labels: [
                'Present',
                'Late',
                'Half Day',
                'Absent'
            ],
            colors: ['#1ABE17', '#1170E4', '#E9EDF4', '#E82646'],
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'left'
                    }
                }
            }],
            legend: {
                position: 'left',
            }
        }

        var donut = new ApexCharts(
            document.querySelector("#attendance_chart2"),
            donutChart
        );

        donut.render();
    }

    // Total Earning
    if ($('#total-earning').length > 0) {
        var sLineArea = {
            chart: {
                height: 90,
                type: 'area',
                toolbar: {
                    show: false,
                },
                sparkline: {
                    enabled: true
                }
            },
            colors: ['#3D5EE1'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'straight'
            },
            series: [{
                name: 'Earnings',
                data: [50, 60, 40, 50, 45, 55, 50]
            }]
        }

        var chart = new ApexCharts(
            document.querySelector("#total-earning"),
            sLineArea
        );

        chart.render();
    }

    // Total Expenses
    if ($('#total-expenses').length > 0) {
        var sLineArea = {
            chart: {
                height: 90,
                type: 'area',
                toolbar: {
                    show: false,
                },
                sparkline: {
                    enabled: true
                }
            },
            colors: ['#E82646'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'straight'
            },
            series: [{
                name: 'Earnings',
                data: [40, 20, 60, 55, 50, 55, 40]
            }]
        }

        var chart = new ApexCharts(
            document.querySelector("#total-expenses"),
            sLineArea
        );

        chart.render();
    }

});

// Donut Chart

if ($('#storage-chart').length > 0) {
    var donutChart = {
        chart: {
            height: 200,
            type: 'donut',
            toolbar: {
                show: false,
            },
            offsetY: -10,
            events: {
                rendered: function () {
                    // Adding the center text
                    var chartElement = document.querySelector("#donutChart");
                    var innerText = '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">' +
                        '<span style="font-size: 24px; font-weight: bold;">Total</span><br>' +
                        '<span style="font-size: 16px;">abb</span>' +
                        '</div>';
                    chartElement.innerHTML += innerText;
                }
            },
        },
        plotOptions: {
            pie: {
                startAngle: -100,
                endAngle: 100,
                donut: {
                    size: '80%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            show: false
        },
        stroke: {
            show: false
        },
        colors: ['#0C4B5E', '#FFC107', '#1B84FF', '#AB47BC', '#FD3995'],
        series: [20, 20, 20, 20, 20],
        labels: ['Documents', 'Video', 'Music', 'Photos', 'Other'],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }],
        grid: {
            padding: {
                bottom: -60  // Reduce padding from the bottom
            }
        }
    }

    var donut = new ApexCharts(
        document.querySelector("#storage-chart"),
        donutChart
    );

    donut.render();
}

// Total Company

if ($('#total-chart').length > 0) {

    var options = {
        series: [{
            name: "Messages",
            data: [25, 66, 41, 12, 36, 9, 21]
        }],
        fill: {
            type: 'gradient',
            gradient: {
                opacityFrom: 0, // Start with 0 opacity (transparent)
                opacityTo: 0    // End with 0 opacity (transparent)
            }
        },
        chart: {
            foreColor: '#fff',
            type: "area",
            width: 50,
            toolbar: {
                show: !1
            },
            zoom: {
                enabled: !1
            },
            dropShadow: {
                enabled: 0,
                top: 3,
                left: 14,
                blur: 4,
                opacity: .12,
                color: "#fff"
            },
            sparkline: {
                enabled: !0
            }
        },
        markers: {
            size: 0,
            colors: ["#F26522"],
            strokeColors: "#fff",
            strokeWidth: 2,
            hover: {
                size: 7
            }
        },
        plotOptions: {
            bar: {
                horizontal: !1,
                columnWidth: "35%",
                endingShape: "rounded"
            }
        },
        dataLabels: {
            enabled: !1
        },
        stroke: {
            show: !0,
            width: 2.5,
            curve: "smooth"
        },
        colors: ["#F26522"],
        xaxis: {
            categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep"]
        },
        tooltip: {
            theme: "dark",
            fixed: {
                enabled: !1
            },
            x: {
                show: !1
            },
            y: {
                title: {
                    formatter: function (e) {
                        return ""
                    }
                }
            },
            marker: {
                show: !1
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#active-chart"), options);
    chart.render();
}

// Active Company

if ($('#active-chart').length > 0) {

    var options = {
        series: [{
            name: "Active Company",
            data: [25, 40, 35, 20, 36, 9, 21]
        }],
        fill: {
            type: 'gradient',
            gradient: {
                opacityFrom: 0, // Start with 0 opacity (transparent)
                opacityTo: 0    // End with 0 opacity (transparent)
            }
        },
        chart: {
            foreColor: '#fff',
            type: "area",
            width: 50,
            toolbar: {
                show: !1
            },
            zoom: {
                enabled: !1
            },
            dropShadow: {
                enabled: 0,
                top: 3,
                left: 14,
                blur: 4,
                opacity: .12,
                color: "#fff"
            },
            sparkline: {
                enabled: !0
            }
        },
        markers: {
            size: 0,
            colors: ["#F26522"],
            strokeColors: "#fff",
            strokeWidth: 2,
            hover: {
                size: 7
            }
        },
        plotOptions: {
            bar: {
                horizontal: !1,
                columnWidth: "35%",
                endingShape: "rounded"
            }
        },
        dataLabels: {
            enabled: !1
        },
        stroke: {
            show: !0,
            width: 2.5,
            curve: "smooth"
        },
        colors: ["#F26522"],
        xaxis: {
            categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep"]
        },
        tooltip: {
            theme: "dark",
            fixed: {
                enabled: !1
            },
            x: {
                show: !1
            },
            y: {
                title: {
                    formatter: function (e) {
                        return ""
                    }
                }
            },
            marker: {
                show: !1
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#total-chart"), options);
    chart.render();
}

// Inactive Company

if ($('#inactive-chart').length > 0) {

    var options = {
        series: [{
            name: "Inactive Company",
            data: [25, 10, 35, 5, 25, 28, 21]
        }],
        fill: {
            type: 'gradient',
            gradient: {
                opacityFrom: 0, // Start with 0 opacity (transparent)
                opacityTo: 0    // End with 0 opacity (transparent)
            }
        },
        chart: {
            foreColor: '#fff',
            type: "area",
            width: 50,
            toolbar: {
                show: !1
            },
            zoom: {
                enabled: !1
            },
            dropShadow: {
                enabled: 0,
                top: 3,
                left: 14,
                blur: 4,
                opacity: .12,
                color: "#fff"
            },
            sparkline: {
                enabled: !0
            }
        },
        markers: {
            size: 0,
            colors: ["#F26522"],
            strokeColors: "#fff",
            strokeWidth: 2,
            hover: {
                size: 7
            }
        },
        plotOptions: {
            bar: {
                horizontal: !1,
                columnWidth: "35%",
                endingShape: "rounded"
            }
        },
        dataLabels: {
            enabled: !1
        },
        stroke: {
            show: !0,
            width: 2.5,
            curve: "smooth"
        },
        colors: ["#F26522"],
        xaxis: {
            categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep"]
        },
        tooltip: {
            theme: "dark",
            fixed: {
                enabled: !1
            },
            x: {
                show: !1
            },
            y: {
                title: {
                    formatter: function (e) {
                        return ""
                    }
                }
            },
            marker: {
                show: !1
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#inactive-chart"), options);
    chart.render();
}

// Location Company

if ($('#location-chart').length > 0) {

    var options = {
        series: [{
            name: "Inactive Company",
            data: [30, 40, 15, 23, 20, 23, 25]
        }],
        fill: {
            type: 'gradient',
            gradient: {
                opacityFrom: 0, // Start with 0 opacity (transparent)
                opacityTo: 0    // End with 0 opacity (transparent)
            }
        },
        chart: {
            foreColor: '#fff',
            type: "area",
            width: 50,
            toolbar: {
                show: !1
            },
            zoom: {
                enabled: !1
            },
            dropShadow: {
                enabled: 0,
                top: 3,
                left: 14,
                blur: 4,
                opacity: .12,
                color: "#fff"
            },
            sparkline: {
                enabled: !0
            }
        },
        markers: {
            size: 0,
            colors: ["#F26522"],
            strokeColors: "#fff",
            strokeWidth: 2,
            hover: {
                size: 7
            }
        },
        plotOptions: {
            bar: {
                horizontal: !1,
                columnWidth: "35%",
                endingShape: "rounded"
            }
        },
        dataLabels: {
            enabled: !1
        },
        stroke: {
            show: !0,
            width: 2.5,
            curve: "smooth"
        },
        colors: ["#F26522"],
        xaxis: {
            categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep"]
        },
        tooltip: {
            theme: "dark",
            fixed: {
                enabled: !1
            },
            x: {
                show: !1
            },
            y: {
                title: {
                    formatter: function (e) {
                        return ""
                    }
                }
            },
            marker: {
                show: !1
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#location-chart"), options);
    chart.render();
}

if ($('#expense-analysis').length > 0) {
    var options = {
        series: [{
            name: "Sales Analysis",
            data: [10, 30, 18, 15, 22, 30, 40, 50, 40, 40, 60, 70]
        }],
        chart: {
            height: 190,
            type: 'area',
            zoom: {
                enabled: false
            }
        },
        colors: ['#FF9F43'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth'
        },
        title: {
            text: '',
            align: 'left'
        },
        // grid: {
        //   row: {
        //     colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns
        //     opacity: 0.5
        //   },
        // },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
        },
        yaxis: {
            min: 10,
            max: 60,
            tickAmount: 5,
            labels: {
                offsetX: -15,
                formatter: (val) => {
                    return val / 1 + 'K'
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left'
        }
    };

    var chart = new ApexCharts(document.querySelector("#expense-analysis"), options);
    chart.render();
}

if ($('#invoice-report').length > 0) {
    var options = {
        series: [{
            name: 'Total Invoices',
            data: [40, 30, 40, 30, 40, 30]
        }, {
            name: 'Paid Invoices',
            data: [20, 10, 20, 10, 20, 10]
        }],
        chart: {
            height: 250,
            type: 'area'
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'straight'
        },
        xaxis: {
            type: 'category',
            categories: ["January", "February", "March", "April", "May", "June", "July"]
        },
        yaxis: {
            labels: {
                offsetX: -15,
                formatter: function (value) {
                    return value + "k"; // Display values with 'k' suffix
                }
            },
        },
        tooltip: {
            x: {
                formatter: function (value) {
                    return value; // Tooltip shows month labels
                }
            },
            y: {
                formatter: function (value) {
                    return value + "k"; // Tooltip shows amounts with 'k' suffix
                }
            }
        },
        colors: ['#FD3995', '#FF9F43'],
        dataLabels: {
            enabled: false
        },
    };

    // Create the chart instance
    var chart = new ApexCharts(document.querySelector("#invoice-report"), options);
    chart.render();
}

if ($('#payment-report').length > 0) {
    var options = {
        series: [44, 55, 41, 17],
        chart: {
            type: 'donut',
        },
        colors: ['#0DCAF0', '#FD3995', '#AB47BC', '#FFC107'],
        labels: ['Paypal', 'Debit Card', 'Bank Transfer', 'Credit Card'],
        plotOptions: {
            pie: {
                startAngle: -90,
                endAngle: 270,
                stroke: {
                    show: true,
                    width: 10, // Width of the gap
                    colors: ['#FFFFFF'] // Color of the gap
                },
                donut: {
                    size: '80%' // Adjusts the size of the donut hole
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            show: false // Set this to false to hide the legend
        },
        annotations: {
            position: 'front', // Ensure it appears above other elements
            style: {
                fontSize: '24px', // Adjust font size
                fontWeight: 'bold',
                color: '#000000' // Change color if needed
            },
            text: {
                // Set the annotation text
                text: '+14%',
                // Optional styling for the text box
                background: {
                    enabled: true,
                    foreColor: '#FFFFFF', // Text color
                    border: '#000000', // Border color
                    borderWidth: 1,
                    borderRadius: 2,
                    opacity: 0.7
                }
            },
            x: '50%', // Center horizontally
            y: '50%', // Center vertically
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    show: false // Also hide legend on smaller screens
                }
            }
        }]
    };

    var chart = new ApexCharts(document.querySelector("#payment-report"), options);
    chart.render();
}

if ($('#task-reports').length > 0) {
    var options = {
        series: [40, 30, 20, 10],
        chart: {
            type: 'donut',
            width: 220,
        },
        colors: ['#03C95A', '#0DCAF0', '#FFC107', '#AB47BC'],
        labels: ['Completed ', 'Pending', 'Inprogress ', 'On Hold '],
        plotOptions: {
            pie: {
                startAngle: -90,
                endAngle: 270,
                stroke: {
                    show: true,
                    width: 10, // Width of the gap
                    colors: ['#FFFFFF'] // Color of the gap
                },
                donut: {
                    size: '80%' // Adjusts the size of the donut hole
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            show: false // Set this to false to hide the legend
        },
        annotations: {
            position: 'front', // Ensure it appears above other elements
            style: {
                fontSize: '24px', // Adjust font size
                fontWeight: 'bold',
                color: '#000000' // Change color if needed
            },
            text: {
                // Set the annotation text
                text: '+14%',
                // Optional styling for the text box
                background: {
                    enabled: true,
                    foreColor: '#FFFFFF', // Text color
                    border: '#000000', // Border color
                    borderWidth: 1,
                    borderRadius: 2,
                    opacity: 0.7
                }
            },
            x: '50%', // Center horizontally
            y: '50%', // Center vertically
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    show: false // Also hide legend on smaller screens
                }
            }
        }]
    };

    var chart = new ApexCharts(document.querySelector("#task-reports"), options);
    chart.render();
}


if ($('#project-report').length > 0) {
    var options = {
        series: [30, 10, 20, 40],
        chart: {
            width: 280,
            type: 'pie',
        },
        labels: ['Pending', 'On Hold', 'In Progress', 'Completed'], // Set your labels here
        colors: ['#0DCAF0', '#AB47BC', '#FFC107', '#03C95A'], // Custom colors for each segment
        dataLabels: {
            enabled: false // Disable data labels to remove numbers
        },
        legend: {
            show: false // Hide the legend
        },
        tooltip: {
            y: {
                formatter: function (value, { seriesIndex }) {
                    return 'Value: ' + value; // Customize the tooltip text
                }
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };

    var chart = new ApexCharts(document.querySelector("#project-report"), options);
    chart.render();
}

if ($('#ticket-report').length > 0) {
    var options = {
        series: [
            {
                name: 'Access Issue',
                data: [
                    { x: 'Critical', y: 80 },
                    { x: 'High', y: 70 },
                    { x: 'Medium', y: 20 },
                    { x: 'Low', y: 15 }
                ]
            },
            {
                name: 'Module Issue',
                data: [
                    { x: 'Critical', y: 15 },
                    { x: 'High', y: 30 },
                    { x: 'Medium', y: 35 },
                    { x: 'Low', y: 65 }
                ]
            },
            {
                name: 'Billing & Payments',
                data: [
                    { x: 'Critical', y: 20 },
                    { x: 'High', y: 65 },
                    { x: 'Medium', y: 60 },
                    { x: 'Low', y: 30 }
                ]
            },
            {
                name: 'Integration Issues',
                data: [
                    { x: 'Critical', y: 15 },
                    { x: 'High', y: 70 },
                    { x: 'Medium', y: 30 },
                    { x: 'Low', y: 20 }
                ]
            },
            {
                name: 'Subscription Issues',
                data: [
                    { x: 'Critical', y: 30 },
                    { x: 'High', y: 75 },
                    { x: 'Medium', y: 70 },
                    { x: 'Low', y: 35 }
                ]
            }
        ],

        chart: {
            type: 'heatmap',
            height: 260,
            toolbar: { show: false }
        },

        plotOptions: {
            heatmap: {
                radius: 6,            // rounded blocks
                shadeIntensity: 0.6,
                colorScale: {
                    ranges: [
                        { from: 0, to: 20, color: '#E9EFF2' },
                        { from: 21, to: 40, color: '#C9D9DF' },
                        { from: 41, to: 60, color: '#9FBAC4' },
                        { from: 61, to: 100, color: '#6F97A4' }
                    ]
                }
            }
        },

        dataLabels: {
            enabled: false // no numbers inside blocks
        },

        stroke: {
            width: 4,
            colors: ['#ffffff'] // spacing between blocks
        },

        xaxis: {
            labels: {
                style: {
                    fontSize: '13px',
                    colors: '#6B7280'
                }
            }
        },

        yaxis: {
            labels: {
                style: {
                    fontSize: '13px',
                    colors: '#6B7280'
                }
            }
        },

        grid: {
            show: false
        },

        legend: {
            show: false
        },

        tooltip: {
            theme: 'light',
            y: {
                formatter: val => `Tickets: ${val}`
            }
        }
    };

    var chart = new ApexCharts(
        document.querySelector("#ticket-report"),
        options
    );
    chart.render();
}


if ($('#employee-reports').length > 0) {
    var options = {
        series: [{
            name: 'Active Employees',
            data: [50, 55, 57, 56, 61, 58, 63, 60, 66]
        }, {
            name: 'Inactive Employees',
            data: [76, 85, 101, 98, 87, 105, 91, 114, 94]
        }],
        chart: {
            type: 'bar',
            height: 180
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                endingShape: 'rounded'
            }
        },
        colors: ['#03C95A', '#E8E9EA'], // Active Employees - Green, Inactive Employees - Gray
        dataLabels: {
            enabled: false, // Disable data labels
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct']
        }, yaxis: {
            labels: {
                offsetX: -15,
            }
        },
        fill: {
            opacity: 1
        },
        legend: {
            show: false
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "$ " + val + " thousands";
                }
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#employee-reports"), options);
    chart.render();
}

if ($('#attendance-report').length > 0) {
    var options = {
        series: [{
            name: "Present",
            data: [30, 65, 70, 75, 80, 95, 100, 70, 65] // Example data for Present
        }, {
            name: "Absent",
            data: [30, 55, 60, 65, 50, 70, 80, 60, 70] // Example data for Absent
        }],
        chart: {
            height: 200, // Change height here
            type: 'line',
            zoom: {
                enabled: false
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth' // Change to 'smooth' for a nicer appearance
        },
        grid: {
            row: {
                colors: ['#f3f3f3', 'transparent'], // alternating row colors
                opacity: 0.5
            },
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
        }, yaxis: {
            labels: {
                offsetX: -15,
            }
        },
        colors: ['#28a745', '#ff69b4'] // Green for Present, Pink for Absent
    };

    var chart = new ApexCharts(document.querySelector("#attendance-report"), options);
    chart.render();
}

if ($('#leave-report').length > 0) {
    var options = {
        series: [{
            name: 'Annual Leave',
            data: [30, 40, 35, 50, 50, 60, 30, 40, 35, 50, 50, 60] // Replace with your data
        }, {
            name: 'Casual Leave',
            data: [20, 30, 25, 40, 50, 60, 20, 30, 25, 40, 50, 60] // Replace with your data
        }, {
            name: 'Medical Leave',
            data: [15, 10, 20, 15, 50, 60, 15, 10, 20, 15, 50, 60] // Replace with your data
        }, {
            name: 'Others',
            data: [25, 20, 30, 35, 50, 60, 25, 20, 30, 35, 50, 60] // Replace with your data
        },
        ],
        chart: {
            type: 'bar',
            height: 210, // Change this value to your desired height
            stacked: true,
            stackType: '100%'
        },
        responsive: [{
            breakpoint: 480,
            options: {
                legend: {
                    position: 'bottom',
                    offsetX: -10,
                    offsetY: 0
                }
            }
        }],
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',] // Update to match your time frame
        },
        yaxis: {
            labels: {
                offsetX: -15,
            }
        },
        fill: {
            opacity: 1
        },
        legend: {
            show: false
        },
        colors: ['#03C95A', '#FFC107', '#0C4B5E', '#F26522'], // Set your colors here
        dataLabels: {
            enabled: false // Disable data labels
        }
    };

    var chart = new ApexCharts(document.querySelector("#leave-report"), options);
    chart.render();
}

if ($('#daily-report').length > 0) {
    var options = {
        series: [{
            name: "Present",
            data: [60, 40, 30, 20, 70,] // Sample data for Present
        }, {
            name: "Absent",
            data: [20, 60, 45, 60, 80,] // Sample data for Absent
        }],
        chart: {
            height: 130, // Changed height
            type: 'line',
            zoom: {
                enabled: false
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            show: false
        },
        stroke: {
            curve: 'smooth' // You can change this to 'straight' if preferred
        },
        grid: {
            row: {
                colors: ['#f3f3f3', 'transparent'],
                opacity: 0.5
            }
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
        },
        yaxis: {
            labels: {
                offsetX: -15,
            }
        },
        colors: ['#4CAF50', '#F44336'] // Green for Present, Red for Absent
    };

    var chart = new ApexCharts(document.querySelector("#daily-report"), options);
    chart.render();
}

if ($('#revenue-chart').length > 0) {
    var sCol = {
        chart: {
            height: 200,
            type: 'bar',
            stacked: true,
            toolbar: {
                show: false,
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '50%',
                endingShape: 'rounded'
            },
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 1,
        },
        series: [{
            name: 'High',
            color: '#0E82FD',
            data: [50, 40, 15, 45, 35, 48, 65]
        }],
        xaxis: {
            categories: ['M', 'T', 'W', 'T', 'F', 'S', 'S'],
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "$ " + val + "k"
                }
            }
        }
    }

    var chart = new ApexCharts(
        document.querySelector("#revenue-chart"),
        sCol
    );

    chart.render();
}

// Employee Department

if ($('#emp-department').length > 0) {
    var sBar = {
        chart: {
            height: 220,
            type: 'bar',
            padding: {
                top: 0,
                left: 0,
                right: 0,
                bottom: 0
            },
            toolbar: {
                show: false,
            }
        },
        colors: ['#FF6F28'],
        grid: {
            borderColor: '#E5E7EB',
            strokeDashArray: 5,
            padding: {
                top: -20,
                left: 0,
                right: 0,
                bottom: 0
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 5,
                horizontal: true,
                barHeight: '35%',
                endingShape: 'rounded'
            }
        },
        dataLabels: {
            enabled: false
        },
        series: [{
            data: [80, 110, 80, 20, 60, 100],
            name: 'Employee'
        }],
        xaxis: {
            categories: ['UI/UX', 'Development', 'Management', 'HR', 'Testing', 'Marketing'],
            labels: {
                style: {
                    colors: '#111827',
                    fontSize: '13px',
                }
            }
        }
    }

    var chart = new ApexCharts(
        document.querySelector("#emp-department"),
        sBar
    );

    chart.render();
}

// Company Chart

if ($('#company-chart').length > 0) {
    var sColStacked = {
        chart: {
            height: 290,
            type: 'bar',
            toolbar: {
                show: false,
            }
        },
        colors: ['#212529'],
        responsive: [{
            breakpoint: 480,
            options: {
                legend: {
                    position: 'bottom',
                    offsetX: -10,
                    offsetY: 0
                }
            }
        }],
        plotOptions: {
            bar: {
                borderRadius: 10,
                borderRadiusWhenStacked: 'all',
                horizontal: false,
                endingShape: 'rounded',
                colors: {
                    backgroundBarColors: ['#f3f4f5'], // Background color for bars
                    backgroundBarOpacity: 0.5,
                    hover: {
                        enabled: true,
                        borderColor: '#F26522', // Color when hovering over the bar
                    }
                }
            },
        },
        series: [{
            name: 'Company',
            data: [40, 60, 20, 80, 60, 60, 60]
        }],
        xaxis: {
            categories: ['M', 'T', 'W', 'T', 'F', 'S', 'S'],
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '13px',
                }
            }
        },
        yaxis: {
            labels: {
                offsetX: -15,
                show: false
            }
        },
        grid: {
            borderColor: '#E5E7EB',
            strokeDashArray: 5,
            padding: {
                left: -8,
            },
        },
        legend: {
            show: false
        },
        dataLabels: {
            enabled: false // Disable data labels
        },
        fill: {
            opacity: 1
        },
    }

    var chart = new ApexCharts(
        document.querySelector("#company-chart"),
        sColStacked
    );

    chart.render();
}


// Plan Chart

if ($('#plan-overview').length > 0) {
    var donutChart = {
        chart: {
            height: 240,
            type: 'donut',
            toolbar: {
                show: false,
            }
        },
        colors: ['#FFC107', '#1B84FF', '#F26522'],
        series: [20, 60, 20],
        labels: ['Enterprise', 'Premium', 'Basic'],
        plotOptions: {
            pie: {
                donut: {
                    size: '50%',
                    labels: {
                        show: false
                    },
                    borderRadius: 30
                }
            }
        },
        stroke: {
            lineCap: 'round',
            show: true,
            width: 0,    // Space between donut sections
            colors: '#fff'
        },
        dataLabels: {
            enabled: false
        },
        legend: { show: false },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    height: 180,
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    }

    var donut = new ApexCharts(
        document.querySelector("#plan-overview"),
        donutChart
    );

    donut.render();
}

// sales income
if ($('#sales-income').length > 0) {
    var sColStacked = {
        chart: {
            height: 290,
            type: 'bar',
            stacked: true,
            toolbar: { show: false }
        },
        colors: ['#FF6F28', '#F8F9FA'],
        plotOptions: {
            bar: {
                columnWidth: '60%',       // Adjust for a cleaner pill-shape look
                borderRadius: 5,         // RADIUS FOR PILL EFFECT
                borderRadiusWhenStacked: 'all', // ENABLES INLINE ROUNDING FOR ALL SEGMENTS
                borderRadiusApplication: 'around', // ROUNDS TOP AND BOTTOM OF INLINE SEGMENTS
                horizontal: false,
            },
        },
        // Keep your existing series, xaxis, yaxis, grid, and tooltip configuration...
        series: [{
            name: 'Income',
            data: [40, 30, 45, 80, 85, 90, 80, 80, 80, 85, 20, 80]
        }, {
            name: 'Expenses',
            data: [60, 70, 55, 20, 15, 10, 20, 20, 20, 15, 80, 20]
        }],
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '13px',
                }
            }
        },
        tooltip: {
            enabled: true,
            shared: true,           // groups values from both series in one tooltip
            intersect: false,       // shows on category hover (better UX for columns)
            fillSeriesColor: false, // prevents colored dots/markers in tooltip
            theme: 'dark',          // base dark theme (helps with background)
            custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                // series[0] = Income, series[1] = Expenses
                var income = series[0][dataPointIndex];
                var expenses = series[1][dataPointIndex];

                // Format as $XXK (assuming your data is already scaled in thousands)
                var incomeStr = '$' + Math.round(income) + 'K';
                var expensesStr = '$' + Math.round(expenses) + 'K';

                return (
                    '<div style="background:#1F2937; color:#ffffff; padding:10px 14px; border-radius:8px; font-size:13.5px; box-shadow:0 6px 16px rgba(0,0,0,0.4); min-width:165px; line-height:1.5; font-family: inherit;">' +
                    '<div style="font-weight:600; margin-bottom:6px; display:flex; justify-content:space-between;">' +
                    '<span>Income</span>' +
                    '<span>' + incomeStr + '</span>' +
                    '</div>' +
                    '<div style="font-weight:600; display:flex; justify-content:space-between;">' +
                    '<span>Expenses</span>' +
                    '<span>' + expensesStr + '</span>' +
                    '</div>' +
                    '</div>'
                );
            }
        },
        yaxis: {
            labels: { offsetX: -15, style: { colors: '#6B7280', fontSize: '13px' } }
        },
        grid: {
            borderColor: '#E5E7EB',
            strokeDashArray: 5,
        },
        legend: { show: false },
        dataLabels: { enabled: false },
        tooltip: {
            enabled: true,
            shared: true,
            intersect: false,
            theme: 'dark',
            custom: function ({ series, dataPointIndex }) {
                var incomeStr = '$' + Math.round(series[0][dataPointIndex]) + 'K';
                var expensesStr = '$' + Math.round(series[1][dataPointIndex]) + 'K';
                return (
                    '<div style="background:#1F2937; color:#ffffff; padding:10px 14px; border-radius:8px; min-width:165px;">' +
                    '<div style="display:flex; justify-content:space-between;"><span>Income</span><span>' + incomeStr + '</span></div>' +
                    '<div style="display:flex; justify-content:space-between;"><span>Expenses</span><span>' + expensesStr + '</span></div>' +
                    '</div>'
                );
            }
        }
    }

    var chart = new ApexCharts(document.querySelector("#sales-income"), sColStacked);
    chart.render();
}

// Performance Chart
if ($('#performance_chart2').length > 0) {
    var options = {
        series: [{
            name: "performance",
            data: [20, 20, 35, 35, 40, 60, 60]
        }],
        chart: {
            height: 273,
            type: 'area',
            zoom: {
                enabled: false
            }
        },
        colors: ['#03C95A'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'straight'
        },
        title: {
            text: '',
            align: 'left'
        },
        grid: {
            padding : {
                left : -8,
                right : -4,
            }
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
        },
        yaxis: {
            min: 10,
            max: 60,
            tickAmount: 5,
            labels: {
                offsetX : -15,
                formatter: (val) => {
                    return val / 1 + 'K'
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left'
        }
    };

    var chart = new ApexCharts(document.querySelector("#performance_chart2"), options);
    chart.render();
}

// Deals Stage

if ($('#deals_stage').length > 0) {
    var sColStacked = {
        chart: {
            height: 310,
            type: 'bar',
            stacked: true,
            toolbar: {
                show: false,
            }
        },
        colors: ['#FF6F28', '#F8F9FA'],
        responsive: [{
            breakpoint: 480,
            options: {
                legend: {
                    position: 'bottom',
                    offsetX: -10,
                    offsetY: 0
                }
            }
        }],
        plotOptions: {
            bar: {
                borderRadius: 20,
                horizontal: false,
                endingShape: 'rounded'
            },
        },
        series: [{
            name: 'Income',
            data: [80, 40, 100, 20]
        }, {
            name: 'Expenses',
            data: [100, 100, 100, 100]
        }],
        xaxis: {
            categories: ['Inpipeline', 'Follow Up', 'Schedule', 'Conversion'],
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '13px',
                }
            }
        },
        yaxis: {
            labels: {
                offsetX: -15,
                style: {
                    colors: '#6B7280',
                    fontSize: '13px',
                }
            }
        },
        grid: {
            borderColor: '#E5E7EB',
            strokeDashArray: 5
        },
        legend: {
            show: false
        },
        dataLabels: {
            enabled: false // Disable data labels
        },
        fill: {
            opacity: 1
        },
    }

    var chart = new ApexCharts(
        document.querySelector("#deals_stage"),
        sColStacked
    );

    chart.render();
}
if ($('#top_deal_chart').length > 0) {
    var options = {
        series: [{
            name: 'Series 1',
            data: [80, 50, 30, 40, 100, 20],
            color: '#F37438',

        }, {
            name: 'Series 2',
            data: [20, 30, 40, 80, 20, 80],
            color: '#B359C3',
        }, {
            name: 'Series 3',
            data: [44, 76, 78, 13, 43, 10],
            color: '#1CCE6B',
        }],
        chart: {
            height: 200,
            type: 'radar',
            dropShadow: {
                enabled: true,
                blur: 1,
                left: 1,
                top: 1
            }
        },
        plotOptions: {
            radar: {
                spiderWeb: false
            }
        },
        stroke: {
            width: 2,
            curve: 'smooth'
        },
        fill: {
            opacity: 1
        },
        markers: {
            size: 0
        },
        yaxis: {
            stepSize: 20
        },
        legend: {
            show: false,
        },
        xaxis: {
            categories: ['Mar', 'Feb', 'Jan', 'Aug', 'Jul', 'Jun', 'May', 'Apr']
        }
    };

    var chart = new ApexCharts(document.querySelector("#top_deal_chart"), options);
    chart.render();
}

// Pipeline Chart
if ($('#pipeline_chart').length > 0) {
    var options = {
        series: [{
            name: "Pipeline",
            data: [130, 110, 90, 70, 50, 40],
        }],
        chart: {
            type: 'bar',
            height: 300,
            toolbar: { show: false },
            sparkline: { enabled: true }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                distributed: true,
                barHeight: '100%',
                borderRadius: 10,
                borderRadiusApplication: 'around',
                isFunnel: true,
            },
        },
        colors: ['#F26522', '#F37438', '#F5844E', '#F69364', '#F7A37A', '#F9B291'],
        dataLabels: {
            enabled: true,
            textAnchor: 'middle',
            formatter: function (val, opt) {
                return opt.w.globals.labels[opt.dataPointIndex]
            },
            style: {
                fontSize: '14px',
                fontFamily: 'Public Sans, sans-serif',
                fontWeight: 500,
                colors: ['#fff']
            },
            dropShadow: { enabled: false }
        },
        xaxis: {
            categories: [
                'Marketing : 7,898', 'Sales : 4,658', 'Email : 2,898',
                'Chat : 789', 'Operational : 655', 'Calls : 454'
            ],
            min: -25,
            max: 125
        },
        tooltip: { enabled: false },
        states: {
            hover: { filter: { type: 'none' } },
            active: { filter: { type: 'none' } }
        },
        grid: {
            padding: {
                top: 20,
                bottom: 20,
                left: 20,
                right: 20
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#pipeline_chart"), options);
    chart.render();
}

// Leads Stage

if ($('#leads_stage').length > 0) {
    var sColStacked = {
        chart: {
            height: 345,
            type: 'bar',
            stacked: true,
            toolbar: {
                show: false,
            }
        },
        colors: ['#FF6F28', '#F8F9FA'],
        responsive: [{
            breakpoint: 480,
            options: {
                legend: {
                    position: 'bottom',
                    offsetX: -10,
                    offsetY: 0
                }
            }
        }],
        plotOptions: {
            bar: {
                borderRadius: 5,
                borderRadiusWhenStacked: 'all',
                horizontal: false,
                endingShape: 'rounded'
            },
        },
        series: [{
            name: 'Income',
            data: [80, 40, 60, 40]
        }, {
            name: 'Expenses',
            data: [100, 100, 100, 100]
        }],
        xaxis: {
            categories: ['Competitor', 'Budget', 'Unresponsie', 'Timing'],
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '9px',
                }
            }
        },
        yaxis: {
            labels: {
                offsetX: -15,
                style: {
                    colors: '#6B7280',
                    fontSize: '10px',
                }
            }
        },
        grid: {
            borderColor: '#E5E7EB',
            strokeDashArray: 5
        },
        legend: {
            show: false
        },
        dataLabels: {
            enabled: false // Disable data labels
        },
        fill: {
            opacity: 1
        },
    }

    var chart = new ApexCharts(
        document.querySelector("#leads_stage"),
        sColStacked
    );

    chart.render();
}

if ($('#donut-chart-2').length > 0) {
    var options = {
        series: [25, 30, 10, 35], // Percentages for each section
        chart: {
            type: 'donut',
            height: 185,
        },
        labels: ['Paid', 'Google', 'Referals', 'Campaigns'], // Labels for the data
        colors: ['#FFC107', '#0C4B5E', '#AB47BC', '#FD3995'], // Colors from the image
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Google',
                            formatter: function (w) {
                                return '40%';
                            }
                        }
                    }
                }
            }
        },
        stroke: {

            lineCap: 'round',
            show: true,
            width: 0,    // Space between donut sections
            colors: '#fff'
        },
        legend: {
            show: false,
        },
        dataLabels: {
            enabled: false
        },
        label: {
            show: false,
        }
    };

    var chart = new ApexCharts(document.querySelector("#donut-chart-2"), options);
    chart.render();
}

if ($('#donut-chart-3').length > 0) {
    var options = {
        series: [15, 10, 5, 10, 60], // Percentages for each section
        chart: {
            type: 'donut',
            height: 290,
        },
        labels: ['Paid', 'Google', 'Referals', 'Campaigns', 'Campaigns'], // Labels for the data
        colors: ['#F26522', '#FFC107', '#E70D0D', '#1B84FF', '#0C4B5E'], // Colors from the image
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Leads',
                            formatter: function (w) {
                                return '589';
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            show: false,
        },
        label: {
            show: false,
        }
    };

    var chart = new ApexCharts(document.querySelector("#donut-chart-3"), options);
    chart.render();
}

// Revenue income

if ($('#revenue-income').length > 0) {
    var sColStacked = {
        chart: {
            height: 280,
            type: 'bar',
            stacked: true, // This stays true to stack the colors
            toolbar: { show: false }
        },
        // STEP 1: Define your gradient-step colors here
        colors: ['#f26522', '#0c4b5e', '#1b84ff', '#F8F9FA'],

        plotOptions: {
            bar: {
                borderRadius: 5,
                borderRadiusWhenStacked: 'last', // Only rounds the very top of the stack
                horizontal: false,
            },
        },

        // STEP 2: Divide your data into steps
        series: [
            {
                name: 'Income Base (25%)',
                data: [25, 25, 25, 25, 25, 25, 25, 25, 25, 25, 20, 25] // Adjust numbers as needed
            },
            {
                name: 'Income Mid (30%)',
                data: [30, 5, 20, 30, 30, 30, 30, 30, 30, 30, 0, 30]
            },
            {
                name: 'Income Top (5%)',
                data: [5, 0, 0, 25, 30, 35, 25, 25, 25, 30, 0, 25]
            },
            {
                name: 'Remaining/Expenses',
                data: [40, 70, 55, 20, 15, 10, 20, 20, 20, 15, 80, 20]
            }
        ],

        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            labels: {
                style: { colors: '#6B7280', fontSize: '13px' }
            }
        },
        yaxis: {
            min: 0,
            max: 100,
            labels: {
                offsetX: -15,
                style: { colors: '#6B7280', fontSize: '13px' },
                formatter: function (value) {
                    return value + "K";
                }
            }
        },
        grid: {
            borderColor: 'transparent',
            padding: { left: -8 }
        },
        legend: { show: false },
        dataLabels: { enabled: false },
        tooltip: {
            shared: true, // Shows all steps in one tooltip
            intersect: false,
            y: {
                formatter: function (val) {
                    return val + " k";
                }
            }
        },
        fill: {
            opacity: 1
        },
    }

    var chart = new ApexCharts(document.querySelector("#revenue-income"), sColStacked);
    chart.render();
}


if ($('#heat_chart').length > 0) {
    var options = {
        chart: {
            type: 'heatmap',
            height: 300,
        },
        colors: [
            "#9CA3AF",
            "#F37438",
            "#9CA3AF",
            "#F37438",
            "#9CA3AF",
            "#F37438",
        ],
        series: [
            {
                name: "0",
                data: [{
                    x: 'Mon',
                    y: 22
                },
                    {
                        x: 'Tue',
                        y: 29
                    },
                    {
                        x: 'Wed',
                        y: 13
                    },
                    {
                        x: 'Thu',
                        y: 32
                    },
                    {
                        x: 'Fri',
                        y: 32
                    },
                    {
                        x: 'Sat',
                        y: 32
                    },
                    {
                        x: 'Sun',
                        y: 32
                    },
                ]
            },
            {
                name: "20",
                data: [{
                    x: 'Mon',
                    y: 22,
                    color: '#ff5722'
                },
                    {
                        x: 'Tue',
                        y: 29
                    },
                    {
                        x: 'Wed',
                        y: 13
                    },
                    {
                        x: 'Thu',
                        y: 32
                    },
                    {
                        x: 'Fri',
                        y: 32
                    },
                    {
                        x: 'Sat',
                        y: 32
                    },
                    {
                        x: 'Sun',
                        y: 32
                    },
                ]
            },
            {
                name: "40",
                data: [{
                    x: 'Mon',
                    y: 22
                },
                    {
                        x: 'Tue',
                        y: 29
                    },
                    {
                        x: 'Wed',
                        y: 13
                    },
                    {
                        x: 'Thu',
                        y: 32
                    },
                    {
                        x: 'Fri',
                        y: 32
                    },
                    {
                        x: 'Sat',
                        y: 32
                    },
                    {
                        x: 'Sun',
                        y: 32
                    },
                ]
            },
            {
                name: "60",
                data: [{
                    x: 'Mon',
                    y: 0
                },
                    {
                        x: 'Tue',
                        y: 29
                    },
                    {
                        x: 'Wed',
                        y: 13
                    },
                    {
                        x: 'Thu',
                        y: 32
                    },
                    {
                        x: 'Fri',
                        y: 0
                    },
                    {
                        x: 'Sat',
                        y: 0
                    },
                    {
                        x: 'Sun',
                        y: 32
                    },
                ]
            },
            {
                name: "80",
                data: [{
                    x: 'Mon',
                    y: 0
                },
                    {
                        x: 'Tue',
                        y: 20
                    },
                    {
                        x: 'Wed',
                        y: 13
                    },
                    {
                        x: 'Thu',
                        y: 32
                    },
                    {
                        x: 'Fri',
                        y: 0
                    },
                    {
                        x: 'Sat',
                        y: 0
                    },
                    {
                        x: 'Sun',
                        y: 32
                    },
                ]
            },
            {
                name: "120",
                data: [{
                    x: 'Mon',
                    y: 0
                },
                    {
                        x: 'Tue',
                        y: 0
                    },
                    {
                        x: 'Wed',
                        y: 75
                    },
                    {
                        x: 'Thu',
                        y: 0
                    },
                    {
                        x: 'Fri',
                        y: 0
                    },
                    {
                        x: 'Sat',
                        y: 0
                    },
                    {
                        x: 'Sun',
                        y: 0
                    },
                ]
            },
        ]
    };
    var chart = new ApexCharts(document.querySelector("#heat_chart"), options);
    chart.render();
}

if ($('#payslip-chart').length > 0) {
    var options = {
        series: [{
            data: [22, 20, 30, 45, 55, 45, 20, 70, 25, 30, 10, 30]
        }],
        chart: {
            type: 'line',
            height: 200,
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            labels: {
            }
        },
        stroke: {
            curve: 'stepline',
        },
        dataLabels: {
            enabled: false
        },
        markers: {
            hover: {
                sizeOffset: 4
            }
        },
        colors: ['#FF5733'],
    };

    var chart = new ApexCharts(document.querySelector("#payslip-chart"), options);
    chart.render();
}

if ($('#user-chart').length > 0) {
    var options = {
        series: [{
            name: 'Data',
            data: [34, 44, 54, 21, 12, 43, 33, 23, 66, 66, 58, 29] // Sample data for each month
        }],
        chart: {
            type: 'bar',
            height: 185
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                endingShape: 'rounded'
            },
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], // Months
        },
        fill: {
            opacity: 1
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " units";
                }
            }
        },
        colors: ['#00E396'] // Bar color (green in this case)
    };

    var chart = new ApexCharts(document.querySelector("#user-chart"), options);
    chart.render();
}


if ($('#status-chart').length > 0) {
    var options = {
        // Data values are now populated to avoid Syntax Errors
        series: [
            { name: 'Full Time', data: [1054] },
            { name: 'Contract', data: [568] },
            { name: 'Probation', data: [80] }
        ],
        chart: {
            type: 'bar',
            height: 45,
            stacked: true,
            stackType: '100%',
            toolbar: { show: false },
            sparkline: { enabled: true }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: '100%',
            }
        },
        colors: ['#F26522', '#0C4B5E', '#F8F9FA'],
        fill: {
            type: 'pattern',
            opacity: 1,
            pattern: {
                style: 'verticalLines',
                width: 6,
                strokeWidth: 4
            }
        },
        tooltip: { enabled: true },
        xaxis: { categories: ['Total'] }
    };

    var chart = new ApexCharts(document.querySelector("#status-chart"), options);
    chart.render();

}

if ($('#employee-distribution').length > 0) {
    var sColStacked = {
        colors: ['#FF7129'],
        chart: {
            height: 340,
            type: 'bar',
            toolbar: {
                show: false,
            },
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical', // Top to bottom (180deg)
                shadeIntensity: 0.5,
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100],
                colorStops: [
                    {
                        offset: 0,
                        color: '#FF7129', // Top color
                        opacity: 0.5
                    },
                    {
                        offset: 100,
                        color: '#FFFFFF', // Bottom color
                        opacity: 1,
                        opacity: 0.5
                    }
                ]
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                legend: {
                    position: 'bottom',
                    offsetY: 10
                }
            }
        }],
        plotOptions: {
            bar: {
                columnWidth: '90%',
                borderRadius: 10,
                borderRadiusWhenStacked: 'all',
                horizontal: false,
                endingShape: 'rounded', dataLabels: {
                    position: 'bottom', // Anchors the label to the base of the bar
                },
                colors: {
                    backgroundBarColors: ['#F8F9FA'], // Background color for bars
                    backgroundBarOpacity: 0.5,
                    backgroundBarRadius: 10,
                    hover: {
                        enabled: true,
                        borderColor: '#f2652247', // Color when hovering over the bar
                    }
                }
            },
        },
        series: [{
            name: 'Company',
            data: [40, 20, 35, 10]
        }],
        dataLabels: {
            enabled: true, // Must be true
            position: 'bottom',
            formatter: function (val) {
                return val + "%"; // Display the raw value
            },
            offsetY: 10, // Adjust this value to nudge the text up or down from the bottom
            style: {
                fontSize: '12px',
                colors: ['#111827'], // Gray color to match your design
                fontWeight: 'bold'
            }
        },
        xaxis: {
            categories: ['Sales', 'Front End', 'React', 'UI'],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: {
                    colors: '#111827',
                    fontSize: '13px',
                }
            }
        },
        yaxis: {
            min: 0,
            max: 50,
            labels: {
                show: false
            }
        },
        grid: {
            show: false,
            strokeDashArray: 5,
            padding: {
                left: -10,
                right: -30,
                bottom: -10
            },
        },
        legend: {
            show: false
        },
    }

    var chart = new ApexCharts(
        document.querySelector("#employee-distribution"),
        sColStacked
    );

    chart.render();
}

if ($('#payroll-chart').length > 0) {
    var sCol = {
        chart: {
            width: 100,
            height: 40,
            type: 'bar',
            toolbar: { show: false },
            sparkline: { enabled: true }
        },
        dataLabels: { enabled: false },
        series: [{
            name: 'Payroll',
            data: [45, 15, 30, 25, 20, 45, 40] // You can adjust these
        }],
        colors: ['#0C4B5E'],
        plotOptions: {
            bar: {
                borderRadius: 4,
                borderRadiusWhenStacked: 'all',
                borderRadiusApplication: 'around', // Ensures top-only rounding for vertical bars
                endingShape: 'around',
                colors: {
                    backgroundBarColors: ['#F8F9FA'], // Background color for bars
                    backgroundBarOpacity: 0.5,
                    backgroundBarRadius: 4,
                    hover: {
                        enabled: true,
                        borderColor: '#F26522', // Color when hovering over the bar
                    }
                }
            },
        },
        xaxis: {
            labels: { show: false },
            axisTicks: { show: false },
            axisBorder: { show: false }
        },
        yaxis: { show: false },
        grid: { show: false },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#payroll-chart"), sCol);
    chart.render();
}

if ($('#deduction-chart').length > 0) {
    var sCol = {
        chart: {
            width: 100,
            height: 70,
            type: 'bar',
            stacked: true,
            toolbar: { show: false },
            sparkline: { enabled: true },
        },
        dataLabels: { enabled: false },
        series: [{
            name: 'Deduction',
            // Example data where total bar height is represented by absolute values
            data: [15, 40, 30, 35, 40, 35, 32]
        }, {
            name: 'Deduction',
            // The mirror values for the left side (negative)
            data: [-15, -40, -30, -35, -40, -35, -32]
        }],
        colors: ['#F26522'],
        plotOptions: {
            bar: {
                borderRadius: 4,
                colors: {
                    backgroundBarColors: ['#F8F9FA'], // Background color for bars
                    backgroundBarOpacity: 0.5,
                    backgroundBarRadius: 4,
                    hover: {
                        enabled: true,
                    }
                }
            },
        },
        xaxis: {
            labels: { show: false },
            axisTicks: { show: false },
            axisBorder: { show: false }
        },
        yaxis: {
            min: -50,
            max: 50,
            show: false
        },
        grid: { show: false },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#deduction-chart"), sCol);
    chart.render();
}

if ($('#attendance-chart').length > 0) {
    var options = {
        // 1. Series Data (You need actual data arrays here)
        series: [{
            name: 'Present',
            data: [600, 300, 300, 700, 400, 600, 600]
        }, {
            name: 'Absent',
            data: [50, 100, 50, 50, 100, 100, 100]
        }, {
            name: 'Late',
            data: [100, 50, 150, 100, 150, 50, 150]
        }],

        // 2. Chart Type (Grouped columns by default)
        chart: {
            type: 'bar',
            height: 260,
            stacked: false,
            toolbar: { show: false },
            sparkline: { enabled: false }
        },

        // 3. Colors (Orange, Dark Teal, Yellow)
        colors: ['#F26522', '#004C6D', '#FFC700'],

        // 4. Plot Options (Controls the 'boxes' look and spacing)
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '50%',
                borderRadius: 2,
                borderRadiusApplication: 'end',
                colors: {
                    backgroundBarColors: ['#F8F9FA'], // Background color for bars
                    backgroundBarOpacity: 0.5,
                    hover: {
                        enabled: true,
                    },
                }
            }
        },
        stroke: {
            show: true,
            width: 2,                // 🔹 THIS creates the inner gap
            colors: ['transparent'] // 🔹 keeps background clean
        },
        fill: {
            type: 'pattern',
            opacity: 1,
            pattern: {
                style: 'horizontalLines',
                width: 5,
                height: 20,
                strokeWidth: 24
            }
        },

        // 5. Axes Configuration
        xaxis: {
            categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { rotate: 0, style: { colors: '#6B7280' } }
        },
        yaxis: {
            show: true,
            min: 0,
            max: 800,
            labels: {
                offsetX: -16
            }
        },

        // 6. Grid (Subtle background lines)
        grid: {
            show: true,
            borderColor: '#E5E7EB',
            strokeDashArray: 3,
            padding: {
                left: 0
            }
        },

        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#attendance-chart"), options);
    chart.render();
}


// Leave Chart
if ($('#leave-chart').length > 0) {
    var options = {
        // Use placeholder percentage values for the series data
        series: [85, 50, 20], // Example data: 85% for Present, 50% for Absent, 20% for Late

        chart: {
            type: 'radialBar',
            width: 150,
            height: 150, // Adjust height as needed
            sparkline: {
                enabled: true
            }
        },

        plotOptions: {
            radialBar: {
                startAngle: -90, // Start from the left horizontal axis
                endAngle: 90,   // End at the right horizontal axis
                offsetY: 0, // Move the chart up slightly
                hollow: {
                    margin: 0,
                    size: '40%', // Adjust the inner circle size
                    background: 'transparent',
                },
                track: {
                    show: true,
                    background: '#F3F4F6', // Light gray background for the full track
                    strokeWidth: '97%',
                    margin: 5, // Space between the series
                },
                dataLabels: {
                    show: false, // Hide default percentage labels
                }
            }
        },

        fill: {
            // Manually define the colors to match your image
            colors: ['#F37438', '#F5844E', '#F69364']
        },

        // Add labels or tooltips if you want to show "Present", "Absent", "Late" names
        labels: ['Sick', 'Casual', 'Unpaid'],

        grid: {
            padding: {
                top: -50,
                left: -10,
                bottom: -150,
            }
        },

        legend: {
            show: false // Assuming you have a separate legend as implied by the dots in the image
        }
    };
    var chart = new ApexCharts(document.querySelector("#leave-chart"), options);
    chart.render();
}


// Insurence Chart Pay roll Dashboard
if ($('#insurence-chart').length > 0) {
    var sCol = {
        chart: {
            width: 100,
            height: 45,
            type: 'bar',
            stacked: true, // Keep stacked for the mirrored effect
            toolbar: { show: false },
            sparkline: { enabled: true },
        },
        plotOptions: {
            bar: {
                // Adjust this percentage until the bars look exactly 8px wide
                columnWidth: '45%',
                borderRadius: 2,
                colors: {
                    // This creates the light background "track" behind the bars
                    backgroundBarColors: ['#F8F9FA', '#F8F9FA', '#F8F9FA', '#F8F9FA', '#F8F9FA'],
                    backgroundBarOpacity: 1,
                    backgroundBarRadius: 5,
                },
            },
        },
        series: [{
            name: 'Positive',
            data: [15, 40, 30, 35, 40, 35, 32]
        }, {
            name: 'Negative',
            data: [-15, -40, -30, -35, -40, -35, -32]
        }],
        colors: ['#F26522'], // Your orange color
        grid: { show: false },
        xaxis: { labels: { show: false } },
        yaxis: {
            min: -50,
            max: 50,
            show: false
        },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#insurence-chart"), sCol);
    chart.render();
}

if ($('#learn-employee').length > 0) {

    var options = {
        series: [
            {
                name: 'Inprogress',
                type: 'column',
                data: [50, 70, 60, 180, 120, 90, 140, 80, 130, 100, 90, 75]
            },
            {
                name: 'Completed',
                type: 'column',
                data: [90, 130, 170, 260, 150, 130, 180, 220, 200, 280, 240, 300]
            },
            {
                name: 'Total Employees',
                type: 'line',
                data: [140, 200, 260, 460, 310, 260, 370, 340, 370, 420, 350, 430]
            }
        ],

        chart: {
            height: 320,
            type: 'line',
            stacked: true,
            toolbar: { show: false }
        },

        stroke: {
            width: [0, 0, 3],
            curve: 'smooth'
        },

        plotOptions: {
            bar: {
                columnWidth: '45%',
                borderRadius: 6
            }
        },

        markers: {
            size: 6,
            strokeWidth: 3,
            hover: { size: 7 }
        },

        colors: ['#FF7A45', '#0B3C49', '#FFC107'],

        dataLabels: {
            enabled: false
        },

        xaxis: {
            categories: [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ]
        },

        yaxis: {
            min: 0,
            max: 500,
            tickAmount: 5
        },

        legend: {
            position: 'bottom',
            markers: {
                radius: 12
            }
        },

        grid: {
            strokeDashArray: 4
        },

        tooltip: {
            shared: true,
            intersect: false
        }
    };

    var chart = new ApexCharts(
        document.querySelector("#learn-employee"),
        options
    );

    chart.render();
}

if ($('#enroll-course').length > 0) {

    var options = {
        series: [{
            name: 'Skill Level',
            data: [35, 55, 40, 15, 25, 45] // Git, HTML, Nodejs, MySQL, React, Java
        }],

        chart: {
            type: 'bar',
            height: 280,
            toolbar: { show: false }
        },

        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: '35%',
                borderRadius: 8,
                distributed: false
            }
        },

        colors: ['#F26522'], // Orange bar color

        dataLabels: {
            enabled: false
        },

        xaxis: {
            categories: ['Git', 'HTML', 'Nodejs', 'MySQL', 'React', 'Java'],
            min: 0,
            max: 60,
            tickAmount: 6,
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '12px'
                }
            }
        },

        yaxis: {
            labels: {
                style: {
                    colors: '#111827',
                    fontSize: '13px',
                    fontWeight: 500
                }
            }
        },

        grid: {
            borderColor: '#E5E7EB',
            strokeDashArray: 4,
            xaxis: {
                lines: { show: true }
            },
            yaxis: {
                lines: { show: false }
            }
        },

        tooltip: {
            y: {
                formatter: val => `${val} hrs`
            }
        }
    };

    var chart = new ApexCharts(
        document.querySelector("#enroll-course"),
        options
    );

    chart.render();
}

if ($('#certification-chart').length > 0) {

    var options = {
        series: [{
            name: 'Performance',
            data: [20, 45, 30, 60, 40, 70, 35, 55, 30, 65]
        }],

        chart: {
            type: 'area',
            height: 230,
            toolbar: { show: false },
            zoom: { enabled: false }
        },

        stroke: {
            curve: 'smooth',
            width: 4,
            colors: ['#F26522']
        },

        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.45,
                opacityTo: 0.05,
                stops: [0, 90, 100],
                colorStops: [
                    {
                        offset: 0,
                        color: '#F26522',
                        opacity: 0.45
                    },
                    {
                        offset: 100,
                        color: '#FFFFFF',
                        opacity: 0
                    }
                ]
            }
        },

        markers: {
            size: 0
        },

        dataLabels: {
            enabled: false
        },

        xaxis: {
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },

        yaxis: {
            labels: { show: false }
        },

        grid: {
            show: false
        },

        tooltip: {
            enabled: true,
            y: {
                formatter: val => val
            }
        }
    };

    var chart = new ApexCharts(
        document.querySelector("#certification-chart"),
        options
    );

    chart.render();
}

// Contribution Chart Pay roll Dashboard
if ($('#contribution-chart').length > 0) {
    var sCol = {
        chart: {
            width: 100,
            height: 45,
            type: 'bar',
            stacked: true,
            toolbar: { show: false },
            sparkline: { enabled: true },
        },
        plotOptions: {
            bar: {
                columnWidth: '45%', // Keeps the bar width at roughly 8px
                borderRadius: 2,
                colors: {
                    backgroundBarColors: ['#F8F9FA', '#F8F9FA', '#F8F9FA', '#F8F9FA', '#F8F9FA'],
                    backgroundBarOpacity: 1,
                    backgroundBarRadius: 5,
                },
            },
        },
        series: [{
            name: 'Positive',
            data: [10, 30, 20, 25, 30, 25, 22]
        }, {
            name: 'Negative',
            data: [-10, -30, -20, -25, -30, -25, -22]
        }],
        colors: ['#0C4B5E'], // Dark Teal color from your design
        grid: {
            show: false,
            padding: {
                left: 0,
                right: 0
            }
        },
        xaxis: {
            labels: { show: false },
            axisBorder: { show: false }, // Removes the bottom/center line
            axisTicks: { show: false }
        },
        yaxis: {
            min: -50,
            max: 50,
            show: false,
            axisBorder: { show: false }, // Ensures no vertical center line remains
        },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#contribution-chart"), sCol);
    chart.render();
}

// Health Chart Pay roll Dashboard
if ($('#health-chart').length > 0) {
    var sCol = {
        chart: {
            width: 100,
            height: 45,
            type: 'bar',
            stacked: true,
            toolbar: { show: false },
            sparkline: { enabled: true },
        },
        plotOptions: {
            bar: {
                columnWidth: '45%', // Keeps the bar width at roughly 8px
                borderRadius: 2,
                colors: {
                    backgroundBarColors: ['#F8F9FA', '#F8F9FA', '#F8F9FA', '#F8F9FA', '#F8F9FA'],
                    backgroundBarOpacity: 1,
                    backgroundBarRadius: 5,
                },
            },
        },
        series: [{
            name: 'Positive',
            data: [20, 40, 30, 35, 40, 35, 32]
        }, {
            name: 'Negative',
            data: [-20, -40, -30, -35, -40, -35, -32]
        }],
        colors: ['#FFC107'], // Dark Teal color from your design
        grid: {
            show: false,
            padding: {
                left: 0,
                right: 0
            }
        },
        xaxis: {
            labels: { show: false },
            axisBorder: { show: false }, // Removes the bottom/center line
            axisTicks: { show: false }
        },
        yaxis: {
            min: -50,
            max: 50,
            show: false,
            axisBorder: { show: false }, // Ensures no vertical center line remains
        },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#health-chart"), sCol);
    chart.render();
}

// Distribution Chart Pay roll Dashboard
// Distribution Chart Payroll Dashboard
if ($('#distribution-chart').length > 0) {
    var sCol = {
        chart: {
            width: '100%',
            height: 240,
            type: 'bar',
            toolbar: { show: false },
        },
        plotOptions: {
            bar: {
                columnWidth: '15px',
                borderRadius: 6,
                borderRadiusApplication: 'around',
                distributed: true, // This allows each bar to have its own color from the array below
            },
        },
        // CHANGED: Second color is now Blue (#007BFF), others are light blue/grey
        colors: ['#E5E7EB', '#f26522', '#E5E7EB', '#E5E7EB', '#E5E7EB', '#E5E7EB', '#E5E7EB', '#E5E7EB', '#E5E7EB', '#E5E7EB', '#E5E7EB'],

        dataLabels: { enabled: false },
        series: [{
            name: 'Income Range',
            data: [40, 96, 50, 40, 65, 45, 75, 70, 60, 45, 80]
        }],
        grid: {
            show: true,
            borderColor: '#F3F4F6',
            strokeDashArray: 3,
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: { left: 10 }
        },
        xaxis: {
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false }
        },
        yaxis: {
            min: 0,
            max: 100,
            tickAmount: 4,
            labels: {
                show: true,
                align: 'left',
                style: {
                    colors: '#6B7280',
                    fontSize: '13px'
                },
                offsetX: 6,
                formatter: function (val) {
                    if (val === 25) return "$30k-50k";
                    if (val === 50) return "$50k-80k";
                    if (val === 75) return "$80k-120k";
                    if (val === 100) return "$120k-180k";
                    return "";
                }
            },
            axisBorder: { show: false }
        },
        legend: { show: false },
        annotations: {
            points: [
                // CHANGED: Match the marker color to the blue bar
                { x: 2, y: 0, marker: { size: 5, fillColor: '#E5E7EB', strokeColor: '#fff', strokeWidth: 2 } }
            ]
        },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#distribution-chart"), sCol);
    chart.render();
}

// Trend Chart Payroll Dashboard
if ($('#chart-trend').length > 0) {
    var options = {
        series: [
            {
                name: "Line 1",
                data: [300, 300, 225, 225, 225], // 5 data points
                color: '#12434e'
            },
            {
                name: "Line 2",
                data: [150, 150, 5, 75, 150], // 5 data points
                color: '#f46a25'
            }
        ],
        chart: {
            type: 'line',
            height: 305,
            toolbar: { show: false }
        },
        stroke: {
            curve: 'stepline',
            width: 2.5
        },
        markers: {
            size: 5,
            strokeWidth: 0,
            hover: { sizeOffset: 2 }
        },
        xaxis: {
            // 5 categories starting from Jan
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
            labels: {
                show: true, // Bottom labels remain hidden as requested
                style: {
                    colors: '#6B7280',
                    fontSize: '13px',
                    fontweight: '500',
                },
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            min: 0,
            max: 350,
            tickAmount: 4,
            labels: {
                align: 'left', // Left aligned
                offsetX: -15,
                style: {
                    colors: '#6B7280',
                    fontSize: '13px',
                    fontweight: '500',
                },
                formatter: function (val) {
                    return "$" + val + "k";
                }
            }
        },
        grid: {
            borderColor: '#f1f1f1',
            xaxis: {
                lines: { show: true }
            },
            yaxis: {
                lines: { show: true }
            },
            padding : {
                right : -7
            }
        },
        title: {
            text: undefined
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            show: false
        }
    };
    var chart = new ApexCharts(document.querySelector("#chart-trend"), options);
    chart.render();
}

// Process Chart Payroll Dashboard
if ($('#process-chart').length > 0) {
    var options = {
        chart: {
            type: 'bar',
            height: 250,
            stacked: true,
            toolbar: { show: false },
            // offsetX: -15 pulls the SVG container to the far left edge
            offsetX: -15,
            sparkline: { enabled: false }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: '50px', // Thickness of the pills
                borderRadius: 0, // Increased for a perfect "pill" shape
                borderRadiusApplication: 'around',
                borderRadiusWhenStacked: 'all'
            }
        },
        series: [
            { name: 'Bar 1', data: [35] },
            { name: 'Gap 1', data: [5] },  // Adjusted gap
            { name: 'Bar 2', data: [30] },
            { name: 'Gap 2', data: [5] },
            { name: 'Bar 3', data: [25] }
        ],
        colors: ['#F26522', 'transparent', '#0D4F5E', 'transparent', '#1F2937'],
        grid: {
            show: true,
            borderColor: '#E5E7EB',
            strokeDashArray: 4,
            xaxis: {
                lines: { show: true } // Vertical dashed lines
            },
            yaxis: {
                lines: { show: false }
            },
            padding: {
                left: 0,   // Remove left padding
                right: 0,
                top: 180,  // Increase this value to push bars further to the BOTTOM
                bottom: 0
            }
        },
        xaxis: {
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false },
            // tickPlacement 'on' ensures the first bar pins to the start
            tickPlacement: 'on',
            max: 100
        },
        yaxis: {
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        fill: {
            opacity: 1
        },
        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: {
            enabled: true,
            y: {
                formatter: function (val, { seriesIndex }) {
                    if (seriesIndex === 1 || seriesIndex === 3) return null;
                    return val + "%";
                }
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#process-chart"), options);
    chart.render();
}

// Arrival Chart 1 Payroll Dashboard
if ($('#arrival-chart-1').length > 0) {
    var sCol = {
        chart: {
            width: 70,
            height: 35, // Container height
            type: 'bar',
            stacked: true,
            toolbar: { show: false },
            sparkline: { enabled: true },
        },
        plotOptions: {
            bar: {
                columnWidth: '35%',
                borderRadius: 2,
                // Distributed allows us to apply the colors array to each bar index
                distributed: true,
            },
        },
        series: [{
            name: 'Positive',
            data: [30, 30, 30, 30, 30, 30, 30, 30] // Even height 30
        }, {
            name: 'Negative',
            data: [-30, -30, -30, -30, -30, -30, -30, -30] // Even height 30
        }],
        // First 3 bars get the brand color, last 2 get light background
        colors: [
            '#F26522', '#F26522', '#F26522', '#F26522', '#F26522', '#F26522', // First 3 (Filled)
            '#F1F1F1', '#F1F1F1'              // Last 2 (Light Bg)
        ],
        grid: {
            show: false,
            padding: {
                left: 0,
                right: 0
            }
        },
        xaxis: {
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            min: -40,
            max: 40,
            show: false,
            axisBorder: { show: false },
        },
        legend: { show: false },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#arrival-chart-1"), sCol);
    chart.render();
}

// Arrival Chart 1 Payroll Dashboard
if ($('#arrival-chart-2').length > 0) {
    var sCol = {
        chart: {
            width: 70,
            height: 35, // Container height
            type: 'bar',
            stacked: true,
            toolbar: { show: false },
            sparkline: { enabled: true },
        },
        plotOptions: {
            bar: {
                columnWidth: '35%',
                borderRadius: 2,
                // Distributed allows us to apply the colors array to each bar index
                distributed: true,
            },
        },
        series: [{
            name: 'Positive',
            data: [30, 30, 30, 30, 30, 30, 30, 30] // Even height 30
        }, {
            name: 'Negative',
            data: [-30, -30, -30, -30, -30, -30, -30, -30] // Even height 30
        }],
        // First 3 bars get the brand color, last 2 get light background
        colors: [
            '#F26522', '#F26522', '#F26522', '#F26522', '#F26522', // First 3 (Filled)
            '#F1F1F1', '#F1F1F1', '#F1F1F1'              // Last 2 (Light Bg)
        ],
        grid: {
            show: false,
            padding: {
                left: 0,
                right: 0
            }
        },
        xaxis: {
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            min: -40,
            max: 40,
            show: false,
            axisBorder: { show: false },
        },
        legend: { show: false },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#arrival-chart-2"), sCol);
    chart.render();
}

// Arrival Chart 1 Payroll Dashboard
if ($('#arrival-chart-3').length > 0) {
    var sCol = {
        chart: {
            width: 70,
            height: 35, // Container height
            type: 'bar',
            stacked: true,
            toolbar: { show: false },
            sparkline: { enabled: true },
        },
        plotOptions: {
            bar: {
                columnWidth: '35%',
                borderRadius: 2,
                // Distributed allows us to apply the colors array to each bar index
                distributed: true,
            },
        },
        series: [{
            name: 'Positive',
            data: [30, 30, 30, 30, 30, 30, 30, 30] // Even height 30
        }, {
            name: 'Negative',
            data: [-30, -30, -30, -30, -30, -30, -30, -30] // Even height 30
        }],
        // First 3 bars get the brand color, last 2 get light background
        colors: [
            '#F26522', '#F26522', '#F26522', '#F26522', // First 3 (Filled)
            '#F1F1F1', '#F1F1F1', '#F1F1F1', '#F1F1F1'              // Last 2 (Light Bg)
        ],
        grid: {
            show: false,
            padding: {
                left: 0,
                right: 0
            }
        },
        xaxis: {
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            min: -40,
            max: 40,
            show: false,
            axisBorder: { show: false },
        },
        legend: { show: false },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#arrival-chart-3"), sCol);
    chart.render();
}

// Arrival Chart 1 Payroll Dashboard
if ($('#arrival-chart-4').length > 0) {
    var sCol = {
        chart: {
            width: 70,
            height: 35, // Container height
            type: 'bar',
            stacked: true,
            toolbar: { show: false },
            sparkline: { enabled: true },
        },
        plotOptions: {
            bar: {
                columnWidth: '35%',
                borderRadius: 2,
                // Distributed allows us to apply the colors array to each bar index
                distributed: true,
            },
        },
        series: [{
            name: 'Positive',
            data: [30, 30, 30, 30, 30, 30, 30, 30] // Even height 30
        }, {
            name: 'Negative',
            data: [-30, -30, -30, -30, -30, -30, -30, -30] // Even height 30
        }],
        // First 3 bars get the brand color, last 2 get light background
        colors: [
            '#F26522', '#F26522', '#F26522', // First 3 (Filled)
            '#F1F1F1', '#F1F1F1', '#F1F1F1', '#F1F1F1', '#F1F1F1' // Last 2 (Light Bg)
        ],
        grid: {
            show: false,
            padding: {
                left: 0,
                right: 0
            }
        },
        xaxis: {
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            min: -40,
            max: 40,
            show: false,
            axisBorder: { show: false },
        },
        legend: { show: false },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#arrival-chart-4"), sCol);
    chart.render();
}

// MFA Status IT Dashboard
if ($('#mfa-chart').length > 0) {
    const totalBlocks = 15;
    const filledBlocks = 11;

    var options = {
        series: [{
            data: Array(totalBlocks).fill(1)
        }],

        chart: {
            type: 'bar',
            height: 16, // FIXED HEIGHT: 16px
            width: '100%',
            toolbar: { show: false },
            sparkline: { enabled: true },
        },

        plotOptions: {
            bar: {
                distributed: true,
                // Adjusted to create a wider 12px gap between the 16px circles
                columnWidth: '85%',
                borderRadius: 8,     // 50% border-radius for perfect circles
                borderRadiusApplication: 'around',
            }
        },

        colors: [
            ({ dataPointIndex }) =>
                dataPointIndex < filledBlocks
                    ? '#F26522' // Active Orange
                    : '#E5E7EB' // Inactive Gray
        ],

        dataLabels: { enabled: false },
        grid: { show: false },
        xaxis: {
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: { show: false, max: 1 },
        tooltip: { enabled: false },

        states: {
            hover: { filter: { type: 'none' } },
            active: { filter: { type: 'none' } }
        }
    };

    new ApexCharts(document.querySelector("#mfa-chart"), options).render();
}

// Performance Chart IT Dashboard
if ($('#Usage-chart').length > 0) {
    var options = {
        series: [{
            name: "performance",
            // Data points adjusted to create that rising wave effect
            data: [1200, 1200, 4800, 4800, 2000, 6000, 6000, 8000, 8000]
        }],
        chart: {
            height: 220,
            type: 'area',
            toolbar: { show: false },
            zoom: { enabled: false },
            sparkline: { enabled: false }
        },
        // Match the dark teal/slate color from your screenshot
        colors: ['#2E5A65'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            curve: 'smooth',
            width: 3,
            lineCap: 'round'
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.5,
                opacityTo: 0.05,
                stops: [0, 90, 95]
            }
        },
        grid: {
            show: true,
            borderColor: '#E5E7EB',
            strokeDashArray: 0,
            xaxis: {
                lines: { show: true }
            },
            yaxis: {
                lines: { show: false },
            },
            padding: {
                left: 0,
                right: -20
            }
        },
        xaxis: {
            categories: ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', ''],
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '12px'
                }
            },
            axisBorder: { show: false },
            axisTicks: { show: true }
        },
        yaxis: {
            min: 0,
            max: 8000,
            tickAmount: 4,
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '12px'
                },
                formatter: (val) => {
                    return val === 0 ? '0' : (val / 1000) + 'K';
                },
                offsetX: -15
            }
        },
        tooltip: {
            enabled: true,
            theme: 'light',
            x: { show: false }
        },
        legend: {
            show: false
        }
    };

    var chart = new ApexCharts(document.querySelector("#Usage-chart"), options);
    chart.render();
}

// Mountain-style Failed IT Dashboard
if ($('#failed-chart').length > 0) {
    var options = {
        series: [{
            name: "performance",
            // Peaks and valleys to create the mountain look
            data: [10, 85, 15, 45, 20, 50, 30, 15]
        }],
        chart: {
            height: 45,
            width: 100, // Fixed height at 50px
            type: 'area',
            toolbar: { show: false },
            zoom: { enabled: false },
            // sparkline: true hides all X/Y axes, labels, and grid lines automatically
            sparkline: { enabled: true }
        },
        colors: ['#E70D0D'], // Pink color from your screenshot
        stroke: {
            show: true,
            curve: 'straight', // Sharp mountain peaks
            width: 2
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.6,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        // Ensure all axes and grids are off for a clean look
        grid: { show: false },
        xaxis: { labels: { show: false }, axisBorder: { show: false } },
        yaxis: { show: false },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#failed-chart"), options);
    chart.render();
}

// Mountain-style Alerts IT Dashboard
if ($('#alerts-chart').length > 0) {
    var options = {
        series: [{
            name: "performance",
            // Peaks and valleys to create the mountain look
            data: [10, 25, 15, 45, 20, 50, 30, 15]
        }],
        chart: {
            height: 45,
            width: 100, // Fixed height at 50px
            type: 'area',
            toolbar: { show: false },
            zoom: { enabled: false },
            // sparkline: true hides all X/Y axes, labels, and grid lines automatically
            sparkline: { enabled: true }
        },
        colors: ['#0C4B5E'], // Add color from your screenshot
        stroke: {
            show: true,
            curve: 'straight', // Sharp mountain peaks
            width: 2
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.6,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        // Ensure all axes and grids are off for a clean look
        grid: { show: false },
        xaxis: { labels: { show: false }, axisBorder: { show: false } },
        yaxis: { show: false },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#alerts-chart"), options);
    chart.render();
}

// Mountain-style Blocked IT Dashboard
if ($('#blocked-chart').length > 0) {
    var options = {
        series: [{
            name: "performance",
            // Peaks and valleys to create the mountain look
            data: [30, 15, 25, 35, 10, 40, 20, 45]
        }],
        chart: {
            height: 45,
            width: 100, // Fixed height at 50px
            type: 'area',
            toolbar: { show: false },
            zoom: { enabled: false },
            // sparkline: true hides all X/Y axes, labels, and grid lines automatically
            sparkline: { enabled: true }
        },
        colors: ['#FD3995'], // Add color from your screenshot
        stroke: {
            show: true,
            curve: 'straight', // Sharp mountain peaks
            width: 2
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.6,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        // Ensure all axes and grids are off for a clean look
        grid: { show: false },
        xaxis: { labels: { show: false }, axisBorder: { show: false } },
        yaxis: { show: false },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#blocked-chart"), options);
    chart.render();
}

// Performance Chart IT Dashboard - Final Mountain View
if ($('#logactivity-chart').length > 0) {
    var options = {
        series: [{
            name: "performance",
            // Data scaled down to fit the 0-800 range
            data: [650, 580, 700, 580, 680, 750, 620, 710, 580, 650, 750, 780, 620, 750, 650, 610, 780, 650, 750, 620, 720, 600, 780, 620, 750, 610, 710, 800, 620, 750, 610, 720, 800, 620, 710, 600, 720, 790, 620, 710, 600, 750, 620, 750, 620, 710, 800, 600, 750, 600]
        }],
        chart: {
            width: '100%',
            height: 310,
            type: 'area',
            toolbar: { show: false },
            sparkline: { enabled: false },
            zoom: { enabled: false }
        },
        colors: ['#F26522'],
        stroke: {
            show: true,
            curve: 'straight',
            width: 1.5
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.05,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            labels: {
                show: true,
                offsetX: 8,
                rotate: 0,
                style: {
                    colors: '#6B7280',
                    fontSize: '10px'
                },
                formatter: function (value, index) {
                    const labels = [
                        '09:00 AM', '10:00 AM', '11:00 AM', '12:00 PM',
                        '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM',
                        '05:00 PM', '06:00 PM', '07:00 PM', '08:00 PM',
                    ];
                    const step = Math.floor(50 / labels.length);
                    return index % step === 0 ? labels[Math.floor(index / step)] : '';
                }
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false }
        },

        // UPDATED Y-AXIS SECTION
        yaxis: {
            min: 0,
            max: 800,
            tickAmount: 4, // Intervals: 0, 200, 400, 600, 800
            labels: {
                show: true,
                align: 'left',
                minWidth: 40,
                style: {
                    colors: '#6B7280',
                    fontSize: '10px'
                },
                formatter: (val) => {
                    return val; // No longer dividing by 1000
                },
                offsetX: -28
            }
        },

        grid: {
            show: false,
            padding: {
                left: 10,
                right: 2,
                bottom: 10
            }
        },
        dataLabels: { enabled: false },
        tooltip: {
            enabled: true,
            theme: 'light'
        }
    };

    var chart = new ApexCharts(document.querySelector("#logactivity-chart"), options);
    chart.render();
}

// Storage chart IT Dashboard
if ($('#storages-chart').length > 0) {
    var sColStacked = {
        colors: ['#0C4B5E', '#0C4B5E', '#F26522', '#0C4B5E', '#0C4B5E', '#0C4B5E'],
        chart: {
            height: 264,
            type: 'bar',
            toolbar: { show: false },
        },
        plotOptions: {
            bar: {
                columnWidth: '65%',
                borderRadius: 10,
                distributed: true,
                colors: {
                    backgroundBarColors: ['#E5E7EB'],
                    backgroundBarOpacity: 0.4,
                    backgroundBarRadius: 10,
                },
                dataLabels: {
                    position: 'bottom',
                },
            },
        },
        series: [{
            name: 'Storage',
            data: [280, 260, 140, 68, 120, 260]
        }],
        dataLabels: {
            enabled: true,
            // Positive value pushes the label down to the base of the bar
            offsetY: 10,
            style: {
                fontSize: '14px',
                colors: ['#FFFFFF'],
                fontWeight: '500'
            },
            formatter: function (val) {
                return val + " GB";
            },
        },
        xaxis: {
            categories: ['HR', 'Payroll', 'Attendance', 'Recruitment', 'Leaves', 'Document'],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                offsetY: 5,
                style: {
                    colors: '#6B7280',
                    fontSize: '13px',
                    fontWeight: 500,
                }
            }
        },
        yaxis: {
            min: 0, // CRITICAL: Ensures bars start from the very bottom
            max: 320,
            tickAmount: 4,
            labels: {
                offsetX: -15,
                style: {
                    fontSize: '13px',
                    fontWeight: 500,
                    colors: '#6B7280',
                }
            }
        },
        grid: {
            show: true,
            borderColor: '#F3F4F6',
            strokeDashArray: 3,
            position: 'back',
            padding: {
                bottom: 0,
                left: -10,
                right: -55 // Removes extra space at the bottom
            },
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
        },
        legend: { show: false },
        tooltip: { enabled: true }
    }

    var chart = new ApexCharts(
        document.querySelector("#storages-chart"),
        sColStacked
    );

    chart.render();
}

// Insurence Chart IT Dashboard
if ($('#api-chart').length > 0) {
    var sCol = {
        chart: {
            width: 80,
            height: 60,
            type: 'bar',
            stacked: true, // Keep stacked for the mirrored effect
            toolbar: { show: false },
            sparkline: { enabled: true },
        },
        plotOptions: {
            bar: {
                // Adjust this percentage until the bars look exactly 8px wide
                columnWidth: '45%',
                borderRadius: 2,
                colors: {
                    // This creates the light background "track" behind the bars
                    backgroundBarColors: ['#F8F9FA', '#F8F9FA', '#F8F9FA', '#F8F9FA', '#F8F9FA'],
                    backgroundBarOpacity: 1,
                    backgroundBarRadius: 2,
                },
            },
        },
        series: [{
            name: 'Positive',
            data: [15, 40, 30, 35, 40, 35, 32]
        }, {
            name: 'Negative',
            data: [-15, -40, -30, -35, -40, -35, -32]
        }],
        colors: ['#F26522'], // Your orange color
        grid: { show: false },
        xaxis: { labels: { show: false } },
        yaxis: {
            min: -50,
            max: 50,
            show: false
        },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#api-chart"), sCol);
    chart.render();
}

// Tickets Chart IT Dashboard
if ($('#tickets-chart').length > 0) {
    var sCol = {
        chart: {
            width: 80,
            height: 50,
            type: 'bar',
            stacked: true, // Keep stacked for the mirrored effect
            toolbar: { show: false },
            sparkline: { enabled: true },
        },
        plotOptions: {
            bar: {
                // Adjust this percentage until the bars look exactly 8px wide
                columnWidth: '45%',
                borderRadius: 2,
                colors: {
                    // This creates the light background "track" behind the bars
                    backgroundBarColors: ['#F8F9FA', '#F8F9FA', '#F8F9FA', '#F8F9FA', '#F8F9FA'],
                    backgroundBarOpacity: 1,
                    backgroundBarRadius: 2,
                },
            },
        },
        series: [{
            name: 'Positive',
            data: [60, 0, 60, 0, 60, 0, 60]
        }, {
            name: 'Negative',
            data: [-60, -0, -60, -0, -60, -0, -60]
        }],
        colors: ['#F26522'], // Your orange color
        grid: { show: false },
        xaxis: { labels: { show: false } },
        yaxis: {
            min: -50,
            max: 50,
            show: false
        },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#tickets-chart"), sCol);
    chart.render();
}

// JObs Chart IT Dashboard
if ($('#jobs-chart').length > 0) {
    var sCol = {
        chart: {
            width: 80, // Increased width to fit all bars from screenshot
            height: 50,
            type: 'bar',
            toolbar: { show: false },
            sparkline: { enabled: true },
        },
        plotOptions: {
            bar: {
                columnWidth: '60%',
                borderRadius: 2,
                // distributed: true allows each bar to have its own color
                distributed: true,
            },
        },
        grid: {
            show: false,
            padding: {
                left: 2, // Provides room for Y-axis text
                right: 2,
                bottom: 2 // Ensures the last label isn't cut off
            }
        },
        // Your data values based on the screenshot heights
        series: [{
            name: 'Jobs',
            data: [45, 30, 70, 15, 45, 100, 45, 35, 25, 15, 25]
        }],
        // The first color is 'inactive', the 6th color (index 5) is 'active'
        colors: [
            '#E9ECEF', '#E9ECEF', '#E9ECEF', '#E9ECEF', '#E9ECEF',
            '#F26522', // This is your active orange bar
            '#E9ECEF', '#E9ECEF', '#E9ECEF', '#E9ECEF', '#E9ECEF'
        ],
        grid: { show: false },
        xaxis: { labels: { show: false }, axisBorder: { show: false } },
        yaxis: { show: false },
        tooltip: { enabled: true },
        legend: { show: false } // Hide legend caused by 'distributed'
    };

    var chart = new ApexCharts(document.querySelector("#jobs-chart"), sCol);
    chart.render();
}

// Uptime Chart IT Dashboard
if ($('#uptime-chart').length > 0) {
    var options = {
        series: [{
            name: "performance",
            // Data updated to follow a "Bell Curve" (low-high-low) for the mountain look
            data: [6, 20, 75, 40, 100, 92, 43, 76, 5]
        }],
        chart: {
            height: 50,
            width: 80,
            type: 'area',
            toolbar: { show: false },
            zoom: { enabled: false },
            sparkline: { enabled: true } // Set to true to hide axes for a cleaner "shape" look
        },
        // Using an orange gradient to match your uploaded image
        colors: ['#FF8C42'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            curve: 'smooth', // Crucial for the rounded mountain top
            width: 2,
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                inverseColors: false,
                opacityFrom: 0.6,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            show: false
        },
        grid: {
            show: false // Removes lines to focus on the mountain shape
        },
        tooltip: {
            enabled: false
        }
    };

    var chart = new ApexCharts(document.querySelector("#uptime-chart"), options);
    chart.render();
}

// Attendance chart IT Dashboard
if ($('#attendancetrend-chart').length > 0) {
    var sColStacked = {
        series: [{
            name: 'Attendance',
            data: [40, 22, 53, 25, 56, 90, 43, 25, 68, 80, 35, 20]
        }],
        chart: {
            height: 375,
            type: 'bar',
            toolbar: { show: false },
        },
        plotOptions: {
            bar: {
                columnWidth: '55%',
                borderRadius: 6,
                borderRadiusApplication: 'around',
                distributed: true,
                colors: {
                    backgroundBarColors: ['#F8F9FA'],
                    backgroundBarOpacity: 1,
                    backgroundBarRadius: 6,
                },
            },
        },
        // We set the base colors. For Jun (index 5), we use the primary start color.
        colors: [
            '#F4CACB', '#F4CACB', '#F4CACB', '#F4CACB', '#F4CACB',
            '#E70D0D', // Jun Start Color
            '#F4CACB', '#F4CACB', '#F4CACB', '#F4CACB', '#F4CACB', '#F4CACB'
        ],
        fill: {
            type: 'gradient',
            gradient: {
                type: 'vertical',
                shadeIntensity: 1,
                opacityFrom: 1,
                opacityTo: 1,
                colorStops: [
                    // This logic applies the specific gradient to Jun and light fade to others
                    [
                        { offset: 0, color: "#F4CACB", opacity: 1 },
                        { offset: 100, color: "#FDE0D3", opacity: 1 }
                    ],
                    [
                        { offset: 0, color: "#F4CACB", opacity: 1 },
                        { offset: 100, color: "#FDE0D3", opacity: 1 }
                    ],
                    [
                        { offset: 0, color: "#F4CACB", opacity: 1 },
                        { offset: 100, color: "#FDE0D3", opacity: 1 }
                    ],
                    [
                        { offset: 0, color: "#F4CACB", opacity: 1 },
                        { offset: 100, color: "#FDE0D3", opacity: 1 }
                    ],
                    [
                        { offset: 0, color: "#F4CACB", opacity: 1 },
                        { offset: 100, color: "#FDE0D3", opacity: 1 }
                    ],
                    [ // INDEX 5: JUNE GRADIENT (Figma: #E70D0D to #F26522)
                        { offset: 0, color: "#E70D0D", opacity: 1 },
                        { offset: 100, color: "#F26522", opacity: 1 }
                    ],
                    [
                        { offset: 0, color: "#F4CACB", opacity: 1 },
                        { offset: 100, color: "#FDE0D3", opacity: 1 }
                    ],
                    [
                        { offset: 0, color: "#F4CACB", opacity: 1 },
                        { offset: 100, color: "#FDE0D3", opacity: 1 }
                    ],
                    [
                        { offset: 0, color: "#F4CACB", opacity: 1 },
                        { offset: 100, color: "#FDE0D3", opacity: 1 }
                    ],
                    [
                        { offset: 0, color: "#F4CACB", opacity: 1 },
                        { offset: 100, color: "#FDE0D3", opacity: 1 }
                    ],
                    [
                        { offset: 0, color: "#F4CACB", opacity: 1 },
                        { offset: 100, color: "#FDE0D3", opacity: 1 }
                    ],
                    [
                        { offset: 0, color: "#F4CACB", opacity: 1 },
                        { offset: 100, color: "#FDE0D3", opacity: 1 }
                    ]
                ]
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val, opt) {
                return opt.dataPointIndex === 5 ? val + "%" : "";
            },
            offsetY: -22,
            style: {
                fontSize: '12px',
                colors: ["#fff"],
                top: 0
            },
            background: {
                enabled: true,
                foreColor: '#fff',
                padding: 5,
                borderRadius: 8,
                backgroundColor: '#1F2937', // Black bubble
                borderWidth: 0
            }
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                show: true,
                // Increase this negative value to push labels lower inside the bar
                offsetY: -20,
                style: {
                    // Colors array: Jun is white, others are dark grey
                    colors: ['#374151', '#374151', '#374151', '#374151', '#374151', '#FFFFFF', '#374151', '#374151', '#374151', '#374151', '#374151', '#374151'],
                    fontSize: '13px',
                    fontWeight: 500
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val, opt) {
                return opt.dataPointIndex === 5 ? val + "%" : "";
            },
            // Adjust this to ensure the black bubble stays at the top of the bar
            offsetY: -22,
            style: {
                fontSize: '12px',
                fontWeight: 500,
                colors: ["#fff"]
            },
            background: {
                enabled: true,
                foreColor: '#fff',
                padding: 5,
                borderRadius: 8,
                backgroundColor: '#1F2937',
                borderWidth: 0
            }
        },
        legend: {
            show: false // This removes the legend list at the bottom (Jan, Feb, Mar...)
        },
        yaxis: {
            max: 100,
            tickAmount: 5,
            labels: {
                formatter: (val) => val + "%",
                style: {
                    fontSize: '13px',
                    fontWeight: 500,
                    colors: '#6B7280'
                },
                padding: {
                    right: 0,
                    left: 0,
                }
            }
        },
        grid: {
            strokeDashArray: 4,
            yaxis: { lines: { show: true } },
            padding: { top: 0, bottom: -30 , right: -5 }
        }
    };

    var chart = new ApexCharts(document.querySelector("#attendancetrend-chart"), sColStacked);
    chart.render();
}

// Violations chart IT Dashboard
if ($('#violation-chart').length > 0) {
    var sColStacked = {
        colors: ['#FF5504', '#47BCB2', '#EFCE6B'],
        chart: {
            height: 120,
            width: 150,
            type: 'bar',
            toolbar: { show: false },
            sparkline: { enabled: true }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: "vertical",
                shadeIntensity: 0.5,
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100],
                colorStops: [
                    [{ offset: 0, color: '#F37438', opacity: 1 }, { offset: 100, color: '#FF5504', opacity: 1 }],
                    [{ offset: 0, color: '#0C4B5E', opacity: 1 }, { offset: 100, color: '#47BCB2', opacity: 1 }],
                    [{ offset: 0, color: '#2DA17C', opacity: 1 }, { offset: 100, color: '#EFCE6B', opacity: 1 }]
                ]
            }
        },
        plotOptions: {
            bar: {
                columnWidth: '80%', // 1. INCREASED BAR WIDTH (100% fills the category space)
                distributed: true,
                borderRadius: 5,
                borderRadiusApplication: 'around',
                borderRadiusWhenStacked: 'all',
                dataLabels: {
                    position: 'top', // 2. ANCHOR LABELS TO THE TOP
                },
            },
        },
        series: [{
            name: 'Company',
            data: [90, 50, 75]
        }],
        dataLabels: {
            enabled: true,
            formatter: function (val) { return val + "%"; },
            offsetY: -20, // 3. NUDGE LABELS ABOVE THE BAR
            style: {
                fontSize: '12px',
                colors: ['#333'], // Changed to dark color so it is visible above the bar
                fontWeight: 'bold'
            }
        },
        xaxis: {
            categories: ['Sales', 'Front End', 'React', 'UI'],
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            min: 0,
            max: 110, // Increased slightly to give room for the labels on top
            labels: { show: false }
        },
        grid: {
            padding: {
                top: 0,
                right: -30,
                bottom: 0,
                left: 0
            },
        },
        legend: { show: false },
    }

    var chart = new ApexCharts(document.querySelector("#violation-chart"), sColStacked);
    chart.render();
}


// Office Chart IT Dashboard
if ($('#office-chart').length > 0) {
    var options = {
        // 1. COLORS: Matches the Orange and Dark Teal from your screenshot
        colors: ['#0C4B5E', '#F26522'],

        series: [{
            name: "Series A (Teal)",
            // X values must be 1-7 to match Mon-Sun
            data: [
                [1, 220], [1.2, 230], [1.5, 280], [1.8, 240], // Mon
                [2, 270], [2.2, 340], [2.5, 270],             // Tue
                [3, 250], [3.5, 300],                         // Wed
                [4, 250], [4.2, 310], [4.5, 320],             // Thu
                [5, 280], [5.5, 320], [5.8, 300],             // Fri
                [6, 310], [6.2, 360], [6.5, 320],             // Sat
                [7, 300]                                      // Sun
            ]
        },{
            name: "Series B (Orange)",
            data: [
                [1, 100], [1.2, 20], [1.4, 40], [1.6, 80],    // Mon
                [2, 30], [2.2, 100], [2.5, 40], [2.8, 80],    // Tue
                [3, 110], [3.2, 160], [3.5, 170],             // Wed
                [4, 150], [4.2, 230], [4.5, 260], [4.8, 240], // Thu
                [5, 290], [5.2, 240], [5.5, 270], [5.8, 250], // Fri
                [6, 300], [6.5, 260],                         // Sat
                [7, 300], [7.5, 330]                          // Sun
            ]
        }],
        chart: {
            height: 200,
            type: 'scatter',
            toolbar: { show: false },
            zoom: { enabled: false }
        },
        // 2. GRID: Dashed lines, vertical lines removed
        grid: {
            borderColor: '#E2E8F0',
            strokeDashArray: 4,       // Creates the dashed effect
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: { top: 0, right: 10, bottom: -10 , left: -10 }
        },
        markers: {
            size: 6,
            strokeWidth: 0, // Removes white border for a flat look
            hover: { size: 8 }
        },
        // 3. X-AXIS: Custom "Mon - Sun" mapping
        xaxis: {
            min: 0.5, // Padding on left
            max: 7.5, // Padding on right
            tickAmount: 7,
            labels: {
                style: { colors: '#64748B', fontSize: '12px' },
                formatter: function(val) {
                    // Converts numeric X value (1-7) to Day Name
                    const days = ["", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
                    return days[Math.round(val)] || "";
                }
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        // 4. Y-AXIS: Fixed range 0 - 400
        yaxis: {
            min: 0,
            max: 400,
            tickAmount: 4,
            labels: {
                // Move labels to the left (negative value) or right (positive value)
                offsetX: -15,
                style: {
                    colors: '#64748B',
                    fontSize: '12px'
                },
                formatter: (val) => val.toFixed(0),
            },
            axisBorder: { show: false }
        },
        legend: { show: false }
    };

    var chart = new ApexCharts(document.querySelector("#office-chart"), options);
    chart.render();
}

// Error Chart IT Dashboard
if ($('#error-chart').length > 0) {
    var options = {
        series: [
            { name: 'M9', data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 40, 80] },
            { name: 'M8', data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 80, 80] },
            { name: 'M7', data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 40, 80] },
            { name: 'M6', data: [0, 0, 0, 0, 0, 40, 0, 0, 0, 0, 0, 0, 40, 0, 0, 0, 80, 80] },
            { name: 'M5', data: [0, 0, 0, 40, 0, 80, 80, 0, 0, 0, 80, 80, 0, 0, 80, 0, 40, 80] },
            { name: 'M4', data: [80, 80, 0, 80, 0, 0, 80, 0, 40, 80, 0, 0, 0, 0, 0, 40, 80, 80] },
            { name: 'M3', data: [80, 80, 80, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0] },
            { name: 'M2', data: [80, 80, 80, 0, 0, 80, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0] },
            { name: 'M1', data: [80, 80, 80, 0, 0, 40, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0] }
        ],
        chart: {
            height: 310,
            type: 'heatmap',
            toolbar: { show: false }
        },
        stroke: {
            width: 4,
            colors: ['#fff']
        },
        plotOptions: {
            heatmap: {
                radius: 8,
                enableShades: false,
                colorScale: {
                    ranges: [
                        { from: 0, to: 0, name: 'none', color: '#F1F3F4' },
                        { from: 1, to: 50, name: 'low', color: '#FFB38A' },
                        { from: 51, to: 100, name: 'high', color: '#F26522' }
                    ]
                }
            }
        },
        dataLabels: {
            enabled: false
        },

        // --- ADD THIS SECTION TO HIDE THE BOTTOM BOX ---
        tooltip: {
            enabled: true,
            x: {
                show: false // This hides the date/index box at the bottom
            },
            marker: {
                show: false // Keeps the tooltip clean without color dots
            }
        },
        grid: {
            show: false,
            padding: {
                left: 2, // Provides room for Y-axis text
                right: 2,
                bottom: -20 // Ensures the last label isn't cut off
            }
        },
        // ----------------------------------------------
        xaxis: {
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false } // Extra backup to ensure x-axis tooltip is off
        },
        yaxis: {
            labels: { show: false }
        },
        grid: {
            padding: { right: -0 , left: -10 }
        },
        legend: {
            show: false
        }
    };

    var chart = new ApexCharts(document.querySelector("#error-chart"), options);
    chart.render();
}

// Total Assets Asset Dashboard
if ($('#total-assets').length > 0) {
    var options = {
        series: [{
            name: 'Assets',
            data: [14, 18, 12, 22, 16, 24, 28, 15, 20, 14, 18, 11, 13, 23, 25, 26, 17, 14, 12, 24, 16, 12, 10, 20, 18, 15, 12, 18]
        }],

        chart: {
            type: 'bar',
            height: 116,
            width: '100%',
            parentHeightOffset: 0,
            toolbar: { show: false },
        },

        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '80%',
                distributed: true
            }
        },

        colors: [
            ({ dataPointIndex }) =>
                dataPointIndex === 23 ? '#F26522' : '#F2F4F7'
        ],

        dataLabels: { enabled: false },
        legend: { show: false },

        grid: {
            show: false,
            padding: {
                left: -10,
                right: -15,
                bottom: -8,
                top: -50,
            }
        },

        xaxis: {
            categories: ['', '', '', '01-08', '', '', '', '', '', '', '09-16', '', '', '', '', '', '', '16-24', '', '', '', '', '', '', '25-30'],
            labels: {

                rotate: 0,

                rotateAlways: false,

                hideOverlappingLabels: true,

                style: {

                    colors: '#9CA3AF',

                    fontSize: '12px',

                    fontFamily: 'inherit',

                    fontWeight: 400,

                },

            },
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false }
        },

        yaxis: { show: false },
        tooltip: { enabled: false },

        states: {
            hover: { filter: { type: 'none' } },
            active: { filter: { type: 'none' } }
        }
    };

    new ApexCharts(
        document.querySelector("#total-assets"),
        options
    ).render();
}

// Assets Assigned Asset Dashboard

if ($('#assets-assigned').length > 0) {
    var options = {
        series: [{
            name: 'Assets',
            data: [14, 18, 12, 22, 16, 24, 28, 15, 20, 14, 18, 11, 13, 23, 25, 26, 17, 14, 12, 24, 16, 12, 10, 20, 18, 15, 12, 18]
        }],

        chart: {
            type: 'bar',
            height: 116,
            width: '100%',
            parentHeightOffset: 0,
            toolbar: { show: false }
        },

        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '80%',
                distributed: true
            }
        },

        colors: [
            ({ dataPointIndex }) =>
                dataPointIndex === 14 ? '#0C4B5E' : '#F2F4F7'
        ],

        dataLabels: { enabled: false },
        legend: { show: false },

        grid: {
            show: false,
            padding: {
                left: -10,
                right: -15,
                bottom: -8,
                top: -50,
            }
        },

        xaxis: {
            categories: ['', '', '', '01-08', '', '', '', '', '', '', '09-16', '', '', '', '', '', '', '16-24', '', '', '', '', '', '', '25-30'],
            labels: {

                rotate: 0,

                rotateAlways: false,

                hideOverlappingLabels: true,

                style: {

                    colors: '#9CA3AF',

                    fontSize: '12px',

                    fontFamily: 'inherit',

                    fontWeight: 400,

                },

            },
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false }
        },




        yaxis: { show: false },
        tooltip: { enabled: false },

        states: {
            hover: { filter: { type: 'none' } },
            active: { filter: { type: 'none' } }
        }
    };

    new ApexCharts(
        document.querySelector("#assets-assigned"),
        options
    ).render();
}

// Assets Available Asset Dashboard

if ($('#assets-available').length > 0) {
    var options = {
        series: [{
            name: 'Assets',
            data: [14, 18, 12, 22, 16, 24, 28, 15, 20, 14, 18, 11, 13, 23, 25, 26, 17, 14, 12, 24, 16, 12, 10, 20, 18, 15, 12, 18]
        }],

        chart: {
            type: 'bar',
            height: 116,
            width: '100%',
            parentHeightOffset: 0,
            toolbar: { show: false }
        },

        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '80%',
                distributed: true
            }
        },

        colors: [
            ({ dataPointIndex }) =>
                dataPointIndex === 20 ? '#AB47BC' : '#F2F4F7'
        ],

        dataLabels: { enabled: false },
        legend: { show: false },

        grid: {
            show: false,
            padding: {
                left: -10,
                right: -15,
                bottom: -8,
                top: -50,
            }
        },

        xaxis: {
            categories: ['', '', '', '01-08', '', '', '', '', '', '', '09-16', '', '', '', '', '', '', '16-24', '', '', '', '', '', '', '25-30'],
            labels: {

                rotate: 0,

                rotateAlways: false,

                hideOverlappingLabels: true,

                style: {

                    colors: '#9CA3AF',

                    fontSize: '12px',

                    fontFamily: 'inherit',

                    fontWeight: 400,

                },

            },
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false }
        },

        yaxis: { show: false },
        tooltip: { enabled: false },

        states: {
            hover: { filter: { type: 'none' } },
            active: { filter: { type: 'none' } }
        }
    };

    new ApexCharts(
        document.querySelector("#assets-available"),
        options
    ).render();
}


if ($('#asset-value').length > 0) {

    const totalBlocks = 30;
    const filledBlocks = 15;

    var options = {
        series: [{
            data: Array.from({ length: totalBlocks }, (_, i) => {
                if (i === filledBlocks - 1) return 1.6; // 🔥 ONE TALL BAR
                return 1;
            })
        }],

        chart: {
            type: 'bar',
            height: 65,
            toolbar: { show: false },
            sparkline: { enabled: false }
        },

        plotOptions: {
            bar: {
                distributed: true,
                columnWidth: '75%',
                borderRadius: 3,
            }
        },

        colors: [
            ({ seriesIndex, dataPointIndex }) =>
                dataPointIndex < filledBlocks
                    ? '#F26522'
                    : '#F2F4F7'
        ],

        dataLabels: { enabled: false },

        grid: {
            show: false,
            padding: { top: 8, bottom: -6 }
        },

        xaxis: {
            categories: Array(totalBlocks).fill(''),
            labels: { show: false },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },

        yaxis: {
            show: false,
            min: 0,
            max: 1.6
        },

        tooltip: { enabled: false },
        legend: { show: false },

        states: {
            hover: { filter: { type: 'none' } },
            active: { filter: { type: 'none' } }
        }
    };

    new ApexCharts(document.querySelector("#asset-value"), options).render();
}

// Asset Categories Chart Asset Dashboard

if ($('#asset-categories-chart').length > 0) {

    var options = {
        series: [40, 20, 15, 15, 5],
        chart: {
            type: 'pie',
            height: 290,
        },

        labels: ['Laptops', 'Mouse', 'Writing Pad', 'Keyboard', 'Chairs'],

        colors: [
            '#3B7080',
            '#7fa2ad',
            '#9fb9c2',
            '#b7ccd3',
            '#d3e1e6'
        ],

        legend: { show: false },

        dataLabels: { enabled: false },

        stroke: {
            width: 0
        }
    };

    new ApexCharts(
        document.querySelector("#asset-categories-chart"),
        options
    ).render();
}

// Depreciated Value Asset Dashboard

if ($('#depreciated-value').length > 0) {
    var sCol = {
        chart: {
            width: 100,
            height: 60,
            type: 'bar',
            stacked: true, // Keep stacked for the mirrored effect
            toolbar: { show: false },
            sparkline: { enabled: true },
        },
        plotOptions: {
            bar: {
                // Adjust this percentage until the bars look exactly 8px wide
                columnWidth: '45%',
                borderRadius: 2,
                colors: {
                    // This creates the light background "track" behind the bars
                    backgroundBarColors: ['#F8F9FA', '#F8F9FA', '#F8F9FA', '#F8F9FA', '#F8F9FA'],
                    backgroundBarOpacity: 1,
                    backgroundBarRadius: 5,
                },
            },
        },
        series: [{
            name: 'Positive',
            data: [15, 40, 30, 35, 40, 35, 32]
        }, {
            name: 'Negative',
            data: [-15, -40, -30, -35, -40, -35, -32]
        }],
        colors: ['#F26522'], // Your orange color
        grid: { show: false },
        xaxis: { labels: { show: false } },
        yaxis: {
            min: -50,
            max: 50,
            show: false
        },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#depreciated-value"), sCol);
    chart.render();
}

// Assets Department

if ($('#assets-department').length > 0) {
    var sBar = {
        chart: {
            height: 296,
            type: 'bar',
            padding: {
                top: 0,
                left: 0,
                right: 0,
                bottom: 0
            },
            toolbar: {
                show: false,
            }
        },
        colors: ['#0C4B5E'],
        grid: {
            borderColor: '#E5E7EB',
            strokeDashArray: 5,
            padding: {
                top: -20,
                left: 0,
                right: 0,
                bottom: 0
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 5,
                horizontal: true,
                barHeight: '85%',
                endingShape: 'rounded'
            }
        },
        dataLabels: {
            enabled: false
        },
        series: [{
            data: [80, 110, 80, 20],
            name: 'Employee'
        }],
        xaxis: {
            categories: ['HR', 'Finance', 'Operations', 'Sales'],
            labels: {
                style: {
                    colors: '#111827',
                    fontSize: '13px',
                }
            }
        }
    }

    var chart = new ApexCharts(
        document.querySelector("#assets-department"),
        sBar
    );

    chart.render();
}

// Purchase Trend Asset Dashboard

if ($('#purchase-trend').length > 0) {

    var options = {
        chart: {
            height: 253,
            type: 'area',
            toolbar: { show: false }
        },

        series: [{
            name: 'Sales',
            data: [1800, 2600, 4500, 7600, 6900, 7600, 6200, 8000, 7300, 5400, 6000, 5400]
        }],

        stroke: {
            curve: 'stepline',
            width: 2,
            colors: ['#FF6F28']
        },

        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 0,
                opacityFrom: 0.35,
                opacityTo: 0.05,
                stops: [0, 100],
                colorStops: [
                    {
                        offset: 0,
                        color: '#FF6F28',
                        opacity: 0.35
                    },
                    {
                        offset: 100,
                        color: '#FF6F28',
                        opacity: 0.05
                    }
                ]
            }
        },

        markers: {
            size: 0
        },

        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '13px'
                }
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },

        yaxis: {
            min: 0,
            max: 8000,
            tickAmount: 4,
            forceNiceScale: true,
            labels: {
                formatter: function (val) {
                    return Math.round(val / 1000) + 'K';
                },
                style: {
                    colors: '#6B7280',
                    fontSize: '13px'
                },
                offsetX : -15,
            }
        },


        grid: {
            borderColor: '#ffffff00',
            strokeDashArray: 5,
            padding: {
                left: 0,
            }
        },

        tooltip: {
            custom: function ({ series, seriesIndex, dataPointIndex }) {
                if (dataPointIndex === -1) return '';

                const value = series[seriesIndex][dataPointIndex];
                const formatted = (value / 1000).toFixed(1) + 'K';

                return `
      <div style="
        background:#000;
        color:#fff;
        padding:6px 10px;
        border-radius:6px;
        font-size:12px;
      ">
        ${formatted}
      </div>
    `;
            }
        },

        annotations: {
            xaxis: [
                {
                    x: 'Jun',
                    strokeDashArray: 0,
                    borderColor: '#FF6F28',
                    fillColor: '#FF6F28',
                    opacity: 1,
                    width: 30,
                    label: {
                        text: '7.6K',
                        position: 'top',
                        offsetY: -8,
                        style: {
                            background: '#000',
                            color: '#fff',
                            fontSize: '12px',
                            padding: {
                                left: 8,
                                right: 8,
                                top: 4,
                                bottom: 4
                            },
                            borderRadius: 6,
                            rotate: -90
                        }
                    }
                }
            ]
        },

        legend: { show: false },
        dataLabels: { enabled: false }
    };

    var chart = new ApexCharts(
        document.querySelector("#purchase-trend"),
        options
    );

    chart.render();
}

if ($('#headcount-chart').length > 0) {
    var options = {
        series: [{
            name: 'Revenue',
            data: [20, 28, 29, 20, 15, 30, 25, 20, 20, 12, 20, 20, 30, 15, 20, 25],
        }, {
            name: 'Expenses',
            data: [-20, -30, -20, -20, -25, -25, -20, -30, -20, -25, -30, -20, -30, -20, -10, -28]
        }],
        grid: {
            padding: {
                top: 5, // Adds space on the left
                right: 0, // Adds space on the right
            },
        },
        colors: ['#F26522', '#E5E7EB'],
        chart: {
            type: 'bar',
            height: 240,
            stacked: true,
            zoom: {
                enabled: true
            }
        },
        responsive: [{
            breakpoint: 280,
            options: {
                legend: {
                    position: 'bottom',
                    offsetY: 0
                }
            }
        }],
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 8,
                borderRadiusApplication: "around", // "around" / "end"
                borderRadiusWhenStacked: "all", // "all"/"last"
                columnWidth: '40%',
            },
        },
        dataLabels: {
            enabled: false
        },
        yaxis: {
            opposite: true,
            labels: {
                offsetX: -5,
                formatter: (val) => {
                    return val / 1 + 'K'
                },
            },
            min: -30,
            max: 30,
            tickAmount: 6,
        },
        xaxis: {
            categories: ['', '', 'Jan', '', '', '', 'Feb', '', '', '', 'Mar', '', '', '', 'Apr', ''],
        },
        legend: { show: false },
        fill: {
            opacity: 1
        }
    };


    var chart = new ApexCharts(document.querySelector("#headcount-chart"), options);
    chart.render();
}

if ($('#finance-chart').length > 0) {
    var sColStacked = {
        colors: ['#FF7129'],
        chart: {
            height: 140,
            type: 'bar',
            toolbar: {
                show: false,
            },
            sparkline: { enabled: true },
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical', // Top to bottom (180deg)
                shadeIntensity: 0.5,
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100],
                colorStops: [
                    {
                        offset: 0,
                        color: '#9CB9C2', // Top color
                        opacity: 0.5
                    },
                    {
                        offset: 100,
                        color: '#F8F9FA', // Bottom color
                        opacity: 1,
                        opacity: 0.5
                    }
                ]
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                legend: {
                    position: 'bottom',
                    offsetY: 10
                }
            }
        }],
        plotOptions: {
            bar: {
                columnWidth: '80%',
                borderRadius: 12,
                horizontal: false,
                endingShape: 'rounded', dataLabels: {
                    position: 'bottom', // Anchors the label to the base of the bar
                },
                colors: {
                    backgroundBarColors: ['#F8F9FA'], // Background color for bars
                    backgroundBarOpacity: 0.5,
                    hover: {
                        enabled: true,
                        borderColor: '#F26522', // Color when hovering over the bar
                    }
                }
            },
        },
        series: [{
            name: 'Amount',
            data: [30, 60, 30, 40, 100, 80, 90, 50, 60, 40, 30, 60]
        }],
        dataLabels: {
            enabled: false, // Must be true
            formatter: function (val) {
                return "$" + val; // Display the raw value
            },
            offsetY: 10, // Adjust this value to nudge the text up or down from the bottom
            style: {
                fontSize: '12px',
                colors: ['#F26522'], // Gray color to match your design
                fontWeight: 'bold'
            }
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: {
                    colors: '#111827',
                    fontSize: '13px',
                }
            }
        },
        yaxis: {
            min: 0,
            max: 100,
            labels: {
                show: false
            }
        },
        grid: {
            show: false,
            strokeDashArray: 5,
            padding: {
                left: 0,
                right: 0,
                top: 0,
            },
        },
        legend: {
            show: false
        },
    }

    var chart = new ApexCharts(
        document.querySelector("#finance-chart"),
        sColStacked
    );

    chart.render();
}

// Simple Line Area
if ($('#budget-chart').length > 0) {
    var sLineArea = {
        chart: {
            height: 280,
            type: 'area',
            toolbar: {
                show: false,
            }
        },
        colors: ['#F26522', '#0C4B5E'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'straight',
            width: 1,
        },
        // Added the fill style from the first chart
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.35,
                opacityTo: 0.05,
                stops: [0, 100],
            },
        },
        series: [{
            name: 'Budget',
            data: [5, 10, 8, 6, 5, 10, 8, 10]
        }, {
            name: 'Spent',
            data: [15, 20, 16, 15, 15, 20, 18, 20]
        }],

        xaxis: {
            categories: ["Engineering", "Sales", "Marketing", "Operations", "Support", "Admin", "UI/UX", "Devops"],
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            min: 0,
            max: 40,
            labels: {
                offsetX: -15,
                formatter: function (value) {
                    return value + "k"; // Label remains exactly as you had it
                }
            },
        },
        grid: {
            show: false,
            padding: {
                left: 0,
                right: -15,
                top: 0,
            },
        },
    }

    var chart = new ApexCharts(
        document.querySelector("#budget-chart"),
        sLineArea
    );

    chart.render();
}

// Payroll Payment
if ($('#payroll-payment').length > 0) {
    var options = {
        // Data values are now populated to avoid Syntax Errors
        series: [
            { name: 'Payroll', data: [600] },
            { name: 'Remaining', data: [1000] },
        ],
        chart: {
            type: 'bar',
            height: 45,
            stacked: true,
            stackType: '100%',
            toolbar: { show: false },
            sparkline: { enabled: true }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: '100%',
            }
        },
        colors: ['#54C564', '#E5E7EB'],
        fill: {
            type: 'pattern',
            opacity: 1,
            pattern: {
                style: 'verticalLines',
                width: 6,
                strokeWidth: 4
            }
        },
        tooltip: { enabled: true },
        xaxis: { categories: ['Total'] }
    };

    var chart = new ApexCharts(document.querySelector("#payroll-payment"), options);
    chart.render();

}

// Payroll Payment
if ($('#reimbrusement-chart').length > 0) {
    var options = {
        // Data values are now populated to avoid Syntax Errors
        series: [
            { name: 'Reimbrusement', data: [1000] },
            { name: 'Remaining', data: [600] },
        ],
        chart: {
            type: 'bar',
            height: 45,
            stacked: true,
            stackType: '100%',
            toolbar: { show: false },
            sparkline: { enabled: true }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: '100%',
            }
        },
        colors: ['#7298A4', '#E5E7EB'],
        fill: {
            type: 'pattern',
            opacity: 1,
            pattern: {
                style: 'verticalLines',
                width: 6,
                strokeWidth: 4
            }
        },
        tooltip: { enabled: true },
        xaxis: { categories: ['Total'] }
    };

    var chart = new ApexCharts(document.querySelector("#reimbrusement-chart"), options);
    chart.render();

}

// Tenent Chart1
if ($('#tenent-support-chart1').length > 0) {
    var sCol = {
        chart: {
            width: 50,
            height: 115,
            type: 'bar',
            toolbar: { show: false },
            sparkline: { enabled: true }
        },
        // Ensure the primary color is defined in both colors and fill
        colors: ['#0C4B5E'],
        fill: {
            colors: ['#0C4B5E'],
            opacity: 1
        },
        series: [{
            name: 'New',
            data: [60]
        }],
        plotOptions: {
            bar: {
                borderRadius: 10,
                columnWidth: '60%', // Helps visibility for single bars
                colors: {
                    backgroundBarColors: ['#F8F9FA'],
                    backgroundBarOpacity: 0.5,
                    backgroundBarRadius: 10
                }
            }
        },
        // STATES: This is required to make the active/hover color work
        states: {
            normal: { filter: { type: 'none' } },
            hover: {
                filter: { type: 'darken', value: 0.8 } // Darkens current color on hover
            },
            active: {
                allowMultipleDataPointsSelection: false,
                filter: { type: 'none' }
            }
        },
        dataLabels: {
            enabled: true, // Must be true
            formatter: function (val) {
                return val + "%"; // Display the raw value
            },
            offsetY: 0, // Adjust this value to nudge the text up or down from the bottom
            style: {
                fontSize: '12px',
                colors: ['#fff'], // Gray color to match your design
                fontWeight: 'bold'
            }
        },
        xaxis: {
            categories: ['Day'], // Adding a category helps Apex recognize the single point
            labels: { show: false },
            axisTicks: { show: false },
            axisBorder: { show: false }
        },
        yaxis: { show: false, min: 0, max: 100 },
        grid: { show: false },
        tooltip: { enabled: true }
    };


    var chart = new ApexCharts(document.querySelector("#tenent-support-chart1"), sCol);
    chart.render();
}

// Tenent Chart1
if ($('#tenent-support-chart2').length > 0) {
    var sCol = {
        chart: {
            width: 50,
            height: 115,
            type: 'bar',
            toolbar: { show: false },
            sparkline: { enabled: true }
        },
        // Ensure the primary color is defined in both colors and fill
        colors: ['#AB47BC'],
        fill: {
            colors: ['#AB47BC'],
            opacity: 1
        },
        series: [{
            name: 'open',
            data: [30]
        }],
        plotOptions: {
            bar: {
                borderRadius: 10,
                columnWidth: '60%', // Helps visibility for single bars
                colors: {
                    backgroundBarColors: ['#F7EEF9'],
                    backgroundBarOpacity: 0.5,
                    backgroundBarRadius: 10
                }
            }
        },
        // STATES: This is required to make the active/hover color work
        states: {
            normal: { filter: { type: 'none' } },
            hover: {
                filter: { type: 'darken', value: 0.8 } // Darkens current color on hover
            },
            active: {
                allowMultipleDataPointsSelection: false,
                filter: { type: 'none' }
            }
        },
        dataLabels: {
            enabled: true, // Must be true
            formatter: function (val) {
                return val + "%"; // Display the raw value
            },
            offsetY: 0, // Adjust this value to nudge the text up or down from the bottom
            style: {
                fontSize: '12px',
                colors: ['#fff'], // Gray color to match your design
                fontWeight: 'bold'
            }
        },
        xaxis: {
            categories: ['Day'], // Adding a category helps Apex recognize the single point
            labels: { show: false },
            axisTicks: { show: false },
            axisBorder: { show: false }
        },
        yaxis: { show: false, min: 0, max: 100 },
        grid: { show: false },
        tooltip: { enabled: true }
    };


    var chart = new ApexCharts(document.querySelector("#tenent-support-chart2"), sCol);
    chart.render();
}

// Tenent Chart1
if ($('#tenent-support-chart3').length > 0) {
    var sCol = {
        chart: {
            width: 50,
            height: 115,
            type: 'bar',
            toolbar: { show: false },
            sparkline: { enabled: true }
        },
        // Ensure the primary color is defined in both colors and fill
        colors: ['#FFC107'],
        fill: {
            colors: ['#FFC107'],
            opacity: 1
        },
        series: [{
            name: 'On Hold',
            data: [40]
        }],
        plotOptions: {
            bar: {
                borderRadius: 10,
                columnWidth: '60%', // Helps visibility for single bars
                colors: {
                    backgroundBarColors: ['#FDF7E6'],
                    backgroundBarOpacity: 0.5,
                    backgroundBarRadius: 10
                }
            }
        },
        // STATES: This is required to make the active/hover color work
        states: {
            normal: { filter: { type: 'none' } },
            hover: {
                filter: { type: 'darken', value: 0.8 } // Darkens current color on hover
            },
            active: {
                allowMultipleDataPointsSelection: false,
                filter: { type: 'none' }
            }
        },
        dataLabels: {
            enabled: true, // Must be true
            formatter: function (val) {
                return val + "%"; // Display the raw value
            },
            offsetY: 0, // Adjust this value to nudge the text up or down from the bottom
            style: {
                fontSize: '12px',
                colors: ['#fff'], // Gray color to match your design
                fontWeight: 'bold'
            }
        },
        xaxis: {
            categories: ['Day'], // Adding a category helps Apex recognize the single point
            labels: { show: false },
            axisTicks: { show: false },
            axisBorder: { show: false }
        },
        yaxis: { show: false, min: 0, max: 100 },
        grid: { show: false },
        tooltip: { enabled: true }
    };

    var chart = new ApexCharts(document.querySelector("#tenent-support-chart3"), sCol);
    chart.render();
}

// Tenent Chart4
if ($('#tenent-support-chart4').length > 0) {
    var sCol = {
        chart: {
            width: 50,
            height: 115,
            type: 'bar',
            toolbar: { show: false },
            sparkline: { enabled: true }
        },
        // Ensure the primary color is defined in both colors and fill
        colors: ['#0DCAF0'],
        fill: {
            colors: ['#0DCAF0'],
            opacity: 1
        },
        series: [{
            name: 'Pending',
            data: [50]
        }],
        plotOptions: {
            bar: {
                borderRadius: 10,
                columnWidth: '60%', // Helps visibility for single bars
                colors: {
                    backgroundBarColors: ['#E9FAFE'],
                    backgroundBarOpacity: 0.5,
                    backgroundBarRadius: 10
                }
            }
        },
        // STATES: This is required to make the active/hover color work
        states: {
            normal: { filter: { type: 'none' } },
            hover: {
                filter: { type: 'darken', value: 0.8 } // Darkens current color on hover
            },
            active: {
                allowMultipleDataPointsSelection: false,
                filter: { type: 'none' }
            }
        },
        dataLabels: {
            enabled: true, // Must be true
            formatter: function (val) {
                return val + "%"; // Display the raw value
            },
            offsetY: 0, // Adjust this value to nudge the text up or down from the bottom
            style: {
                fontSize: '12px',
                colors: ['#fff'], // Gray color to match your design
                fontWeight: 'bold'
            }
        },
        xaxis: {
            categories: ['Day'], // Adding a category helps Apex recognize the single point
            labels: { show: false },
            axisTicks: { show: false },
            axisBorder: { show: false }
        },
        yaxis: { show: false, min: 0, max: 100 },
        grid: { show: false },
        tooltip: { enabled: true }
    };


    var chart = new ApexCharts(document.querySelector("#tenent-support-chart4"), sCol);
    chart.render();
}

// Tenent Chart4
if ($('#tenent-support-chart5').length > 0) {
    var sCol = {
        chart: {
            width: 50,
            height: 115,
            type: 'bar',
            toolbar: { show: false },
            sparkline: { enabled: true }
        },
        // Ensure the primary color is defined in both colors and fill
        colors: ['#03C95A'],
        fill: {
            colors: ['#03C95A'],
            opacity: 1
        },
        series: [{
            name: 'Solved',
            data: [80]
        }],
        plotOptions: {
            bar: {
                borderRadius: 10,
                columnWidth: '60%', // Helps visibility for single bars
                colors: {
                    backgroundBarColors: ['#EAF8F0'],
                    backgroundBarOpacity: 0.5,
                    backgroundBarRadius: 10
                }
            }
        },
        // STATES: This is required to make the active/hover color work
        states: {
            normal: { filter: { type: 'none' } },
            hover: {
                filter: { type: 'darken', value: 0.8 } // Darkens current color on hover
            },
            active: {
                allowMultipleDataPointsSelection: false,
                filter: { type: 'none' }
            }
        },
        dataLabels: {
            enabled: true, // Must be true
            formatter: function (val) {
                return val + "%"; // Display the raw value
            },
            offsetY: -20, // Adjust this value to nudge the text up or down from the bottom
            style: {
                fontSize: '12px',
                colors: ['#fff'], // Gray color to match your design
                fontWeight: 'bold'
            }
        },
        xaxis: {
            categories: ['Day'], // Adding a category helps Apex recognize the single point
            labels: { show: false },
            axisTicks: { show: false },
            axisBorder: { show: false }
        },
        yaxis: { show: false, min: 0, max: 100 },
        grid: { show: false },
        tooltip: { enabled: true }
    };


    var chart = new ApexCharts(document.querySelector("#tenent-support-chart5"), sCol);
    chart.render();
}




// Ticket Trends Help Desk Dashboard
if ($('#ticket-trends').length > 0) {
    var options = {
        chart: {
            type: 'area',
            height: 241,
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: "'Public Sans', sans-serif" // Standard clean dashboard font
        },
        // Data points adjusted to visually match the peaks in your image
        series: [
            { name: 'Created', data: [45, 60, 95, 70, 75, 60, 75] },
            { name: 'Resolved', data: [145, 155, 185, 145, 145, 170, 170] }
        ],
        colors: ['#F26522', '#0D4C63'], // Exact colors from image
        stroke: {
            curve: 'straight', // Important: The image does not use smooth curves
            width: 1.5
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 0,
                opacityFrom: 0.35,   // 👈 visible fill at top
                opacityTo: 0.05,     // 👈 soft fade at bottom
                stops: [0, 100]
            }
        },
        markers: {
            size: 4,
            colors: ['#F26522', '#0D4C63'],
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: { size: 6 }
        },
        grid: {
            borderColor: '#f1f1f1',
            strokeDashArray: 3, // Dashed horizontal lines
            padding: {
                right : -8,
            },
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } }
        },
        xaxis: {
            categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                offsetX : 2,
                style: {
                    // Highlights "Thu" in orange, others in gray
                    colors: ['#8e94a9', '#8e94a9', '#8e94a9', '#F26522', '#8e94a9', '#8e94a9', '#8e94a9'],
                    fontSize: '13px'
                }
            }
        },
        yaxis: {
            min: 0,
            max: 400,
            tickAmount: 4, // Forces 0, 100, 200, 300, 400
            labels: {
                offsetX : -15,
                style: {
                    colors: '#8e94a9',
                    fontSize: '13px'
                }
            }
        },
        tooltip: {
            shared: true,
            intersect: false,
            custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                return `
                    <div class="custom-apex-tooltip shadow-sm">
                        <div class="tooltip-title">September</div>
                        <div class="tooltip-row">
                            <div class="tooltip-col">
                                <span class="label"><span class="dot orange"></span> Created</span>
                                <span class="value">${series[0][dataPointIndex]}</span>
                            </div>
                            <div class="tooltip-col">
                                <span class="label"><span class="dot blue"></span> Resolved</span>
                                <span class="value">${series[1][dataPointIndex]}</span>
                            </div>
                        </div>
                    </div>
                `;
            }
        },
        legend: {
            show: false,
            position: 'bottom',
            horizontalAlign: 'center',
            markers: { radius: 12 }
        },
        dataLabels: { enabled: false }
    };

    var chart = new ApexCharts(document.querySelector("#ticket-trends"), options);
    chart.render();
}

// Ticket Status Help Desk Dashboard
if ($('#ticket-status').length > 0) {

    var options = {
        chart: {
            height: 296,
            type: 'radialBar',
            sparkline: {
                enabled: true
            }
        },

        series: [72, 55, 38, 22],

        plotOptions: {
            radialBar: {
                startAngle: -120,
                endAngle: 240,

                hollow: {
                    size: '18%',
                },

                track: {
                    background: '#f2f2f2',
                    strokeWidth: '100%',
                    opacity: 1,
                    margin: 6
                },

                dataLabels: {
                    show: false
                }
            }
        },

        stroke: {
            lineCap: 'round'
        },

        colors: [
            '#F26522',
            '#1B84FF',
            '#FFC107',
            '#AB47BC',
        ],

        labels: [
            'Open',
            'In Progres',
            'On Hold',
            'Closed',
        ]
    };

    var chart = new ApexCharts(
        document.querySelector("#ticket-status"),
        options
    );

    chart.render();
}


// SLA Compliance Help Desk Dashboard
if ($('#sla-compliance').length > 0) {
    var options = {
        series: [80.5],
        chart: {
            type: 'radialBar',
            height: 230,
            sparkline: { enabled: true }
        },

        colors: ['#F26522'],

        plotOptions: {
            radialBar: {
                startAngle: -110,
                endAngle: 250,

                hollow: {
                    size: '62%'
                },

                track: {
                    background: '#E5E7EB',
                    strokeWidth: '100%'
                },

                dataLabels: {
                    name: {
                        show: false
                    },
                    value: {
                        fontSize: '24px',
                        fontWeight: 600,
                        color: '#1F2937',
                        offsetY: 0,
                        offsetX: 0,
                        textAnchor: 'middle',
                        formatter: function (val) {
                            return val.toFixed(1) + '%';
                        }
                    }
                }

            }
        },

        stroke: {
            lineCap: 'round'
        }
    };

    new ApexCharts(
        document.querySelector("#sla-compliance"),
        options
    ).render();
}


// Backlog Growth Help Desk Dashboard
if ($('#backlog-growth').length > 0) {

    const maxValue = 700;
    const actual = [80, 280, 330, 410, 470, 520, 560];
    const remaining = actual.map(v => maxValue - v);

    var options = {
        series: [
            {
                name: 'Growth',
                data: actual
            },
            {
                name: 'Remaining',
                data: remaining
            }
        ],

        chart: {
            height: 300,
            type: 'bar',
            stacked: true,
            toolbar: { show: false }
        },

        colors: ['#0F4C5C', '#EEF2F5'],

        plotOptions: {
            bar: {
                columnWidth: '80%',
                borderRadius: 8,
                borderRadiusWhenStacked: 'last',
                endingShape: 'rounded'
            }
        },

        stroke: {
            width: [0, 1],
            colors: ['transparent', '#CBD5E1'],
            dashArray: [0, 6]            // dotted tube
        },

        xaxis: {
            categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            labels: {
                offsetX : 1,
                style: {
                    colors: '#6B7280',
                    fontSize: '13px'
                }
            }
        },

        yaxis: {
            max: maxValue,
            tickAmount: 7,
            labels: {
                offsetX : -15,
                style: {
                    colors: '#6B7280',
                    fontSize: '13px'
                }
            }
        },

        grid: {
            borderColor: '#e5e7eb00',
            strokeDashArray: 5,
            padding : {
                right : -13,
                left : -8
            }
        },

        legend: { show: false },
        dataLabels: { enabled: false },

        fill: {
            opacity: [1, 1]
        }
    };

    new ApexCharts(
        document.querySelector("#backlog-growth"),
        options
    ).render();
}


// Agent Performance
document.querySelectorAll('.agent-performance').forEach((el) => {

    const totalDots = 24;
    const filledDots = Number(el.dataset.filled) || 0;

    var options = {
        series: [{
            data: Array.from({ length: totalDots }, (_, i) => ({
                x: i + 1,
                y: 1,
                fillColor: i < filledDots ? '#F26522' : '#E5E7EB'
            }))
        }],
        chart: { type: 'scatter', height: 18, sparkline: { enabled: true } },
        markers: { size: 5.5, shape: 'circle', strokeWidth: 0 },
        xaxis: { min: 0, max: totalDots + 1, labels: { show: false } },
        yaxis: { show: false },
        grid: { show: false },
        tooltip: { enabled: false },
        legend: { show: false }
    };

    new ApexCharts(el, options).render();
});


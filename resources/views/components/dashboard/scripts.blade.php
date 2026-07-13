@props(['charts'])

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const charts = @json($charts);

                if (typeof ApexCharts === 'undefined') {
                    console.warn('ApexCharts is not loaded.');
                    return;
                }

                const commonFont = "Outfit, sans-serif";

                const statusEl = document.querySelector('#dashboard-status-chart');
                if (statusEl) {
                    const statusChart = new ApexCharts(statusEl, {
                        series: charts.status.data,
                        labels: charts.status.labels,
                        chart: {
                            fontFamily: commonFont,
                            type: 'donut',
                            height: 320,
                            toolbar: { show: false },
                        },
                        colors: ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444', '#14b8a6'],
                        legend: {
                            position: 'bottom',
                            fontFamily: commonFont,
                        },
                        dataLabels: {
                            enabled: true,
                        },
                        stroke: {
                            width: 0,
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '72%',
                                },
                            },
                        },
                        tooltip: {
                            y: {
                                formatter: function (val) {
                                    return val;
                                },
                            },
                        },
                    });

                    statusChart.render();
                }

                const creatorsEl = document.querySelector('#dashboard-creators-chart');
                if (creatorsEl) {
                    const creatorsChart = new ApexCharts(creatorsEl, {
                        series: [{
                            name: 'Tasks Created',
                            data: charts.creators.data,
                        }],
                        chart: {
                            fontFamily: commonFont,
                            type: 'bar',
                            height: 320,
                            toolbar: { show: false },
                        },
                        colors: ['#465FFF'],
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '40%',
                                borderRadius: 10,
                                borderRadiusApplication: 'end',
                            },
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        xaxis: {
                            categories: charts.creators.labels,
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                        },
                        yaxis: {
                            title: { text: undefined },
                            labels: {
                                formatter: function (val) {
                                    return Math.round(val);
                                },
                            },
                        },
                        grid: {
                            yaxis: {
                                lines: {
                                    show: true,
                                },
                            },
                        },
                        tooltip: {
                            y: {
                                formatter: function (val) {
                                    return val;
                                },
                            },
                        },
                        legend: {
                            show: false,
                        },
                    });

                    creatorsChart.render();
                }

                const completersEl = document.querySelector('#dashboard-completers-chart');
                if (completersEl) {
                    const completersChart = new ApexCharts(completersEl, {
                        series: [{
                            name: 'Tasks Completed',
                            data: charts.completers.data,
                        }],
                        chart: {
                            fontFamily: commonFont,
                            type: 'bar',
                            height: 320,
                            toolbar: { show: false },
                        },
                        colors: ['#10b981'],
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                barHeight: '38%',
                                borderRadius: 10,
                                borderRadiusApplication: 'end',
                            },
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        xaxis: {
                            categories: charts.completers.labels,
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                        },
                        yaxis: {
                            title: { text: undefined },
                        },
                        grid: {
                            xaxis: {
                                lines: {
                                    show: true,
                                },
                            },
                        },
                        tooltip: {
                            y: {
                                formatter: function (val) {
                                    return val;
                                },
                            },
                        },
                        legend: {
                            show: false,
                        },
                    });

                    completersChart.render();
                }

                const dailyEl = document.querySelector('#dashboard-daily-chart');
                if (dailyEl) {
                    const dailyChart = new ApexCharts(dailyEl, {
                        series: [{
                            name: 'Tasks Created',
                            data: charts.daily.data,
                        }],
                        chart: {
                            fontFamily: commonFont,
                            type: 'area',
                            height: 340,
                            toolbar: { show: false },
                        },
                        colors: ['#8b5cf6'],
                        dataLabels: {
                            enabled: false,
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 2,
                        },
                        fill: {
                            gradient: {
                                enabled: true,
                                opacityFrom: 0.55,
                                opacityTo: 0,
                            },
                        },
                        markers: {
                            size: 0,
                        },
                        xaxis: {
                            categories: charts.daily.labels,
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                        },
                        yaxis: {
                            title: { text: undefined },
                        },
                        grid: {
                            xaxis: {
                                lines: {
                                    show: false,
                                },
                            },
                            yaxis: {
                                lines: {
                                    show: true,
                                },
                            },
                        },
                        tooltip: {
                            x: {
                                format: 'dd MMM',
                            },
                        },
                        legend: {
                            show: false,
                        },
                    });

                    dailyChart.render();
                }
            });
        </script>
    @endpush
@endonce
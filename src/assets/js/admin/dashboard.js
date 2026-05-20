const dataFromPHP = JSON.parse(document.getElementById("data-to-js").textContent);

const revenueLabels = JSON.parse(dataFromPHP.revenueLabels);
const revenueData = JSON.parse(dataFromPHP.revenueData);
const statusData = dataFromPHP.statusData;

const accent = '#f59e0b';
const green = '#22c55e';
const orange = '#f97316';
const red = '#ef4444';
const gridCol = 'rgba(255,255,255,0.05)';
const textCol = '#6b6865';

Chart.defaults.color = textCol;
Chart.defaults.font.family = "'DM Sans', sans-serif";

// Revenue Bar Chart 
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: revenueLabels,
        datasets: [{
            label: 'Revenue (TND)',
            data: revenueData,
            backgroundColor: 'rgba(245,158,11,0.25)',
            borderColor: accent,
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + ctx.parsed.y.toLocaleString() + ' TND'
                }
            }
        },
        scales: {
            x: {
                grid: {
                    color: gridCol
                },
                ticks: {
                    color: textCol
                }
            },
            y: {
                grid: {
                    color: gridCol
                },
                ticks: {
                    color: textCol,
                    callback: v => (v / 1000).toFixed(0) + 'k'
                },
                beginAtZero: true
            }
        }
    }
});

// Status Donut Chart 
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Confirmed', 'Pending', 'Canceled'],
        datasets: [{
            data: statusData,
            backgroundColor: [
                'rgba(34,197,94,0.8)',
                'rgba(249,115,22,0.8)',
                'rgba(239,68,68,0.8)',
            ],
            borderColor: '#141416',
            borderWidth: 3,
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        cutout: '70%',
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + ctx.label + ': ' + ctx.parsed
                }
            }
        }
    }
});
import Chart from 'chart.js/auto';

export function initCharts() {
    document.querySelectorAll('canvas[data-blf-chart]').forEach((canvas) => {
        const type = canvas.getAttribute('data-blf-chart');
        const raw = canvas.getAttribute('data-chart-payload');
        if (!raw) {
            return;
        }

        let payload;
        try {
            payload = JSON.parse(raw);
        } catch {
            return;
        }

        if (type === 'monthly') {
            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: payload.labels || [],
                    datasets: [
                        {
                            label: 'Lost Reports',
                            data: payload.lost || [],
                            borderColor: '#D97706',
                            backgroundColor: 'rgba(217, 119, 6, 0.12)',
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: 'Found Reports',
                            data: payload.found || [],
                            borderColor: '#087EA4',
                            backgroundColor: 'rgba(8, 126, 164, 0.12)',
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: 'Recovered',
                            data: payload.recovered || [],
                            borderColor: '#0D9488',
                            backgroundColor: 'rgba(13, 148, 136, 0.12)',
                            tension: 0.3,
                            fill: false,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                    },
                },
            });
        }

        if (type === 'barangay') {
            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: (payload || []).map((row) => row.name),
                    datasets: [
                        {
                            label: 'Lost',
                            data: (payload || []).map((row) => row.lost),
                            backgroundColor: '#F59E0B',
                            borderRadius: 4,
                        },
                        {
                            label: 'Found',
                            data: (payload || []).map((row) => row.found),
                            backgroundColor: '#087EA4',
                            borderRadius: 4,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                    },
                },
            });
        }
    });
}

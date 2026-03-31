/* ============================================================
   Pomegranate – Dashboard JavaScript
   Sidebar, charts, tables, modals
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initCharts();
    initTableSearch();
    initDeleteConfirm();
    initDismissAlerts();
});

/* ──────────────────────────────────────────────────────────── */
/*  SIDEBAR TOGGLE (mobile)                                     */
/* ──────────────────────────────────────────────────────────── */
function initSidebar() {
    const toggle   = document.getElementById('sidebar-toggle');
    const sidebar  = document.querySelector('.sidebar');
    const overlay  = document.getElementById('sidebar-overlay');

    if (!toggle || !sidebar) return;

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    });

    overlay?.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });
}

/* ──────────────────────────────────────────────────────────── */
/*  CHARTS (Chart.js)                                           */
/* ──────────────────────────────────────────────────────────── */
function initCharts() {
    // Revenue chart
    const revenueCanvas = document.getElementById('revenueChart');
    if (revenueCanvas && typeof Chart !== 'undefined') {
        const labels = revenueCanvas.dataset.labels
            ? JSON.parse(revenueCanvas.dataset.labels)
            : ['Jan','Feb','Mar','Apr','May','Jun','Jul'];
        const data = revenueCanvas.dataset.values
            ? JSON.parse(revenueCanvas.dataset.values)
            : [12400, 19800, 15600, 24200, 21000, 28900, 31500];

        new Chart(revenueCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Revenue ($)',
                    data,
                    borderColor: '#22d3ee',
                    backgroundColor: 'rgba(34,211,238,.07)',
                    fill: true,
                    tension: .45,
                    pointBackgroundColor: '#22d3ee',
                    pointBorderColor: '#050a14',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0c1220',
                        borderColor: 'rgba(255,255,255,.08)',
                        borderWidth: 1,
                        titleColor: '#fff',
                        bodyColor: 'rgba(255,255,255,.6)',
                        callbacks: {
                            label: ctx => ' $' + ctx.raw.toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,.04)', drawBorder: false },
                        ticks: { color: 'rgba(255,255,255,.4)', font: { size: 11 } }
                    },
                    y: {
                        grid: { color: 'rgba(255,255,255,.04)', drawBorder: false },
                        ticks: {
                            color: 'rgba(255,255,255,.4)',
                            font: { size: 11 },
                            callback: v => '$' + v.toLocaleString()
                        }
                    }
                }
            }
        });
    }

    // Orders by status doughnut
    const ordersCanvas = document.getElementById('ordersChart');
    if (ordersCanvas && typeof Chart !== 'undefined') {
        const labels = ordersCanvas.dataset.labels
            ? JSON.parse(ordersCanvas.dataset.labels)
            : ['Pending','Processing','Shipped','Delivered','Cancelled'];
        const data = ordersCanvas.dataset.values
            ? JSON.parse(ordersCanvas.dataset.values)
            : [12, 25, 18, 92, 4];

        new Chart(ordersCanvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: ['#fbbf24','#22d3ee','#818cf8','#34d399','#f87171'],
                    borderColor: '#0c1220',
                    borderWidth: 3,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'rgba(255,255,255,.5)',
                            padding: 16,
                            font: { size: 11 },
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0c1220',
                        borderColor: 'rgba(255,255,255,.08)',
                        borderWidth: 1,
                        titleColor: '#fff',
                        bodyColor: 'rgba(255,255,255,.6)',
                    }
                }
            }
        });
    }

    // Top products bar
    const productsCanvas = document.getElementById('productsChart');
    if (productsCanvas && typeof Chart !== 'undefined') {
        const labels = productsCanvas.dataset.labels
            ? JSON.parse(productsCanvas.dataset.labels)
            : ['NeoPulse X1','UltraBook Pro','ArcWatch Ultra','SoundPods Pro','SlimAir 13'];
        const data = productsCanvas.dataset.values
            ? JSON.parse(productsCanvas.dataset.values)
            : [145, 89, 203, 178, 61];

        new Chart(productsCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Units Sold',
                    data,
                    backgroundColor: 'rgba(129,140,248,.6)',
                    borderColor: '#818cf8',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0c1220',
                        borderColor: 'rgba(255,255,255,.08)',
                        borderWidth: 1,
                        titleColor: '#fff',
                        bodyColor: 'rgba(255,255,255,.6)',
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: 'rgba(255,255,255,.4)', font: { size: 10 } }
                    },
                    y: {
                        grid: { color: 'rgba(255,255,255,.04)', drawBorder: false },
                        ticks: { color: 'rgba(255,255,255,.4)', font: { size: 11 } }
                    }
                }
            }
        });
    }
}

/* ──────────────────────────────────────────────────────────── */
/*  TABLE SEARCH                                                */
/* ──────────────────────────────────────────────────────────── */
function initTableSearch() {
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(input => {
        const tableId = input.dataset.tableSearch;
        const table   = document.getElementById(tableId);
        if (!table) return;

        input.addEventListener('input', () => {
            const q = input.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(q) ? '' : 'none';
            });
        });
    });
}

/* ──────────────────────────────────────────────────────────── */
/*  DELETE CONFIRM                                              */
/* ──────────────────────────────────────────────────────────── */
function initDeleteConfirm() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function(e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) e.preventDefault();
        });
    });
}

/* ──────────────────────────────────────────────────────────── */
/*  AUTO-DISMISS ALERTS                                         */
/* ──────────────────────────────────────────────────────────── */
function initDismissAlerts() {
    document.querySelectorAll('.auto-dismiss').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .5s';
            el.style.opacity    = '0';
            setTimeout(() => el.remove(), 500);
        }, 4000);
    });
}

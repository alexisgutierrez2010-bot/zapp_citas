// js/modules/dashboard.js

/**
 * Renderiza la vista del Dashboard con gráficos de resumen.
 * @param {object} context - El objeto de contexto de la aplicación.
 */
export async function renderDashboardView(context) {
    const { dom, API_URL } = context;

    dom.appContainer.innerHTML = `
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Resumen de Gestion de mi negocio</h3>
            </div>
            <div id="dashboard-content" class="text-center">
                <div class="spinner-border" role="status"><span class="visually-hidden">Cargando datos...</span></div>
            </div>
        </div>
    `;

    try {
        const response = await fetch(`${API_URL}api_owner_dashboard_data.php`);
        if (!response.ok) throw new Error('No se pudieron cargar los datos del dashboard.');
        const data = await response.json();

        const dashboardContent = document.getElementById('dashboard-content');
        dashboardContent.innerHTML = `
            <div class="row">
                <div class="col-lg-6 mb-4"><div class="card"><div class="card-body"><canvas id="clientesChart"></canvas></div></div></div>
                <div class="col-lg-6 mb-4"><div class="card"><div class="card-body"><canvas id="citasChart"></canvas></div></div></div>
            </div>
            <div class="row">
                <div class="col-lg-6 mb-4"><div class="card"><div class="card-body"><canvas id="ingresosChart"></canvas></div></div></div>
                <div class="col-lg-6 mb-4"><div class="card"><div class="card-body"><canvas id="reunionesChart"></canvas></div></div></div>
            </div>
        `;

        // Nombres de los meses en español
        const monthNames = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];

        // Función para preparar datos y renderizar un gráfico de barra simple
        const renderSingleBarChart = (canvasId, chartTitle, chartData, dataKey, barColor = 'rgba(0, 123, 255, 0.5)') => {
            const labels = [];
            const dataPoints = [];
            
            // Crear un mapa para los últimos 6 meses con valor 0
            const last6Months = new Map();
            const hoy = new Date();
            for (let i = 5; i >= 0; i--) {
                const d = new Date(hoy.getFullYear(), hoy.getMonth() - i, 1);
                const key = `${d.getFullYear()}-${d.getMonth() + 1}`;
                last6Months.set(key, 0);
            }

            // Llenar con datos reales
            chartData.forEach(item => {
                const key = `${item.anio}-${item.mes}`;
                last6Months.set(key, Number(item[dataKey]));
            });

            // Preparar labels y data para el gráfico
            last6Months.forEach((value, key) => {
                const [year, month] = key.split('-');
                labels.push(`${monthNames[month - 1]} ${year.slice(-2)}`);
                dataPoints.push(value);
            });

            const ctx = document.getElementById(canvasId).getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: chartTitle,
                        data: dataPoints,
                        backgroundColor: barColor,
                        borderColor: barColor.replace('0.5', '1'),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: chartTitle }
                    },
                    // SOLUCIÓN: Añadir animación para mostrar los valores sobre las barras
                    animation: {
                        onComplete: function () {
                            const chart = this;
                            const ctx = this.ctx;
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';
                            ctx.fillStyle = '#666'; // Color del texto
                            this.data.datasets.forEach(function (dataset, i) {
                                const meta = chart.getDatasetMeta(i);
                                meta.data.forEach(function (bar, index) {
                                    const data = dataset.data[index];
                                    if (data > 0) { ctx.fillText(data, bar.x, bar.y - 5); }
                                });
                            });
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0 // No mostrar decimales en el eje Y
                            }
                        }
                    }
                }
            });
        };

        const renderMultiBarChart = (canvasId, chartTitle, chartData) => {
            const labels = [];
            const dataRegistradas = [];
            const dataCompletadas = [];
            const dataCanceladas = [];

            const last6Months = new Map();
            const hoy = new Date();
            for (let i = 5; i >= 0; i--) {
                const d = new Date(hoy.getFullYear(), hoy.getMonth() - i, 1);
                const key = `${d.getFullYear()}-${d.getMonth() + 1}`;
                last6Months.set(key, { registradas: 0, completadas: 0, canceladas: 0 });
            }

            chartData.forEach(item => {
                const key = `${item.anio}-${item.mes}`;
                last6Months.set(key, {
                    registradas: Number(item.total_registradas),
                    completadas: Number(item.total_completadas),
                    canceladas: Number(item.total_canceladas)
                });
            });

            last6Months.forEach((value, key) => {
                const [year, month] = key.split('-');
                labels.push(`${monthNames[month - 1]} ${year.slice(-2)}`);
                dataRegistradas.push(value.registradas);
                dataCompletadas.push(value.completadas);
                dataCanceladas.push(value.canceladas);
            });

            const ctx = document.getElementById(canvasId).getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Registradas',
                            data: dataRegistradas,
                            backgroundColor: 'rgba(0, 123, 255, 0.5)', // Azul
                            borderColor: 'rgba(0, 123, 255, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Completadas',
                            data: dataCompletadas,
                            backgroundColor: 'rgba(40, 167, 69, 0.5)', // Verde
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Canceladas',
                            data: dataCanceladas,
                            backgroundColor: 'rgba(220, 53, 69, 0.5)', // Rojo
                            borderColor: 'rgba(220, 53, 69, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true }, // Mostrar leyenda para este gráfico
                        title: { display: true, text: chartTitle }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });
        };

        // Renderizar los gráficos
        if (data.clientesPorMes.length > 0 || data.citasPorMes.length > 0 || data.ingresosPorMes.length > 0 || data.reunionesPorMes.length > 0) {
            renderSingleBarChart('clientesChart', 'Cantidad de Nuevos Clientes por Mes', data.clientesPorMes, 'total');
            renderMultiBarChart('citasChart', 'Estado de Citas por Mes', data.citasPorMes);
            renderSingleBarChart('ingresosChart', 'Ingresos por Servicios Completados ($)', data.ingresosPorMes, 'total', 'rgba(255, 193, 7, 0.5)'); // Amarillo/Dorado
            renderSingleBarChart('reunionesChart', 'Cantidad de Reuniones Registradas por Mes', data.reunionesPorMes, 'total', 'rgba(111, 66, 193, 0.5)'); // Morado
        } else {
            dashboardContent.innerHTML = `<div class="alert alert-info">Aún no hay suficientes datos para mostrar los gráficos.</div>`;
        }

    } catch (error) {
        const dashboardContent = document.getElementById('dashboard-content');
        dashboardContent.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}
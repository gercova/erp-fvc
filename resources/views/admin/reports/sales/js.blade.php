<script>
let salesChart; 

document.addEventListener("DOMContentLoaded", function () {
    let table = $('#salesReportTable').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'pdf', 'print']
    });

    function loadSalesReport() {
        let startDate = document.getElementById("start_date").value;
        let endDate = document.getElementById("end_date").value;
        let warehouse = document.getElementById("id_almacen") ? document.getElementById("id_almacen").value : '';

        fetch(`{{ route('report.sales.data') }}?start_date=${startDate}&end_date=${endDate}&id_almacen=${warehouse}`)
            .then(response => response.json())
            .then(data => {
                const signo = data.signo || "S/";
                table.clear();
                let sumTotal = 0, sumCant = 0, sumTicket = 0;

                data.sales.forEach(sale => {
                    sumTotal += Number(sale.total) || 0;
                    sumCant += parseInt(sale.cantidad_ventas);
                    
                    table.row.add([
                        sale.fecha,
                        `<span class="badge bg-secondary">${sale.cantidad_ventas}</span>`,
                        `${signo} ${parseFloat(sale.subtotal).toFixed(2)}`,
                        `${signo} ${parseFloat(sale.total_impuestos).toFixed(2)}`,
                        `<strong>${signo} ${parseFloat(sale.total).toFixed(2)}</strong>`,
                        `${signo} ${parseFloat(sale.ticket_promedio).toFixed(2)}`
                    ]);
                });
                table.draw();

                document.getElementById('kpi-total-ventas').innerText = `${signo} ${sumTotal.toFixed(2)}`;
                document.getElementById('kpi-cantidad-ventas').innerText = sumCant;
                document.getElementById('kpi-ticket-promedio').innerText = `${signo} ${(sumCant > 0 ? (sumTotal/sumCant) : 0).toFixed(2)}`;
                renderChart(data.sales, signo);
            });
    }

    document.getElementById("formReport").addEventListener("submit", function (event) {
        event.preventDefault();
        loadSalesReport();
    });

    if (document.getElementById("btnExportPdf")) {
        document.getElementById("btnExportPdf").addEventListener("click", function() {
            let startDate = document.getElementById("start_date").value;
            let endDate = document.getElementById("end_date").value;
            let warehouse = document.getElementById("id_almacen") ? document.getElementById("id_almacen").value : '';
            window.location.href = `{{ route('report.sales.by_date.pdf') }}?start_date=${startDate}&end_date=${endDate}&id_almacen=${warehouse}`;
        });
    }

    if (document.getElementById("btnExportExcel")) {
        document.getElementById("btnExportExcel").addEventListener("click", function() {
            let startDate = document.getElementById("start_date").value;
            let endDate = document.getElementById("end_date").value;
            let warehouse = document.getElementById("id_almacen") ? document.getElementById("id_almacen").value : '';
            window.location.href = `{{ route('report.sales.by_date.excel') }}?start_date=${startDate}&end_date=${endDate}&id_almacen=${warehouse}`;
        });
    }

    loadSalesReport();
});

function renderChart(salesData, signo) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    if (salesChart) { salesChart.destroy(); }

    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: salesData.map(s => s.fecha),
            datasets: [{
                label: 'Ventas Diarias',
                data: salesData.map(s => s.total),
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (context) => `${signo} ${context.parsed.y.toFixed(2)}`
                    }
                }
            }
        }
    });
}

    </script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    function loadPaymentReport() {
        let startDate = document.getElementById("start_date").value;
        let endDate = document.getElementById("end_date").value;
        let warehouse = document.getElementById("id_almacen") ? document.getElementById("id_almacen").value : '';

        if (!startDate || !endDate) {
            toast_msg("Seleccione un rango de fechas válido.", "warning");
            return;
        }

        fetch(`{{ route('report.sales.payment_methods') }}?start_date=${startDate}&end_date=${endDate}&id_almacen=${warehouse}`)
            .then(response => response.json())
            .then(data => {
                let tableBody = document.querySelector("#salesReportTable tbody");
                tableBody.innerHTML = "";

                if (!data.sales || data.sales.length === 0) {
                    tableBody.innerHTML = "<tr><td colspan='3' class='text-center'>No hay datos disponibles</td></tr>";
                    return;
                }

                data.sales.forEach(sale => {
                    let row = `<tr>
                        <td class="text-center font-weight-bold">${sale.metodo_pago}</td>
                        <td class="text-center"><span class="badge bg-secondary">${sale.cantidad_transacciones}</span></td>
                        <td class="text-center"><strong>${data.signo} ${parseFloat(sale.total_recaudado).toFixed(2)}</strong></td>
                    </tr>`;
                    tableBody.innerHTML += row;
                });
            })
            .catch(error => console.error("Error al obtener los datos:", error));
    }

    document.getElementById("formReport").addEventListener("submit", function (event) {
        event.preventDefault();
        loadPaymentReport();
    });

    if (document.getElementById("btnExportPdf")) {
        document.getElementById("btnExportPdf").addEventListener("click", function() {
            let startDate = document.getElementById("start_date").value;
            let endDate = document.getElementById("end_date").value;
            let warehouse = document.getElementById("id_almacen") ? document.getElementById("id_almacen").value : '';
            window.location.href = `{{ route('report.payments.pdf') }}?start_date=${startDate}&end_date=${endDate}&id_almacen=${warehouse}`;
        });
    }

    if (document.getElementById("btnExportExcel")) {
        document.getElementById("btnExportExcel").addEventListener("click", function() {
            let startDate = document.getElementById("start_date").value;
            let endDate = document.getElementById("end_date").value;
            let warehouse = document.getElementById("id_almacen") ? document.getElementById("id_almacen").value : '';
            window.location.href = `{{ route('report.payments.excel') }}?start_date=${startDate}&end_date=${endDate}&id_almacen=${warehouse}`;
        });
    }

    loadPaymentReport();
});
</script>
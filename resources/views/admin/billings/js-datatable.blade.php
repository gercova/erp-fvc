<script>
    function load_datatable() {
        return $('#table').DataTable({
            serverSide: true,
            paging: true,
            searching: true,
            destroy: true,
            responsive: false,
            ordering: false,
            autoWidth: false,
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, "Todos"]
            ],
            dom: '<"row"lr><"row"<"col-xs-12"t>><"row"<"col-sm-6"i><"col-sm-6"p>>',
            language: {
                decimal: "",
                emptyTable: "No hay información",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(Filtrado de _MAX_ total)",
                thousands: ",",
                lengthMenu: "Mostrar _MENU_",
                loadingRecords: "Cargando...",
                processing: "Procesando...",
                search: "",
                searchPlaceholder: "Buscar...",
                zeroRecords: "Sin resultados encontrados",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                }
            },
            ajax: {
                url: "{{ route('billings.get') }}",
                data: function(d) {
                    d.date = $('#date-filter').val();
                    d.serie = $('#serie-filter').val();
                    d.correlativo = $('#correlativo-filter').val();
                    d.customer = $('#customer-filter').val();
                    d.total = $('#total-filter').val();
                    d.status = $('#status-filter').val();
                }
            },
            stripeClasses: [],
            columns: [
                { data: 'fecha_emision', name: 'billings.fecha_emision', className: 'text-center' },
                { data: 'comprobante', name: 'comprobante', className: 'text-center', searchable: true },
                { data: 'cliente_info', name: 'clients.nombres' },
                { data: 'almacen_badge', name: 'warehouses.descripcion', className: 'text-center', orderable: false, searchable: false },
                { data: 'total', name: 'billings.total', className: 'text-center' },
                { data: 'xml', orderable: false, searchable: false, className: 'text-center' },
                { data: 'cdr_archivo', orderable: false, searchable: false, className: 'text-center' },
                { data: 'sunat_badge', orderable: false, searchable: false, className: 'text-center' },
                { data: 'estado_badge', orderable: false, searchable: false, className: 'text-center' },
                { data: 'acciones', orderable: false, searchable: false, className: 'text-center' }
            ],
            drawCallback: function() {
                $('.dataTables_paginate ul.pagination').addClass("pagination-sm");
            }
        });
    }

    $(document).ready(function() {
        let datatable = load_datatable();

        $('#date-filter').on('change', function() {
            datatable.draw();
        });

        $('#serie-filter, #correlativo-filter, #customer-filter, #total-filter').on('keyup change', function() {
            datatable.draw();
        });

        $('#status-filter').on('change', function() {
            datatable.draw();
        });

        $('#btn-reset-filters').on('click', function() {
            $('#date-filter').val('');
            $('#serie-filter').val('');
            $('#correlativo-filter').val('');
            $('#customer-filter').val('');
            $('#total-filter').val('');
            $('#status-filter').val('');
            datatable.search('').columns().search('').draw();
        });
    });
</script>

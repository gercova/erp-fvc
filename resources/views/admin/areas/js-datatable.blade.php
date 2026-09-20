<script>
    function load_datatable() {
        $('#table').DataTable({
            serverSide: true,
            paging: true,
            searching: true,
            destroy: true,
            responsive: true,
            ordering: false,
            autoWidth: false,
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, 'Todos']
            ],
            language: {
                decimal: '',
                emptyTable: 'No hay información',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(Filtrado de _MAX_ total)',
                thousands: ',',
                lengthMenu: 'Mostrar _MENU_',
                loadingRecords: 'Cargando...',
                processing: 'Procesando...',
                search: '',
                searchPlaceholder: 'Buscar área...',
                zeroRecords: 'Sin resultados encontrados',
                paginate: {
                    first: 'Primero',
                    last: 'Ultimo',
                    next: 'Siguiente',
                    previous: 'Anterior'
                }
            },
            ajax: "{{ route('areas.get') }}",
            stripeClasses: [],
            columns: [
                { data: 'codigo', name: 'code', className: 'text-center' },
                { data: 'nombre', name: 'name', className: 'text-start' },
                { data: 'tipo', name: 'type', className: 'text-center' },
                { data: 'parent', name: 'parent.name', className: 'text-start' },
                { data: 'head', name: 'head.nombres', className: 'text-start' },
                { data: 'level', name: 'level', className: 'text-center' },
                { data: 'is_advisory', name: 'is_advisory', className: 'text-center' },
                { data: 'acciones', name: 'acciones', className: 'text-center', orderable: false, searchable: false }
            ],
            drawCallback: function() {
                $('.dataTables_paginate ul.pagination').addClass('pagination-sm');
            }
        });
    }

    function reload_table() {
        $('#table').DataTable().ajax.reload(null, false);
    }

    $(document).ready(function() {
        load_datatable();
    });
</script>

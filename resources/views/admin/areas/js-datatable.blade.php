<script>
    window.selectedAreaIds = window.selectedAreaIds || new Set();
    window.selectAllPages = false;
    window.totalRecordsCount = 0;

    function updateCheckAllState(pageCheckedCount, totalVisible) {
        if (typeof pageCheckedCount === 'undefined') {
            pageCheckedCount = $('.check-area-row:checked').length;
            totalVisible = $('.check-area-row').length;
        }

        if (totalVisible === 0) {
            $('#check_all_areas').prop('checked', false).prop('indeterminate', false);
            return;
        }

        if (window.selectAllPages) {
            $('#check_all_areas').prop('checked', true).prop('indeterminate', false);
            return;
        }

        if (pageCheckedCount === totalVisible) {
            $('#check_all_areas').prop('checked', true).prop('indeterminate', false);
        } else if (pageCheckedCount > 0) {
            $('#check_all_areas').prop('checked', false).prop('indeterminate', true);
        } else {
            $('#check_all_areas').prop('checked', false).prop('indeterminate', false);
        }
    }

    function updateBulkToolbar(pageCheckedCount, totalVisible) {
        if (typeof pageCheckedCount === 'undefined') {
            pageCheckedCount = $('.check-area-row:checked').length;
            totalVisible = $('.check-area-row').length;
        }

        const totalSelected = window.selectAllPages ? window.totalRecordsCount : window.selectedAreaIds.size;
        $('.selected-count').text(totalSelected);

        if (totalSelected > 0) {
            $('#bulk_toolbar').removeClass('d-none').addClass('d-flex');
            $('#btn_bulk_delete_header').removeClass('d-none');

            if (window.selectAllPages) {
                $('#all_pages_selected_notice').removeClass('d-none');
                $('#select_all_pages_prompt').addClass('d-none');
                $('#selection_page_info').text('');
            } else {
                $('#all_pages_selected_notice').addClass('d-none');
                if (pageCheckedCount > 0 && totalSelected > pageCheckedCount) {
                    $('#selection_page_info').text(`(${pageCheckedCount} en esta página)`);
                } else if (pageCheckedCount === 0 && totalSelected > 0) {
                    $('#selection_page_info').text(`(en otras páginas)`);
                } else {
                    $('#selection_page_info').text('');
                }

                if (pageCheckedCount === totalVisible && totalVisible > 0 && window.totalRecordsCount > totalVisible) {
                    $('#select_all_pages_prompt').removeClass('d-none');
                } else {
                    $('#select_all_pages_prompt').addClass('d-none');
                }
            }
        } else {
            $('#bulk_toolbar').addClass('d-none').removeClass('d-flex');
            $('#btn_bulk_delete_header').addClass('d-none');
            $('#select_all_pages_prompt').addClass('d-none');
            $('#all_pages_selected_notice').addClass('d-none');
            $('#selection_page_info').text('');
        }
    }

    function syncCheckboxStates() {
        let pageCheckedCount = 0;
        const totalVisible = $('.check-area-row').length;

        $('.check-area-row').each(function() {
            const id = parseInt($(this).val(), 10);
            if (window.selectAllPages || window.selectedAreaIds.has(id)) {
                $(this).prop('checked', true);
                $(this).closest('tr').addClass('table-active');
                pageCheckedCount++;
            } else {
                $(this).prop('checked', false);
                $(this).closest('tr').removeClass('table-active');
            }
        });

        updateCheckAllState(pageCheckedCount, totalVisible);
        updateBulkToolbar(pageCheckedCount, totalVisible);
    }

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
                { data: 'checkbox', name: 'checkbox', className: 'text-center align-middle', orderable: false, searchable: false },
                { data: 'codigo', name: 'code', className: 'text-center align-middle' },
                { data: 'nombre', name: 'name', className: 'text-start align-middle' },
                { data: 'tipo', name: 'type', className: 'text-center align-middle' },
                { data: 'parent', name: 'parent.name', className: 'text-start align-middle' },
                { data: 'head', name: 'head.nombres', className: 'text-start align-middle' },
                { data: 'level', name: 'level', className: 'text-center align-middle' },
                { data: 'is_advisory', name: 'is_advisory', className: 'text-center align-middle' },
                { data: 'acciones', name: 'acciones', className: 'text-center align-middle', orderable: false, searchable: false }
            ],
            drawCallback: function() {
                $('.dataTables_paginate ul.pagination').addClass('pagination-sm');
                const api = this.api();
                const info = api.page.info();
                window.totalRecordsCount = info.recordsDisplay || info.recordsTotal || 0;
                $('.total-table-count').text(window.totalRecordsCount);
                syncCheckboxStates();
            }
        });
    }

    function reload_table() {
        $('#table').DataTable().ajax.reload(null, false);
    }

    $(document).ready(function() {
        load_datatable();

        $('body').on('change', '#check_all_areas', function() {
            const isChecked = $(this).is(':checked');
            if (!isChecked) {
                window.selectAllPages = false;
            }

            $('.check-area-row').each(function() {
                const id = parseInt($(this).val(), 10);
                $(this).prop('checked', isChecked);
                if (isChecked) {
                    window.selectedAreaIds.add(id);
                    $(this).closest('tr').addClass('table-active');
                } else {
                    window.selectedAreaIds.delete(id);
                    $(this).closest('tr').removeClass('table-active');
                }
            });

            const pageChecked = isChecked ? $('.check-area-row').length : 0;
            updateCheckAllState(pageChecked, $('.check-area-row').length);
            updateBulkToolbar(pageChecked, $('.check-area-row').length);
        });

        $('body').on('click', '#btn_select_all_pages', function(e) {
            e.preventDefault();
            window.selectAllPages = true;
            $('.check-area-row').prop('checked', true).closest('tr').addClass('table-active');
            $('#check_all_areas').prop('checked', true).prop('indeterminate', false);
            updateBulkToolbar();
        });

        $('body').on('change', '.check-area-row', function() {
            const id = parseInt($(this).val(), 10);
            if (window.selectAllPages) {
                window.selectAllPages = false;
            }

            if ($(this).is(':checked')) {
                window.selectedAreaIds.add(id);
                $(this).closest('tr').addClass('table-active');
            } else {
                window.selectedAreaIds.delete(id);
                $(this).closest('tr').removeClass('table-active');
            }

            syncCheckboxStates();
        });

        $('body').on('click', '#btn_clear_selection', function() {
            window.selectedAreaIds.clear();
            window.selectAllPages = false;
            $('.check-area-row').prop('checked', false).closest('tr').removeClass('table-active');
            $('#check_all_areas').prop('checked', false).prop('indeterminate', false);
            updateBulkToolbar(0, $('.check-area-row').length);
        });

        $('body').on('click', '#btn_bulk_delete, #btn_bulk_delete_header', function(event) {
            event.preventDefault();
            const count = window.selectAllPages ? window.totalRecordsCount : window.selectedAreaIds.size;

            if (count === 0) {
                toast_msg('Seleccione al menos un área para eliminar.', 'warning');
                return;
            }

            const idsArray = window.selectAllPages ? [] : Array.from(window.selectedAreaIds);

            Swal.fire({
                title: `¿Eliminar ${count} área(s) seleccionada(s)?`,
                html: `Se procederá a dar de baja <b>${count}</b> área(s) en lote.<br><br><span class="text-danger small"><i class="ri-error-warning-line me-1"></i>Las áreas que tengan dependencias o personal/bienes asignados serán omitidas para resguardar la consistencia institucional.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `<i class="ri-delete-bin-line me-1"></i> Sí, eliminar (${count})`,
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: "{{ route('areas.bulk_delete') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        all: window.selectAllPages ? 1 : 0,
                        ids: idsArray
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Eliminando en lote...',
                            text: 'Procesando eliminación de registros seleccionados, por favor espere...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });
                    },
                    success: function(r) {
                        Swal.close();
                        toast_msg(r.msg, r.type || 'success');

                        window.selectedAreaIds.clear();
                        window.selectAllPages = false;
                        updateBulkToolbar(0, 0);
                        reload_table();
                    },
                    error: function(xhr) {
                        Swal.close();
                        let errorMsg = xhr.responseJSON?.msg || 'Hubo un problema al procesar la eliminación en lote.';
                        toast_msg(errorMsg, xhr.responseJSON?.type || 'warning');

                        if (xhr.responseJSON?.deleted_count && xhr.responseJSON.deleted_count > 0) {
                            window.selectedAreaIds.clear();
                            window.selectAllPages = false;
                            updateBulkToolbar(0, 0);
                            reload_table();
                        }
                    },
                    dataType: 'json'
                });
            });
        });
    });
</script>

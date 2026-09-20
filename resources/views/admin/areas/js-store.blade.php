<script>
    function initAreaSelects(modalSelector) {
        $(`${modalSelector} select[name="type"]`).select2({
            placeholder: '[SELECCIONE]',
            dropdownParent: $(`${modalSelector} .modal-body`),
            width: '100%'
        });

        $(`${modalSelector} select[name="parent_id"]`).select2({
            placeholder: '[NINGUNA - NIVEL PRINCIPAL]',
            dropdownParent: $(`${modalSelector} .modal-body`),
            width: '100%',
            allowClear: true
        });

        $(`${modalSelector} select[name="head_user_id"]`).select2({
            placeholder: '[SIN ASIGNAR]',
            dropdownParent: $(`${modalSelector} .modal-body`),
            width: '100%',
            allowClear: true
        });
    }

    function resetAreaForm(formSelector) {
        const form = $(formSelector);
        form.trigger('reset');
        form.find('select[name="type"]').val('').trigger('change');
        form.find('select[name="parent_id"]').val('').trigger('change');
        form.find('select[name="head_user_id"]').val('').trigger('change');
        form.find('input[name="is_advisory"]').prop('checked', false);
    }

    $('body').on('click', '.btn-create', function(event) {
        event.preventDefault();
        initAreaSelects('#modalAddArea');
        resetAreaForm('#form_save_area');
        $('#modalAddArea').modal('show');
    });

    $('body').on('submit', '#form_save_area', function(event) {
        event.preventDefault();

        $.ajax({
            url: "{{ route('areas.save') }}",
            method: 'POST',
            data: $(this).serialize(),
            beforeSend: function() {
                $('.btn-save-area').prop('disabled', true);
                $('#form_save_area .text-saving').removeClass('d-none');
                $('#form_save_area .text-save').addClass('d-none');
            },
            success: function(r) {
                $('.btn-save-area').prop('disabled', false);
                $('#form_save_area .text-saving').addClass('d-none');
                $('#form_save_area .text-save').removeClass('d-none');

                if (!r.status) {
                    toast_msg(r.msg, r.type || 'warning');
                    return;
                }

                $('#modalAddArea').modal('hide');
                resetAreaForm('#form_save_area');
                toast_msg(r.msg, r.type || 'success');
                reload_table();
            },
            error: function(xhr) {
                $('.btn-save-area').prop('disabled', false);
                $('#form_save_area .text-saving').addClass('d-none');
                $('#form_save_area .text-save').removeClass('d-none');

                let errorMsg = xhr.responseJSON?.msg || xhr.responseJSON?.message || 'No se pudo registrar el área.';
                if (xhr.responseJSON?.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors)[0][0];
                }
                toast_msg(errorMsg, 'warning');
            },
            dataType: 'json'
        });
    });

    $('body').on('click', '.btn-detail', function(event) {
        event.preventDefault();
        let id = $(this).data('id');

        $.ajax({
            url: "{{ route('areas.detail') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                id: id
            },
            beforeSend: function() {
                block_content('#layout-content');
            },
            success: function(r) {
                close_block('#layout-content');

                if (!r.status || !r.area) {
                    toast_msg(r.msg || 'No se pudo obtener el detalle.', r.type || 'warning');
                    return;
                }

                initAreaSelects('#modalEditArea');
                let area = r.area;

                $('#form_edit_area input[name="id"]').val(area.id);
                $('#form_edit_area input[name="code"]').val(area.code);
                $('#form_edit_area input[name="name"]').val(area.name);
                $('#form_edit_area select[name="type"]').val(area.type).trigger('change');
                $('#form_edit_area input[name="level"]').val(area.level || 1);
                $('#form_edit_area select[name="parent_id"]').val(area.parent_id || '').trigger('change');
                $('#form_edit_area select[name="head_user_id"]').val(area.head_user_id || '').trigger('change');
                $('#form_edit_area input[name="is_advisory"]').prop('checked', !!area.is_advisory);

                $('#modalEditArea').modal('show');
            },
            error: function(xhr) {
                close_block('#layout-content');
                toast_msg(xhr.responseJSON?.msg || 'No se pudo cargar la información del área.', 'warning');
            },
            dataType: 'json'
        });
    });

    $('body').on('submit', '#form_edit_area', function(event) {
        event.preventDefault();

        $.ajax({
            url: "{{ route('areas.store') }}",
            method: 'POST',
            data: $(this).serialize(),
            beforeSend: function() {
                $('.btn-store-area').prop('disabled', true);
                $('#form_edit_area .text-storing').removeClass('d-none');
                $('#form_edit_area .text-store').addClass('d-none');
            },
            success: function(r) {
                $('.btn-store-area').prop('disabled', false);
                $('#form_edit_area .text-storing').addClass('d-none');
                $('#form_edit_area .text-store').removeClass('d-none');

                if (!r.status) {
                    toast_msg(r.msg, r.type || 'warning');
                    return;
                }

                $('#modalEditArea').modal('hide');
                toast_msg(r.msg, r.type || 'success');
                reload_table();
            },
            error: function(xhr) {
                $('.btn-store-area').prop('disabled', false);
                $('#form_edit_area .text-storing').addClass('d-none');
                $('#form_edit_area .text-store').removeClass('d-none');

                let errorMsg = xhr.responseJSON?.msg || xhr.responseJSON?.message || 'No se pudo actualizar el área.';
                if (xhr.responseJSON?.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors)[0][0];
                }
                toast_msg(errorMsg, 'warning');
            },
            dataType: 'json'
        });
    });

    $('body').on('click', '.btn-confirm', function(event) {
        event.preventDefault();
        let id = $(this).data('id');

        Swal.fire({
            title: '¿Desea eliminar el área?',
            text: 'Esta acción desactivará el área del organigrama institucional.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: "{{ route('areas.delete') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id
                },
                beforeSend: function() {
                    Swal.fire({
                        title: 'Eliminando...',
                        text: 'Por favor, espere',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                },
                success: function(r) {
                    Swal.close();
                    toast_msg(r.msg, r.type || 'success');

                    if (r.status) {
                        reload_table();
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    toast_msg(xhr.responseJSON?.msg || 'Hubo un problema al procesar la solicitud.', xhr.responseJSON?.type || 'warning');
                },
                dataType: 'json'
            });
        });
    });
</script>

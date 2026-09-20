<script>
    function initUserSelects(modalSelector) {
        $(`${modalSelector} select[name="idcaja"]`).select2({
            placeholder: '[SELECCIONE]',
            dropdownParent: $(`${modalSelector} .modal-body`),
            width: '100%'
        });

        $(`${modalSelector} select[name="role"]`).select2({
            placeholder: '[SELECCIONE]',
            dropdownParent: $(`${modalSelector} .modal-body`),
            width: '100%'
        });

        $(`${modalSelector} select[name="warehouse_ids[]"]`).select2({
            placeholder: '[SELECCIONE AL MENOS UN ALMACEN]',
            dropdownParent: $(`${modalSelector} .modal-body`),
            width: '100%'
        });

        $(`${modalSelector} select[name="area_id"]`).select2({
            placeholder: '[SIN ÁREA ASIGNADA]',
            dropdownParent: $(`${modalSelector} .modal-body`),
            width: '100%',
            allowClear: true
        });

        $(`${modalSelector} select[name="condicion_laboral"]`).select2({
            placeholder: '[SELECCIONE]',
            dropdownParent: $(`${modalSelector} .modal-body`),
            width: '100%',
            allowClear: true
        });
    }

    function resetUserForm(formSelector) {
        const form = $(formSelector);
        form.trigger('reset');
        form.find('select[name="role"]').val('').trigger('change');
        form.find('select[name="warehouse_ids[]"]').val(null).trigger('change');
        form.find('select[name="estado"]').val('1').trigger('change');
        form.find('select[name="area_id"]').val('').trigger('change');
        form.find('select[name="condicion_laboral"]').val('').trigger('change');
        form.find('input[name="cargo"]').val('');
        form.find('input[name="password"]').val('');
        form.find('input[name="firma_digital"]').val('');

        if (formSelector === '#form_edit') {
            $('#edit_remove_firma_digital').val('0');
            $('#wrapper_signature_preview').addClass('d-none');
            $('#img_signature_preview').attr('src', '');
        }
    }

    $('body').on('click', '.btn-create', function(event) {
        event.preventDefault();
        initUserSelects('#modalAddUser');
        resetUserForm('#form_save');
        $('#modalAddUser').modal('show');
    });

    $('body').on('click', '.btn-save', function(event) {
        event.preventDefault();

        const formElement = document.getElementById('form_save');
        const formData = new FormData(formElement);

        $.ajax({
            url: "{{ route('users.save') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('.btn-save').prop('disabled', true);
                $('#modalAddUser .text-saving').removeClass('d-none');
                $('#modalAddUser .text-save').addClass('d-none');
            },
            success: function(r) {
                $('.btn-save').prop('disabled', false);
                $('#modalAddUser .text-saving').addClass('d-none');
                $('#modalAddUser .text-save').removeClass('d-none');

                if (!r.status) {
                    toast_msg(r.msg, r.type || 'warning');
                    return;
                }

                $('#modalAddUser').modal('hide');
                resetUserForm('#form_save');
                toast_msg(r.msg, r.type || 'success');
                reload_table();
            },
            error: function(xhr) {
                $('.btn-save').prop('disabled', false);
                $('#modalAddUser .text-saving').addClass('d-none');
                $('#modalAddUser .text-save').removeClass('d-none');

                let errorMsg = xhr.responseJSON?.msg || xhr.responseJSON?.message || 'No se pudo registrar el usuario.';
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
            url: "{{ route('users.detail') }}",
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

                if (!r.status) {
                    toast_msg(r.msg, r.type || 'warning');
                    return;
                }

                initUserSelects('#modalEditUser');
                resetUserForm('#form_edit');

                $('#form_edit input[name="id"]').val(r.user.id);
                $('#form_edit input[name="nombres"]').val(r.user.nombres);
                $('#form_edit input[name="user"]').val(r.user.user);
                $('#form_edit input[name="password"]').val('');
                $('#form_edit select[name="idcaja"]').val(String(r.user.idcaja)).trigger('change');
                $('#form_edit select[name="role"]').val(r.role || '').trigger('change');
                $('#form_edit select[name="estado"]').val(String(r.user.estado)).trigger('change');
                $('#form_edit select[name="warehouse_ids[]"]').val((r.warehouse_ids || []).map(String)).trigger('change');

                // Area & Cargo details
                $('#form_edit select[name="area_id"]').val(r.area_id || '').trigger('change');
                $('#form_edit input[name="cargo"]').val(r.cargo || '');
                $('#form_edit select[name="condicion_laboral"]').val(r.condicion_laboral || '').trigger('change');

                // Digital signature preview
                if (r.firma_digital_url) {
                    $('#img_signature_preview').attr('src', r.firma_digital_url);
                    $('#wrapper_signature_preview').removeClass('d-none');
                    $('#edit_remove_firma_digital').val('0');
                } else {
                    $('#wrapper_signature_preview').addClass('d-none');
                    $('#img_signature_preview').attr('src', '');
                    $('#edit_remove_firma_digital').val('0');
                }

                $('#modalEditUser').modal('show');
            },
            error: function(xhr) {
                close_block('#layout-content');
                toast_msg(xhr.responseJSON?.msg || 'No se pudo cargar el usuario.', xhr.responseJSON?.type || 'warning');
            },
            dataType: 'json'
        });
    });

    $('body').on('click', '#btn_remove_signature', function(event) {
        event.preventDefault();
        $('#edit_remove_firma_digital').val('1');
        $('#wrapper_signature_preview').addClass('d-none');
        $('#form_edit input[name="firma_digital"]').val('');
        toast_msg('La firma digital actual se quitará al guardar los cambios.', 'info');
    });

    $('body').on('click', '.btn-store', function(event) {
        event.preventDefault();

        const formElement = document.getElementById('form_edit');
        const formData = new FormData(formElement);

        $.ajax({
            url: "{{ route('users.store') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('.btn-store').prop('disabled', true);
                $('#modalEditUser .text-storing').removeClass('d-none');
                $('#modalEditUser .text-store').addClass('d-none');
            },
            success: function(r) {
                $('.btn-store').prop('disabled', false);
                $('#modalEditUser .text-storing').addClass('d-none');
                $('#modalEditUser .text-store').removeClass('d-none');

                if (!r.status) {
                    toast_msg(r.msg, r.type || 'warning');
                    return;
                }

                $('#modalEditUser').modal('hide');
                toast_msg(r.msg, r.type || 'success');
                reload_table();
            },
            error: function(xhr) {
                $('.btn-store').prop('disabled', false);
                $('#modalEditUser .text-storing').addClass('d-none');
                $('#modalEditUser .text-store').removeClass('d-none');

                let errorMsg = xhr.responseJSON?.msg || xhr.responseJSON?.message || 'No se pudo actualizar el usuario.';
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
            title: '¿Desea eliminar el registro?',
            text: 'Esta acción no se puede deshacer',
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
                url: "{{ route('users.delete') }}",
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
                    toast_msg(r.msg, r.type);

                    if (r.status) {
                        reload_table();
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    toast_msg(xhr.responseJSON?.msg || 'Hubo un problema en la solicitud.', xhr.responseJSON?.type || 'warning');
                }
            });
        });
    });

    // Bulk Excel Upload
    $('body').on('click', '.btn-upload-excel', function(event) {
        event.preventDefault();
        $('#form_excel_users').trigger('reset');
        $('#modalUploadUsers').modal('show');
    });

    $('body').on('submit', '#form_excel_users', function(event) {
        event.preventDefault();

        const formElement = document.getElementById('form_excel_users');
        const formData = new FormData(formElement);

        $.ajax({
            url: "{{ route('users.upload_excel') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('.btn-upload-user-submit').prop('disabled', true);
                $('#modalUploadUsers .text-uploading-user').removeClass('d-none');
                $('#modalUploadUsers .text-upload-user').addClass('d-none');
            },
            success: function(r) {
                $('.btn-upload-user-submit').prop('disabled', false);
                $('#modalUploadUsers .text-uploading-user').addClass('d-none');
                $('#modalUploadUsers .text-upload-user').removeClass('d-none');

                if (!r.status) {
                    toast_msg(r.msg, r.type || 'warning');
                    return;
                }

                $('#modalUploadUsers').modal('hide');
                $('#form_excel_users').trigger('reset');
                toast_msg(r.msg, r.type || 'success');
                reload_table();
            },
            error: function(xhr) {
                $('.btn-upload-user-submit').prop('disabled', false);
                $('#modalUploadUsers .text-uploading-user').addClass('d-none');
                $('#modalUploadUsers .text-upload-user').removeClass('d-none');

                let errorMsg = xhr.responseJSON?.msg || xhr.responseJSON?.message || 'No se pudo importar el archivo Excel.';
                toast_msg(errorMsg, 'warning');
            },
            dataType: 'json'
        });
    });

    // Quick Role Dialog
    $('body').on('click', '.btn-roles', function(event) {
        event.preventDefault();
        let id = $(this).data('id');

        $.ajax({
            url: "{{ route('users.view_role') }}",
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

                if (!r.status) {
                    toast_msg(r.msg, r.type);
                    return;
                }

                let htmlRoles = '';
                $('#modalUpdateRole input[name="id"]').val(r.data.user.id);
                $('#modalUpdateRole input[name="usuario"]').val(r.data.user.nombres);

                $.each(r.data.roles, function(index, role) {
                    htmlRoles += `
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" value="${role.name}" name="roles[]" id="role_${role.id}" ${r.data.selRoles.includes(role.name) ? 'checked' : ''}>
                            <label class="form-check-label" for="role_${role.id}">
                                ${role.name}
                            </label>
                        </div>
                    `;
                });

                $('#wrapper_roles').html(htmlRoles);
                $('#modalUpdateRole').modal('show');
            },
            error: function(xhr) {
                close_block('#layout-content');
                toast_msg(xhr.responseJSON?.msg || 'No se pudieron cargar los roles.', xhr.responseJSON?.type || 'warning');
            },
            dataType: 'json'
        });
    });

    $('body').on('click', '.btn-update-role', function(event) {
        event.preventDefault();

        $.ajax({
            url: "{{ route('users.update_role') }}",
            method: 'POST',
            data: $('#form_update_role').serialize(),
            beforeSend: function() {
                $('#modalUpdateRole .btn-update-role').prop('disabled', true);
                $('#modalUpdateRole .text-update-role').addClass('d-none');
                $('#modalUpdateRole .text-saving-role').removeClass('d-none');
            },
            success: function(r) {
                $('#modalUpdateRole .btn-update-role').prop('disabled', false);
                $('#modalUpdateRole .text-update-role').removeClass('d-none');
                $('#modalUpdateRole .text-saving-role').addClass('d-none');

                if (!r.status) {
                    toast_msg(r.msg, r.type);
                    return;
                }

                $('#modalUpdateRole').modal('hide');
                toast_msg(r.msg, r.type);
                reload_table();
            },
            error: function(xhr) {
                $('#modalUpdateRole .btn-update-role').prop('disabled', false);
                $('#modalUpdateRole .text-update-role').removeClass('d-none');
                $('#modalUpdateRole .text-saving-role').addClass('d-none');
                toast_msg(xhr.responseJSON?.msg || 'No se pudo actualizar el rol.', xhr.responseJSON?.type || 'warning');
            },
            dataType: 'json'
        });
    });
</script>

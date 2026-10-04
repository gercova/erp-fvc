@extends('errors.layout')

@section('badge')
<div class="error-code-badge" style="background:#fff7ed;color:#9a3412;border-color:#fed7aa;">
    <span class="dot" style="background:#9a3412;"></span> HTTP 422
</div>
@endsection

@section('code', '422')
@section('title', 'Datos no procesables')
@section('grad-from', '#7c2d12')
@section('grad-to',   '#ea580c')

@section('description')
    Los datos enviados no superaron la validación del servidor.
    Revisa cada campo del formulario y asegúrate de que la información ingresada sea correcta y completa.
@endsection

@section('extra')
@if(!empty($errors) && $errors->count() > 0)
<div class="error-detail-block" style="text-align:left;">
    <div style="font-weight:600; color:#495057; margin-bottom:0.4rem; font-family:Inter,sans-serif;">Errores detectados:</div>
    <ul style="margin:0; padding-left:1.2rem; color:#dc2626;">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
@endsection

@section('actions')
    <a href="javascript:history.back()" class="btn-primary-erp">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Corregir datos
    </a>
    <a href="{{ url('/') }}" class="btn-secondary-erp">Ir al inicio</a>
@endsection

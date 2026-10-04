@extends('errors.layout')

@section('badge')
<div class="error-code-badge" style="background:#fef9c3;color:#854d0e;border-color:#fde047;">
    <span class="dot" style="background:#854d0e;"></span> HTTP 429
</div>
@endsection

@section('code', '429')
@section('title', 'Demasiadas solicitudes')
@section('grad-from', '#713f12')
@section('grad-to',   '#ca8a04')

@section('description')
    Has realizado demasiadas solicitudes en poco tiempo.
    Por favor espera unos momentos antes de intentarlo de nuevo.
@endsection

@section('actions')
    <a href="javascript:history.back()" class="btn-primary-erp">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Volver
    </a>
    <a href="{{ url('/') }}" class="btn-secondary-erp">Ir al inicio</a>
@endsection

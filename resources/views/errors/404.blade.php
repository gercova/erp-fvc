@extends('errors.layout')

@section('badge')
<div class="error-code-badge" style="background:#eff6ff;color:#1e40af;border-color:#bfdbfe;">
    <span class="dot" style="background:#1e40af;"></span> HTTP 404
</div>
@endsection

@section('code', '404')
@section('title', 'Página no encontrada')
@section('grad-from', '#1e3a5f')
@section('grad-to',   '#0d6efd')

@section('description')
    El recurso que buscas no existe o fue movido a otra dirección.
    Verifica la URL e intenta nuevamente, o regresa a la página de inicio.
@endsection

@section('extra')
@if(request()->path() !== '/')
<div class="error-detail-block">
    <span style="color:#adb5bd;">Ruta solicitada:</span><br>
    /{{ request()->path() }}
</div>
@endif
@endsection

@section('actions')
    <a href="{{ url('/') }}" class="btn-primary-erp">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Ir al inicio
    </a>
    <a href="javascript:history.back()" class="btn-secondary-erp">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Volver
    </a>
@endsection

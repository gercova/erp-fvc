@extends('errors.layout')

@section('badge')
<div class="error-code-badge" style="background:#fef2f2;color:#991b1b;border-color:#fecaca;">
    <span class="dot" style="background:#991b1b;"></span> HTTP 500
</div>
@endsection

@section('code', '500')
@section('title', 'Error interno del servidor')
@section('grad-from', '#450a0a')
@section('grad-to',   '#b91c1c')

@section('description')
    Ocurrió un error inesperado en el servidor mientras procesaba tu solicitud.
    Nuestro equipo técnico ya fue notificado. Intenta nuevamente en unos minutos.
@endsection

@section('extra')
@if(config('app.debug') && !empty($exception))
<div class="error-detail-block">
    <div style="color:#dc2626; font-weight:600; margin-bottom:0.4rem; font-family:Inter,sans-serif;">
        {{ get_class($exception) }}
    </div>
    {{ $exception->getMessage() }}<br><br>
    <span style="color:#adb5bd;">{{ $exception->getFile() }}:{{ $exception->getLine() }}</span>
</div>
@endif
@endsection

@section('actions')
    <a href="{{ url('/') }}" class="btn-primary-erp">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Ir al inicio
    </a>
    <a href="javascript:location.reload()" class="btn-secondary-erp">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        Reintentar
    </a>
@endsection

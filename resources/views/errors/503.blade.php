@extends('errors.layout')

@section('badge')
<div class="error-code-badge" style="background:#f0fdf4;color:#14532d;border-color:#bbf7d0;">
    <span class="dot" style="background:#14532d;"></span> HTTP 503
</div>
@endsection

@section('code', '503')
@section('title', 'Servicio en mantenimiento')
@section('grad-from', '#14532d')
@section('grad-to',   '#16a34a')

@section('description')
    @if(isset($exception) && $exception->getMessage())
        {{ $exception->getMessage() }}
    @else
        El sistema está temporalmente fuera de servicio por tareas de mantenimiento.
        Estará disponible nuevamente en breve. Agradecemos tu paciencia.
    @endif
@endsection

@section('extra')
<div style="display:flex; align-items:center; justify-content:center; gap:0.5rem; margin-bottom:1.75rem; font-size:0.82rem; color:#6c757d;">
    <svg width="14" height="14" fill="none" stroke="#16a34a" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
    </svg>
    Tiempo estimado: <strong style="color:#16a34a;">Pocos minutos</strong>
</div>
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="btn-primary-erp">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        Verificar nuevamente
    </a>
@endsection

@extends('errors.layout')

@section('badge')
<div class="error-code-badge" style="background:#fdf4ff;color:#6b21a8;border-color:#e9d5ff;">
    <span class="dot" style="background:#6b21a8;"></span> HTTP 419
</div>
@endsection

@section('code', '419')
@section('title', 'Sesión expirada')
@section('grad-from', '#581c87')
@section('grad-to',   '#9333ea')

@section('description')
    Tu sesión o el token CSRF del formulario han expirado por inactividad.
    Por seguridad, deberás recargar la página y volver a intentarlo.
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="btn-primary-erp">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        Recargar página
    </a>
    <a href="{{ route('login') }}" class="btn-secondary-erp">Ir al login</a>
@endsection

@extends('errors.layout')

@section('badge')
<div class="error-code-badge" style="background:#fef2f2;color:#991b1b;border-color:#fecaca;">
    <span class="dot" style="background:#991b1b;"></span> HTTP 403
</div>
@endsection

@section('code', '403')
@section('title', 'Acceso denegado')
@section('grad-from', '#7f1d1d')
@section('grad-to',   '#dc2626')

@section('description')
    @if(!empty($exception) && $exception->getMessage())
        {{ $exception->getMessage() }}
    @else
        No tienes los permisos necesarios para realizar esta acción.
        Si crees que esto es un error, contacta con el administrador del sistema.
    @endif
@endsection

@section('extra')
<div style="margin: 0 0 1.75rem; display:flex; align-items:center; justify-content:center; gap:0.5rem; font-size:0.82rem; color:#6c757d;">
    <svg width="14" height="14" fill="none" stroke="#adb5bd" stroke-width="2" viewBox="0 0 24 24">
        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
    </svg>
    Sesión activa como: <strong style="color:#343a40;">{{ auth()->user()->name ?? 'Invitado' }}</strong>
    &nbsp;·&nbsp; Rol: <strong style="color:#343a40;">{{ auth()->user()?->getRoleNames()->first() ?? '—' }}</strong>
</div>
@endsection

@section('actions')
    <a href="javascript:history.back()" class="btn-primary-erp">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Volver
    </a>
    <a href="{{ url('/') }}" class="btn-secondary-erp">Ir al inicio</a>
@endsection

@extends('errors.layout')

@section('badge')
<div class="error-code-badge" style="background:#fef9c3;color:#713f12;border-color:#fde68a;">
    <span class="dot" style="background:#713f12;"></span> HTTP 401
</div>
@endsection

@section('code', '401')
@section('title', 'No autenticado')
@section('grad-from', '#92400e')
@section('grad-to',   '#d97706')

@section('description')
    Debes iniciar sesión para acceder a este recurso. Tu sesión puede haber expirado o las credenciales ingresadas son inválidas.
@endsection

@section('actions')
    <a href="{{ route('login') }}" class="btn-primary-erp">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
        Iniciar sesión
    </a>
    <a href="{{ url('/') }}" class="btn-secondary-erp">Ir al inicio</a>
@endsection

@extends('errors.layout')

@section('badge')
<div class="error-code-badge" style="background:#fff3cd;color:#664d03;border-color:#ffecb5;">
    <span class="dot" style="background:#664d03;"></span> HTTP 400
</div>
@endsection

@section('code', '400')
@section('title', 'Solicitud incorrecta')
@section('grad-from', '#b45309')
@section('grad-to',   '#f59e0b')

@section('description')
    La solicitud que enviaste no pudo ser procesada porque contiene datos incorrectos o mal formados.
    Verifica los campos del formulario e intenta de nuevo.
@endsection

@section('actions')
    <a href="javascript:history.back()" class="btn-primary-erp">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Volver
    </a>
    <a href="{{ url('/') }}" class="btn-secondary-erp">Ir al inicio</a>
@endsection

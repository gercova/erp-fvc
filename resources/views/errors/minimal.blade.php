@extends('errors.layout')

@section('badge')
<div class="error-code-badge" style="background:#f8f9fa;color:#495057;border-color:#dee2e6;">
    <span class="dot" style="background:#495057;"></span> HTTP {{ $exception->getStatusCode() ?? '???' }}
</div>
@endsection

@section('code') {{ $exception->getStatusCode() ?? '???' }} @endsection
@section('title') {{ $exception->getMessage() ?: 'Error del servidor' }} @endsection
@section('grad-from', '#1e3a5f')
@section('grad-to',   '#0d6efd')

@section('description')
    Ha ocurrido un error inesperado. Si el problema persiste, contacta con el administrador del sistema.
@endsection

@section('actions')
    <a href="{{ url('/') }}" class="btn-primary-erp">Ir al inicio</a>
    <a href="javascript:history.back()" class="btn-secondary-erp">Volver</a>
@endsection

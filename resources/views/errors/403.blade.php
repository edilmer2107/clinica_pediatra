@extends('errors.layout')
@section('codigo', '403')
@section('titulo', 'Acceso denegado')
@section('mensaje', $exception->getMessage() && ! str_contains($exception->getMessage(), 'User does not have') ? $exception->getMessage() : 'No tiene permisos para acceder a este recurso.')

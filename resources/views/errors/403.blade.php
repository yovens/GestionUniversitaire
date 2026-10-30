@extends('errors.layout')
@section('code', '403')
@section('title', 'Accès refusé')
@section('message'){{ $exception->getMessage() ?: "Votre profil ne permet pas d'ouvrir cette page." }}@endsection

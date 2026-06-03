@extends('layouts.app')

@section('title', 'Nouveau role | SFMID Gestion')
@section('subtitle', 'Acces et securite')
@section('page-title', 'Nouveau role')

@section('content')
    <form method="POST" action="{{ route('roles.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @include('roles._form')
    </form>
@endsection

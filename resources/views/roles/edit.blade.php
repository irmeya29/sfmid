@extends('layouts.app')

@section('title', 'Modifier role | SFMID Gestion')
@section('subtitle', 'Acces et securite')
@section('page-title', 'Modifier role')

@section('content')
    <form method="POST" action="{{ route('roles.update', $role) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @method('PUT')
        @include('roles._form')
    </form>
@endsection

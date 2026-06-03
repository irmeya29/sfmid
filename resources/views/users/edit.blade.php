@extends('layouts.app')

@section('title', 'Modifier utilisateur | SFMID Gestion')
@section('subtitle', 'Acces et securite')
@section('page-title', 'Modifier utilisateur')

@section('content')
    <form method="POST" action="{{ route('users.update', $user) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @method('PUT')
        @include('users._form')
    </form>
@endsection

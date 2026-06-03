@extends('layouts.app')

@section('title', 'Nouvel utilisateur | SFMID Gestion')
@section('subtitle', 'Acces et securite')
@section('page-title', 'Nouvel utilisateur')

@section('content')
    <form method="POST" action="{{ route('users.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @include('users._form')
    </form>
@endsection

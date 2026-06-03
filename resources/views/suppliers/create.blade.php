@extends('layouts.app')
@section('title','Nouveau fournisseur | SFMID')
@section('subtitle','Achats')
@section('page-title','Nouveau fournisseur')
@section('content')
<form method="POST" action="{{ route('suppliers.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@include('suppliers._form')</form>
@endsection

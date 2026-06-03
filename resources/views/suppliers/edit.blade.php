@extends('layouts.app')
@section('title','Modifier fournisseur | SFMID')
@section('subtitle','Achats')
@section('page-title','Modifier fournisseur')
@section('content')
<form method="POST" action="{{ route('suppliers.update',$supplier) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@method('PUT')@include('suppliers._form')</form>
@endsection

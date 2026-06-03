@extends('layouts.app')
@section('title','Modifier catégorie charge | SFMID')
@section('subtitle','Charges')
@section('page-title','Modifier catégorie de charge')
@section('content')
<form method="POST" action="{{ route('expense-categories.update',$category) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@method('PUT')@include('expense-categories._form')</form>
@endsection

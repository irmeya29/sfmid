@extends('layouts.app')
@section('title','Nouvelle catégorie charge | SFMID')
@section('subtitle','Charges')
@section('page-title','Nouvelle catégorie de charge')
@section('content')
<form method="POST" action="{{ route('expense-categories.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@include('expense-categories._form')</form>
@endsection

@extends('layouts.app')
@section('title','Demande achat | SFMID')
@section('subtitle','Achats')
@section('page-title',"Demande d'achat")
@section('content')
<form method="POST" action="{{ route('purchases.requests.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@csrf
<div class="grid gap-4 lg:grid-cols-3"><select name="supplier_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm"><option value="">Fournisseur eventuel</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select><input type="date" name="request_date" value="{{ now()->toDateString() }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm"></div><textarea name="notes" rows="5" placeholder="Besoin d'achat, produits ou observations" class="mt-4 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></textarea><div class="mt-4 flex justify-end"><x-button type="submit" icon="save">Creer la demande</x-button></div></form>
@endsection

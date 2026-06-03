@extends('layouts.app')
@section('title','Catégorie charge | SFMID')
@section('subtitle','Charges')
@section('page-title',$category->name)
@section('content')
<div class="mb-6 flex justify-end gap-3"><x-button :href="route('expense-categories.edit',$category)" tone="secondary" icon="pencil">Modifier</x-button>@if(!$category->expenses()->exists())<form method="POST" action="{{ route('expense-categories.destroy',$category) }}">@csrf @method('DELETE')<x-button type="submit" tone="danger" icon="trash-2">Supprimer</x-button></form>@endif</div>
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">{{ $category->description ?: 'Aucune description.' }}</p><div class="mt-4 flex gap-2">@if($category->is_sensitive)<span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">Sensible</span>@endif<span class="rounded-full px-3 py-1 text-xs font-bold {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-700' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></div></section>
<section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h3 class="font-bold">Dépenses liées</h3><table class="mt-4 min-w-full text-sm"><tbody>@forelse($category->expenses as $expense)<tr class="border-b"><td class="py-3 font-bold">{{ $expense->number }}</td><td>{{ $expense->expense_date?->format('d/m/Y') }}</td><td>{{ $expense->beneficiary ?: '-' }}</td><td class="text-right">{{ number_format((float)$expense->amount,0,',',' ') }} FCFA</td></tr>@empty<tr><td class="py-6 text-center text-slate-500">Aucune dépense liée.</td></tr>@endforelse</tbody></table></section>
@endsection

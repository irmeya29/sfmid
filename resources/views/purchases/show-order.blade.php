@extends('layouts.app')
@section('title','BC fournisseur | SFMID')
@section('subtitle','Achats')
@section('page-title',$order->number)
@section('content')
<div class="mb-6 flex justify-between"><div><p class="font-bold">{{ $order->supplier?->name }}</p><p class="text-sm text-slate-500">{{ $order->order_date?->format('d/m/Y') }} - {{ $order->status }}</p></div><div class="flex gap-3"><a href="{{ route('purchases.orders.pdf',$order) }}" target="_blank" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold">PDF</a><a href="{{ route('purchases.invoices.create',['order_id'=>$order->id]) }}" class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white">Facture fournisseur</a></div></div>
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><table class="min-w-full text-sm"><thead><tr class="border-b"><th class="py-3 text-left">Produit</th><th class="text-right">Qté</th><th class="text-right">PU</th><th class="text-right">Total</th></tr></thead><tbody>@foreach($order->items as $item)<tr class="border-b"><td class="py-3">{{ $item->product_code }} - {{ $item->product_name }}</td><td class="text-right">{{ \App\Support\NumberFormatter::quantity($item->quantity) }}</td><td class="text-right">{{ number_format((float)$item->unit_price,0,',',' ') }}</td><td class="text-right font-bold">{{ number_format((float)$item->line_total,0,',',' ') }} FCFA</td></tr>@endforeach</tbody></table><div class="mt-4 text-right text-xl font-bold">{{ number_format((float)$order->total,0,',',' ') }} FCFA</div></section>
@endsection

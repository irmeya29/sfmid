<?php

namespace App\Http\Controllers;

use App\Actions\Stock\MarkDeliveryNoteAsDeliveredAction;
use App\Actions\Stock\MarkDeliveryNoteAsPreparedAction;
use App\Actions\Validation\RejectDeliveryNoteAction;
use App\Actions\Validation\SubmitDeliveryNoteAction;
use App\Actions\Validation\ValidateDeliveryNoteAction;
use App\Http\Requests\MarkDeliveryNoteDeliveredRequest;
use App\Http\Requests\RejectDocumentRequest;
use App\Models\DeliveryNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use RuntimeException;

class DeliveryNoteValidationController extends Controller
{
    public function submit(DeliveryNote $deliveryNote, Request $request, SubmitDeliveryNoteAction $action): RedirectResponse
    {
        $action->execute($deliveryNote, $request->user());

        return redirect()
            ->route('delivery-notes.show', $deliveryNote)
            ->with('success', 'BL soumis pour validation.');
    }

    public function validate(DeliveryNote $deliveryNote, Request $request, ValidateDeliveryNoteAction $action): RedirectResponse
    {
        $action->execute($deliveryNote, $request->user());

        return redirect()
            ->route('delivery-notes.show', $deliveryNote)
            ->with('success', 'BL validé avec succès.');
    }

    public function reject(DeliveryNote $deliveryNote, RejectDocumentRequest $request, RejectDeliveryNoteAction $action): RedirectResponse
    {
        $action->execute(
            deliveryNote: $deliveryNote,
            user: $request->user(),
            reason: $request->validated('reason'),
        );

        return redirect()
            ->route('delivery-notes.show', $deliveryNote)
            ->with('success', 'BL rejeté avec motif.');
    }

    public function markPrepared(DeliveryNote $deliveryNote, Request $request, MarkDeliveryNoteAsPreparedAction $action): RedirectResponse
    {
        $action->execute($deliveryNote, $request->user());

        return redirect()
            ->route('delivery-notes.show', $deliveryNote)
            ->with('success', 'BL marqué comme préparé.');
    }

    public function markDelivered(
        DeliveryNote $deliveryNote,
        MarkDeliveryNoteDeliveredRequest $request,
        MarkDeliveryNoteAsDeliveredAction $action,
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $action->execute(
                deliveryNote: $deliveryNote,
                user: $request->user(),
                receiverName: $validated['receiver_name'],
                receiverPhone: $validated['receiver_phone'] ?? null,
                deliveredAt: Carbon::parse($validated['delivered_at']),
                deliveryAddress: $validated['delivery_address'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        }

        return redirect()
            ->route('delivery-notes.show', $deliveryNote)
            ->with('success', 'BL livré. Stock physique transféré vers stock en suspens.');
    }
}

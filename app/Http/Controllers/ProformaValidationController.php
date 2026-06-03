<?php

namespace App\Http\Controllers;

use App\Actions\Documents\ConvertProformaToDeliveryNoteAction;
use App\Actions\Validation\RejectProformaAction;
use App\Actions\Validation\SubmitProformaAction;
use App\Actions\Validation\ValidateProformaAction;
use App\Http\Requests\RejectDocumentRequest;
use App\Models\Proforma;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProformaValidationController extends Controller
{
    public function submit(Proforma $proforma, Request $request, SubmitProformaAction $action): RedirectResponse
    {
        $action->execute($proforma, $request->user());

        return redirect()
            ->route('proformas.show', $proforma)
            ->with('success', 'Proforma soumise pour validation.');
    }

    public function validate(Proforma $proforma, Request $request, ValidateProformaAction $action): RedirectResponse
    {
        $action->execute($proforma, $request->user());

        return redirect()
            ->route('proformas.show', $proforma)
            ->with('success', 'Proforma validée avec succès.');
    }

    public function reject(Proforma $proforma, RejectDocumentRequest $request, RejectProformaAction $action): RedirectResponse
    {
        $action->execute(
            proforma: $proforma,
            user: $request->user(),
            reason: $request->validated('reason'),
        );

        return redirect()
            ->route('proformas.show', $proforma)
            ->with('success', 'Proforma rejetée avec motif.');
    }

    public function convertToDeliveryNote(Proforma $proforma, Request $request, ConvertProformaToDeliveryNoteAction $action): RedirectResponse
    {
        $deliveryNote = $action->execute($proforma, $request->user());

        return redirect()
            ->route('proformas.show', $proforma)
            ->with('success', "BL {$deliveryNote->number} créé avec succès.");
    }
}

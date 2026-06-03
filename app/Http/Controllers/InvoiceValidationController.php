<?php

namespace App\Http\Controllers;

use App\Actions\Validation\RejectInvoiceAction;
use App\Actions\Validation\SubmitInvoiceAction;
use App\Actions\Validation\ValidateInvoiceAction;
use App\Http\Requests\RejectDocumentRequest;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoiceValidationController extends Controller
{
    public function submit(Invoice $invoice, Request $request, SubmitInvoiceAction $action): RedirectResponse
    {
        $action->execute($invoice, $request->user());

        return redirect()->route('invoices.show', $invoice)->with('success', 'Facture soumise pour validation.');
    }

    public function validate(Invoice $invoice, Request $request, ValidateInvoiceAction $action): RedirectResponse
    {
        $action->execute($invoice, $request->user());

        return redirect()->route('invoices.show', $invoice)->with('success', 'Facture validée.');
    }

    public function reject(Invoice $invoice, RejectDocumentRequest $request, RejectInvoiceAction $action): RedirectResponse
    {
        $action->execute($invoice, $request->user(), $request->validated('reason'));

        return redirect()->route('invoices.show', $invoice)->with('success', 'Facture rejetée avec motif.');
    }
}

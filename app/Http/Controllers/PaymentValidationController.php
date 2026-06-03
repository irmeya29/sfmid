<?php

namespace App\Http\Controllers;

use App\Actions\Payments\RejectPaymentAction;
use App\Actions\Payments\SubmitPaymentAction;
use App\Actions\Payments\ValidatePaymentAction;
use App\Http\Requests\RejectDocumentRequest;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentValidationController extends Controller
{
    public function submit(Payment $payment, Request $request, SubmitPaymentAction $action): RedirectResponse
    {
        $action->execute($payment, $request->user());

        return redirect()->route('payments.show', $payment)->with('success', 'Paiement soumis pour validation.');
    }

    public function validate(Payment $payment, Request $request, ValidatePaymentAction $action): RedirectResponse
    {
        $action->execute($payment, $request->user());

        return redirect()->route('payments.show', $payment)->with('success', 'Paiement validé.');
    }

    public function reject(Payment $payment, RejectDocumentRequest $request, RejectPaymentAction $action): RedirectResponse
    {
        $action->execute($payment, $request->user(), $request->validated('reason'));

        return redirect()->route('payments.show', $payment)->with('success', 'Paiement rejeté avec motif.');
    }
}

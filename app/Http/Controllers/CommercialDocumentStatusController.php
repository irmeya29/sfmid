<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryNoteStatus;
use App\Enums\DocumentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ValidationAction;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\Proforma;
use App\Services\Audit\ActivityLogger;
use App\Services\Stock\DirectInvoiceStockMover;
use App\Services\Stock\SuspenseStockCloser;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CommercialDocumentStatusController extends Controller
{
    public function updateProforma(Request $request, Proforma $proforma, ActivityLogger $activityLogger, ValidationHistoryLogger $validationHistoryLogger): RedirectResponse
    {
        $this->authorizeSensitiveStatusChange($request);

        $data = $request->validate([
            'status' => ['required', Rule::enum(DocumentStatus::class)],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $status = DocumentStatus::from($data['status']);

        DB::transaction(function () use ($request, $proforma, $status, $data, $activityLogger, $validationHistoryLogger): void {
            $fromStatus = $proforma->status;

            $proforma->forceFill([
                'status' => $status,
                'submitted_at' => $status === DocumentStatus::PendingValidation ? now() : $proforma->submitted_at,
                'validated_by' => $status === DocumentStatus::Validated ? $request->user()->id : $proforma->validated_by,
                'validated_at' => $status === DocumentStatus::Validated ? now() : $proforma->validated_at,
                'rejected_by' => $status === DocumentStatus::Rejected ? $request->user()->id : $proforma->rejected_by,
                'rejected_at' => $status === DocumentStatus::Rejected ? now() : $proforma->rejected_at,
                'rejection_reason' => $status === DocumentStatus::Rejected ? $data['reason'] : $proforma->rejection_reason,
                'cancelled_by' => $status === DocumentStatus::Cancelled ? $request->user()->id : $proforma->cancelled_by,
                'cancelled_at' => $status === DocumentStatus::Cancelled ? now() : $proforma->cancelled_at,
                'cancellation_reason' => $status === DocumentStatus::Cancelled ? $data['reason'] : $proforma->cancellation_reason,
            ])->save();

            $this->logStatusChange($validationHistoryLogger, $activityLogger, $proforma, 'proformas', $fromStatus->value, $status->value, $data['reason']);
        });

        return back()->with('success', 'Statut de la proforma modifie.');
    }

    public function updateDeliveryNote(Request $request, DeliveryNote $deliveryNote, ActivityLogger $activityLogger, ValidationHistoryLogger $validationHistoryLogger): RedirectResponse
    {
        $this->authorizeSensitiveStatusChange($request);

        $data = $request->validate([
            'status' => ['required', Rule::enum(DeliveryNoteStatus::class)],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $status = DeliveryNoteStatus::from($data['status']);

        DB::transaction(function () use ($request, $deliveryNote, $status, $data, $activityLogger, $validationHistoryLogger): void {
            $fromStatus = $deliveryNote->status;

            $deliveryNote->forceFill([
                'status' => $status,
                'submitted_at' => $status === DeliveryNoteStatus::PendingValidation ? now() : $deliveryNote->submitted_at,
                'validated_by' => $status === DeliveryNoteStatus::Validated ? $request->user()->id : $deliveryNote->validated_by,
                'validated_at' => $status === DeliveryNoteStatus::Validated ? now() : $deliveryNote->validated_at,
                'rejected_by' => $status === DeliveryNoteStatus::Rejected ? $request->user()->id : $deliveryNote->rejected_by,
                'rejected_at' => $status === DeliveryNoteStatus::Rejected ? now() : $deliveryNote->rejected_at,
                'rejection_reason' => $status === DeliveryNoteStatus::Rejected ? $data['reason'] : $deliveryNote->rejection_reason,
                'cancelled_by' => $status === DeliveryNoteStatus::Cancelled ? $request->user()->id : $deliveryNote->cancelled_by,
                'cancelled_at' => $status === DeliveryNoteStatus::Cancelled ? now() : $deliveryNote->cancelled_at,
                'cancellation_reason' => $status === DeliveryNoteStatus::Cancelled ? $data['reason'] : $deliveryNote->cancellation_reason,
            ])->save();

            $this->logStatusChange($validationHistoryLogger, $activityLogger, $deliveryNote, 'delivery_notes', $fromStatus->value, $status->value, $data['reason']);
        });

        return back()->with('success', 'Statut du BL modifie.');
    }

    public function updateInvoice(Request $request, Invoice $invoice, ActivityLogger $activityLogger, ValidationHistoryLogger $validationHistoryLogger, SuspenseStockCloser $suspenseStockCloser, DirectInvoiceStockMover $directInvoiceStockMover): RedirectResponse
    {
        $this->authorizeSensitiveStatusChange($request);

        $data = $request->validate([
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $status = InvoiceStatus::from($data['status']);

        DB::transaction(function () use ($request, $invoice, $status, $data, $activityLogger, $validationHistoryLogger, $suspenseStockCloser, $directInvoiceStockMover): void {
            $fromStatus = $invoice->status;

            $invoice->forceFill([
                'status' => $status,
                'submitted_at' => $status === InvoiceStatus::PendingValidation ? now() : $invoice->submitted_at,
                'validated_by' => in_array($status, [InvoiceStatus::Validated, InvoiceStatus::Unpaid], true) ? $request->user()->id : $invoice->validated_by,
                'validated_at' => in_array($status, [InvoiceStatus::Validated, InvoiceStatus::Unpaid], true) ? now() : $invoice->validated_at,
                'rejected_by' => $status === InvoiceStatus::Rejected ? $request->user()->id : $invoice->rejected_by,
                'rejected_at' => $status === InvoiceStatus::Rejected ? now() : $invoice->rejected_at,
                'rejection_reason' => $status === InvoiceStatus::Rejected ? $data['reason'] : $invoice->rejection_reason,
                'cancelled_by' => $status === InvoiceStatus::Cancelled ? $request->user()->id : $invoice->cancelled_by,
                'cancelled_at' => $status === InvoiceStatus::Cancelled ? now() : $invoice->cancelled_at,
                'cancellation_reason' => $status === InvoiceStatus::Cancelled ? $data['reason'] : $invoice->cancellation_reason,
            ])->save();

            if (in_array($status, [InvoiceStatus::Validated, InvoiceStatus::Unpaid, InvoiceStatus::PartiallyPaid, InvoiceStatus::Paid], true)) {
                $directInvoiceStockMover->moveForValidatedInvoice($invoice, $request->user());

                $suspenseStockCloser->closeForInvoice(
                    $invoice,
                    $request->user(),
                    "Facture {$invoice->number} validee par changement manuel de statut."
                );
            }

            $this->logStatusChange($validationHistoryLogger, $activityLogger, $invoice, 'invoices', $fromStatus->value, $status->value, $data['reason']);
        });

        return back()->with('success', 'Statut de la facture modifie.');
    }

    private function authorizeSensitiveStatusChange(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('sensitive.update_validated_document'), 403);
    }

    private function logStatusChange(ValidationHistoryLogger $validationHistoryLogger, ActivityLogger $activityLogger, Model $document, string $module, string $fromStatus, string $toStatus, string $reason): void
    {
        $validationHistoryLogger->log(
            document: $document,
            action: ValidationAction::Correct,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            reason: $reason,
            comment: 'Changement manuel de statut par administrateur.'
        );

        $activityLogger->log(
            action: 'manual_status_change',
            module: $module,
            description: 'Changement manuel de statut.',
            subject: $document,
            oldValues: ['status' => $fromStatus],
            newValues: ['status' => $toStatus, 'reason' => $reason],
        );
    }
}

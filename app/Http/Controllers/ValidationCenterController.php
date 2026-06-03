<?php

namespace App\Http\Controllers;

use App\Actions\Expenses\RejectExpenseAction;
use App\Actions\Expenses\ValidateExpenseAction;
use App\Actions\Payments\RejectPaymentAction;
use App\Actions\Payments\ValidatePaymentAction;
use App\Actions\Stock\SaveStockMovementAction;
use App\Actions\Validation\RejectDeliveryNoteAction;
use App\Actions\Validation\RejectInvoiceAction;
use App\Actions\Validation\RejectProformaAction;
use App\Actions\Validation\ValidateDeliveryNoteAction;
use App\Actions\Validation\ValidateInvoiceAction;
use App\Actions\Validation\ValidateProformaAction;
use App\Enums\DeliveryNoteStatus;
use App\Enums\DocumentStatus;
use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementStatus;
use App\Enums\StockMovementType;
use App\Enums\ValidationAction;
use App\Http\Requests\RejectDocumentRequest;
use App\Models\DeliveryNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Proforma;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\ValidationHistory;
use App\Services\Audit\ActivityLogger;
use App\Services\Validation\ValidationHistoryLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Throwable;

class ValidationCenterController extends Controller
{
    private const TYPES = [
        'proforma' => Proforma::class,
        'delivery_note' => DeliveryNote::class,
        'invoice' => Invoice::class,
        'payment' => Payment::class,
        'expense' => Expense::class,
        'stock_movement' => StockMovement::class,
    ];

    public function index(Request $request): View
    {
        $items = $this->pendingItems($request);

        return view('validations.index', [
            'items' => $this->paginate($items, $request),
            'history' => $this->historyQuery($request)->limit(15)->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'types' => $this->typeLabels(),
            'filters' => $this->filters($request),
        ]);
    }

    public function history(Request $request): View
    {
        return view('validations.history', [
            'histories' => $this->historyQuery($request)->paginate(25)->withQueryString(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'types' => $this->typeLabels(),
            'filters' => $this->filters($request),
        ]);
    }

    public function validateItem(
        string $type,
        int $id,
        Request $request,
        ValidateProformaAction $validateProforma,
        ValidateDeliveryNoteAction $validateDeliveryNote,
        ValidateInvoiceAction $validateInvoice,
        ValidatePaymentAction $validatePayment,
        ValidateExpenseAction $validateExpense,
        SaveStockMovementAction $saveStockMovement,
    ): RedirectResponse {
        $document = $this->findDocument($type, $id);

        try {
            match ($type) {
                'proforma' => $validateProforma->execute($document, $request->user()),
                'delivery_note' => $validateDeliveryNote->execute($document, $request->user()),
                'invoice' => $validateInvoice->execute($document, $request->user()),
                'payment' => $validatePayment->execute($document, $request->user()),
                'expense' => $validateExpense->execute($document, $request->user()),
                'stock_movement' => $saveStockMovement->applyPending($document, $request->user()),
                default => abort(404),
            };
        } catch (Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Element valide avec succes.');
    }

    public function rejectItem(
        string $type,
        int $id,
        RejectDocumentRequest $request,
        RejectProformaAction $rejectProforma,
        RejectDeliveryNoteAction $rejectDeliveryNote,
        RejectInvoiceAction $rejectInvoice,
        RejectPaymentAction $rejectPayment,
        RejectExpenseAction $rejectExpense,
        ValidationHistoryLogger $historyLogger,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $document = $this->findDocument($type, $id);
        $reason = $request->validated('reason');

        try {
            match ($type) {
                'proforma' => $rejectProforma->execute($document, $request->user(), $reason),
                'delivery_note' => $rejectDeliveryNote->execute($document, $request->user(), $reason),
                'invoice' => $rejectInvoice->execute($document, $request->user(), $reason),
                'payment' => $rejectPayment->execute($document, $request->user(), $reason),
                'expense' => $rejectExpense->execute($document, $request->user(), $reason),
                'stock_movement' => $this->rejectStockMovement($document, $request, $reason, $historyLogger, $activityLogger),
                default => abort(404),
            };
        } catch (Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Element rejete avec succes.');
    }

    private function rejectStockMovement(
        StockMovement $movement,
        Request $request,
        string $reason,
        ValidationHistoryLogger $historyLogger,
        ActivityLogger $activityLogger,
    ): StockMovement {
        Gate::authorize('validate', $movement);

        $fromStatus = $movement->status->value;

        $movement->forceFill([
            'status' => StockMovementStatus::Rejected,
            'reason' => trim($movement->reason."\n\nRejet: ".$reason),
        ])->save();

        $historyLogger->log(
            document: $movement,
            action: ValidationAction::Reject,
            fromStatus: $fromStatus,
            toStatus: StockMovementStatus::Rejected->value,
            reason: $reason,
        );

        $activityLogger->log(
            action: 'rejected',
            module: 'stock',
            description: "Mouvement stock #{$movement->id} rejete.",
            subject: $movement,
            oldValues: ['status' => $fromStatus],
            newValues: ['status' => StockMovementStatus::Rejected->value, 'reason' => $reason],
        );

        return $movement->refresh();
    }

    private function pendingItems(Request $request): Collection
    {
        $items = collect();
        $type = $request->string('type')->toString();

        foreach (self::TYPES as $key => $class) {
            if ($type !== '' && $type !== $key) {
                continue;
            }

            $items = $items->merge($this->pendingForType($key, $class, $request));
        }

        return $items->sortByDesc('submitted_at')->values();
    }

    private function pendingForType(string $type, string $class, Request $request): Collection
    {
        $status = match ($type) {
            'proforma' => DocumentStatus::PendingValidation->value,
            'delivery_note' => DeliveryNoteStatus::PendingValidation->value,
            'invoice' => InvoiceStatus::PendingValidation->value,
            'payment' => PaymentStatus::PendingValidation->value,
            'expense' => ExpenseStatus::PendingValidation->value,
            'stock_movement' => StockMovementStatus::PendingValidation->value,
        };

        $query = $class::query()
            ->with($this->relationsFor($type))
            ->where('status', $status)
            ->when($request->filled('creator_id'), fn ($query) => $query->where('created_by', $request->integer('creator_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate($this->dateColumn($type), '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate($this->dateColumn($type), '<=', $request->date('date_to')))
            ->latest($this->dateColumn($type));

        return $query->get()->map(fn (Model $document): array => $this->toItem($type, $document));
    }

    private function toItem(string $type, Model $document): array
    {
        $submittedAt = $document->submitted_at ?? $document->created_at;
        $isSensitive = $this->isSensitive($type, $document);

        return [
            'type' => $type,
            'type_label' => $this->typeLabels()[$type],
            'id' => $document->getKey(),
            'number' => $document->number ?? '#'.$document->getKey(),
            'title' => $this->titleFor($type, $document),
            'amount' => $this->amountFor($document),
            'creator' => $document->creator?->name,
            'submitted_at' => $submittedAt,
            'priority' => $this->priorityFor($submittedAt, $isSensitive),
            'sensitive' => $isSensitive,
            'show_route' => $this->showRoute($type, $document),
            'can_validate' => auth()->user()?->can('validate', $document) ?? false,
            'can_reject' => $type === 'stock_movement'
                ? (auth()->user()?->can('validate', $document) ?? false)
                : (auth()->user()?->can('reject', $document) ?? false),
        ];
    }

    private function historyQuery(Request $request)
    {
        return ValidationHistory::query()
            ->with('user')
            ->when($request->filled('type'), fn ($query) => $query->where('document_type', self::TYPES[$request->string('type')->toString()] ?? null))
            ->when($request->filled('creator_id'), fn ($query) => $query->where('user_id', $request->integer('creator_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest();
    }

    private function findDocument(string $type, int $id): Model
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type]::query()->findOrFail($id);
    }

    private function relationsFor(string $type): array
    {
        return match ($type) {
            'stock_movement' => ['product', 'creator'],
            'payment' => ['invoice.client', 'creator'],
            'expense' => ['category', 'creator'],
            default => ['client', 'creator'],
        };
    }

    private function dateColumn(string $type): string
    {
        return $type === 'stock_movement' ? 'created_at' : 'submitted_at';
    }

    private function titleFor(string $type, Model $document): string
    {
        return match ($type) {
            'stock_movement' => ($document->product?->code ?? '').' '.$document->type->label(),
            'payment' => 'Facture '.$document->invoice?->number,
            'expense' => $document->category?->name.' - '.$document->beneficiary,
            default => $document->client?->name ?? '',
        };
    }

    private function amountFor(Model $document): ?float
    {
        if (isset($document->total)) {
            return (float) $document->total;
        }

        if (isset($document->amount)) {
            return (float) $document->amount;
        }

        if (isset($document->quantity)) {
            return (float) $document->quantity;
        }

        return null;
    }

    private function isSensitive(string $type, Model $document): bool
    {
        return match ($type) {
            'expense' => (bool) $document->category?->is_sensitive,
            'stock_movement' => in_array($document->type, [
                StockMovementType::PositiveAdjustment,
                StockMovementType::NegativeAdjustment,
                StockMovementType::LossOrDamage,
            ], true),
            'payment' => true,
            default => false,
        };
    }

    private function priorityFor($submittedAt, bool $isSensitive): string
    {
        if ($isSensitive) {
            return 'Sensible';
        }

        if ($submittedAt && $submittedAt->lt(now()->subDays(2))) {
            return 'Urgent';
        }

        return 'Normal';
    }

    private function showRoute(string $type, Model $document): string
    {
        return match ($type) {
            'proforma' => route('proformas.show', $document),
            'delivery_note' => route('delivery-notes.show', $document),
            'invoice' => route('invoices.show', $document),
            'payment' => route('payments.show', $document),
            'expense' => route('expenses.show', $document),
            'stock_movement' => route('stock.movements', ['status' => StockMovementStatus::PendingValidation->value]),
        };
    }

    private function typeLabels(): array
    {
        return [
            'proforma' => 'Proforma',
            'delivery_note' => 'BL',
            'invoice' => 'Facture',
            'payment' => 'Paiement',
            'expense' => 'Depense',
            'stock_movement' => 'Mouvement stock',
        ];
    }

    private function filters(Request $request): array
    {
        return [
            'type' => $request->string('type')->toString(),
            'creator_id' => $request->integer('creator_id') ?: null,
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];
    }

    private function paginate(Collection $items, Request $request): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();
        $perPage = 15;

        return new Paginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}

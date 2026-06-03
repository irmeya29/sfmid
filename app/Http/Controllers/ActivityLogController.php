<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        return view('activity-logs.index', [
            'logs' => $this->query($request)->paginate(25)->withQueryString(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'modules' => ActivityLog::query()->whereNotNull('module')->distinct()->orderBy('module')->pluck('module'),
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
            'filters' => $this->filters($request),
        ]);
    }

    public function show(ActivityLog $activityLog): View
    {
        $activityLog->load('user');

        return view('activity-logs.show', compact('activityLog'));
    }

    public function csv(Request $request): StreamedResponse
    {
        $filename = 'journal-activite-'.now()->format('Ymd-His').'.csv';

        return ResponseFactory::streamDownload(function () use ($request): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Utilisateur', 'Module', 'Action', 'Description', 'Sujet', 'IP'], ';');

            $this->query($request)->chunk(200, function ($logs) use ($handle): void {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->created_at?->format('d/m/Y H:i:s'),
                        $log->user?->name,
                        $log->module,
                        $log->action,
                        $log->description,
                        trim(class_basename($log->subject_type).' #'.$log->subject_id),
                        $log->ip_address,
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Request $request): Response
    {
        return Pdf::loadView('activity-logs.pdf', [
            'logs' => $this->query($request)->limit(500)->get(),
            'filters' => $this->filters($request),
        ])->setPaper('a4', 'landscape')->stream('journal-activite.pdf');
    }

    private function query(Request $request)
    {
        return ActivityLog::query()
            ->with('user')
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('module'), fn ($query) => $query->where('module', $request->string('module')->toString()))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest();
    }

    private function filters(Request $request): array
    {
        return [
            'user_id' => $request->integer('user_id') ?: null,
            'module' => $request->string('module')->toString(),
            'action' => $request->string('action')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];
    }
}

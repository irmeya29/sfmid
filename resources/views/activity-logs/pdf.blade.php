<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; }
        h1 { font-size: 18px; margin-bottom: 12px; }
    </style>
</head>
<body>
    <h1>Journal d'activite</h1>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Module</th>
                <th>Action</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->user?->name ?: '-' }}</td>
                    <td>{{ $log->module ?: '-' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

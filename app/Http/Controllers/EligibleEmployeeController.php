<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportEligibleEmployeesRequest;
use App\Models\EligibleEmployee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EligibleEmployeeController extends Controller
{
    private const MAX_IMPORT_ROWS = 5000;

    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()->can('view eligible employees'),
            403
        );

        $search = trim((string) $request->query('search', ''));

        $employees = EligibleEmployee::query()
            ->when(
                $search !== '',
                fn ($query) => $query->where('nic', 'like', "%{$search}%")
            )
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('eligible-employees/index', [
            'employees' => $employees,
            'filters' => [
                'search' => $search,
            ],
            'canImport' => $request->user()->can('import eligible employees'),
            'canManage' => $request->user()->can('manage eligible employees'),
        ]);
    }

    public function template(Request $request): StreamedResponse
    {
        abort_unless(
            $request->user()->can('import eligible employees'),
            403
        );

        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');

            fputcsv($output, ['nic']);

            fclose($output);
        }, 'eligible_employee_nic_template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function import(
        ImportEligibleEmployeesRequest $request
    ): RedirectResponse {
        $file = $request->file('file');

        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'The file could not be read.',
            ]);
        }

        $nics = [];
        $seen = [];
        $errors = [];
        $rowNumber = 0;
        $dataRows = 0;

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                throw ValidationException::withMessages([
                    'file' => 'The CSV file is empty.',
                ]);
            }

            $firstColumn = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                (string) ($header[0] ?? '')
            );

            if (count($header) !== 1 || strtolower(trim($firstColumn)) !== 'nic') {
                throw ValidationException::withMessages([
                    'file' => 'The file must have one column with the header: nic.',
                ]);
            }

            $rowNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // Ignore completely empty lines.
                if (count($row) === 1 && trim((string) $row[0]) === '') {
                    continue;
                }

                $dataRows++;

                if ($dataRows > self::MAX_IMPORT_ROWS) {
                    $errors[] = 'The file exceeds the 5,000-row limit.';
                    break;
                }

                if (count($row) !== 1) {
                    $errors[] = "Row {$rowNumber}: expected one NIC column.";
                    continue;
                }

                $nic = strtoupper(trim((string) $row[0]));

                $validOldFormat = preg_match(
                    '/^[0-9]{9}[VX]$/',
                    $nic
                ) === 1;

                $validNewFormat = preg_match(
                    '/^[0-9]{12}$/',
                    $nic
                ) === 1;

                if (! $validOldFormat && ! $validNewFormat) {
                    $errors[] = "Row {$rowNumber}: invalid NIC format.";
                    continue;
                }

                if (isset($seen[$nic])) {
                    $errors[] = "Row {$rowNumber}: duplicate NIC in this file.";
                    continue;
                }

                $seen[$nic] = true;
                $nics[] = $nic;
            }
        } finally {
            fclose($handle);
        }

        if ($dataRows === 0) {
            $errors[] = 'The file does not contain any NIC numbers.';
        }

        if ($errors !== []) {
            $shownErrors = array_slice($errors, 0, 10);

            if (count($errors) > 10) {
                $shownErrors[] = 'More errors exist. Please review the file.';
            }

            throw ValidationException::withMessages([
                'file' => implode(' ', $shownErrors),
            ]);
        }

        $existing = [];

        foreach (array_chunk($nics, 500) as $chunk) {
            $existing = array_merge(
                $existing,
                EligibleEmployee::query()
                    ->whereIn('nic', $chunk)
                    ->pluck('nic')
                    ->all()
            );
        }

        if ($existing !== []) {
            throw ValidationException::withMessages([
                'file' => count($existing).' NIC(s) already exist in the system. Remove them from the file before importing.',
            ]);
        }

        $now = now();

        $records = array_map(
            fn (string $nic) => [
                'nic' => $nic,
                'is_active' => true,
                'imported_by' => $request->user()->id,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $nics
        );

        DB::transaction(function () use ($records) {
            foreach (array_chunk($records, 500) as $chunk) {
                EligibleEmployee::insert($chunk);
            }
        });

        return redirect()
            ->route('eligible-employees.index')
            ->with('success', count($records).' NIC(s) imported successfully.');
    }

    public function updateStatus(
        Request $request,
        EligibleEmployee $eligibleEmployee
    ): RedirectResponse {
        abort_unless(
            $request->user()->can('manage eligible employees'),
            403
        );

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $eligibleEmployee->update($data);

        return redirect()
            ->route('eligible-employees.index')
            ->with('success', 'Eligibility status updated successfully.');
    }
}

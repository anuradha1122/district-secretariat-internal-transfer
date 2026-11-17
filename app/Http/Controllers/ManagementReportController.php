<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Service;
use App\Models\TransferCycle;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagementReportController extends Controller
{
    private function authorizeView(Request $request): void
    {
        abort_unless(
            $request->user()?->can('view district reports'),
            403
        );
    }

    private function authorizeExport(Request $request): void
    {
        $this->authorizeView($request);

        abort_unless(
            $request->user()?->can('export reports'),
            403
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->validate([
            'report' => [
                'nullable',
                Rule::in(['applications', 'preferences']),
            ],

            'cycle_id' => [
                'nullable',
                'integer',
                Rule::exists('transfer_cycles', 'id'),
            ],

            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id'),
            ],

            'office_id' => [
                'nullable',
                'integer',
                Rule::exists('offices', 'id'),
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'submitted',
                    'office_recommended',
                    'approved',
                    'rejected',
                ]),
            ],
        ]);
    }

    private function reportType(array $filters): string
    {
        return $filters['report'] ?? 'applications';
    }

    private function reportTitle(string $report): string
    {
        return $report === 'preferences'
            ? 'Office-to-office preferences'
            : 'Service-wise applications';
    }

    private function reportQuery(array $filters): Builder
    {
        $report = $this->reportType($filters);

        $query = DB::table('transfer_applications as applications')
            ->leftJoin(
                'transfer_cycles as cycles',
                'cycles.id',
                '=',
                'applications.transfer_cycle_id'
            )
            ->leftJoin(
                'services',
                'services.id',
                '=',
                'applications.service_id'
            )
            ->leftJoin(
                'offices as current_offices',
                'current_offices.id',
                '=',
                'applications.current_office_id'
            )
            /*
             * Returned applications use draft status and are excluded
             * until they are submitted again.
             */
            ->whereIn('applications.status', [
                'submitted',
                'office_recommended',
                'approved',
                'rejected',
            ])
            ->when(
                ! empty($filters['cycle_id']),
                fn (Builder $query) => $query->where(
                    'applications.transfer_cycle_id',
                    $filters['cycle_id']
                )
            )
            ->when(
                ! empty($filters['service_id']),
                fn (Builder $query) => $query->where(
                    'applications.service_id',
                    $filters['service_id']
                )
            )
            ->when(
                ! empty($filters['office_id']),
                fn (Builder $query) => $query->where(
                    'applications.current_office_id',
                    $filters['office_id']
                )
            )
            ->when(
                ! empty($filters['status']),
                fn (Builder $query) => $query->where(
                    'applications.status',
                    $filters['status']
                )
            );

        if ($report === 'preferences') {
            $query
                ->join(
                    'transfer_application_preferences as preferences',
                    'preferences.transfer_application_id',
                    '=',
                    'applications.id'
                )
                ->leftJoin(
                    'offices as destination_offices',
                    'destination_offices.id',
                    '=',
                    'preferences.office_id'
                )
                ->select([
                    'applications.id as application_id',
                    'applications.reference_no',
                    'applications.full_name',
                    'applications.status',
                    'cycles.name as cycle_name',
                    'services.name as service_name',
                    'current_offices.name as current_office_name',
                    'destination_offices.name as destination_office_name',
                    'preferences.priority',
                ])
                ->orderBy('services.name')
                ->orderBy('current_offices.name')
                ->orderBy('applications.id')
                ->orderBy('preferences.priority');

            return $query;
        }

        return $query
            ->select([
                'applications.id as application_id',
                'applications.reference_no',
                'applications.full_name',
                'applications.post',
                'applications.grade',
                'applications.status',
                'applications.submitted_at',
                'cycles.name as cycle_name',
                'services.name as service_name',
                'current_offices.name as current_office_name',
            ])
            ->orderBy('services.name')
            ->orderBy('current_offices.name')
            ->orderBy('applications.id');
    }

    /**
     * @return array<int, string>
     */
    private function headings(string $report): array
    {
        if ($report === 'preferences') {
            return [
                'Reference',
                'Applicant',
                'Cycle',
                'Service',
                'Current office',
                'Preferred office',
                'Priority',
                'Status',
            ];
        }

        return [
            'Reference',
            'Applicant',
            'Cycle',
            'Service',
            'Current office',
            'Post',
            'Grade',
            'Status',
            'Submitted at',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function rowValues(object $row, string $report): array
    {
        if ($report === 'preferences') {
            return [
                $row->reference_no,
                $row->full_name,
                $row->cycle_name,
                $row->service_name,
                $row->current_office_name,
                $row->destination_office_name,
                $row->priority,
                $row->status,
            ];
        }

        return [
            $row->reference_no,
            $row->full_name,
            $row->cycle_name,
            $row->service_name,
            $row->current_office_name,
            $row->post,
            $row->grade,
            $row->status,
            $row->submitted_at,
        ];
    }

    /**
     * Prevent spreadsheet applications from interpreting untrusted
     * text as formulas when a CSV is opened.
     */
    private function csvCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        if (preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value)) {
            return "'".$value;
        }

        return $value;
    }

    public function index(Request $request): Response
    {
        $this->authorizeView($request);

        $filters = $this->filters($request);
        $report = $this->reportType($filters);

        return Inertia::render('reports/index', [
            'report' => $report,
            'title' => $this->reportTitle($report),

            'filters' => [
                'cycle_id' => isset($filters['cycle_id'])
                    ? (int) $filters['cycle_id']
                    : null,

                'service_id' => isset($filters['service_id'])
                    ? (int) $filters['service_id']
                    : null,

                'office_id' => isset($filters['office_id'])
                    ? (int) $filters['office_id']
                    : null,

                'status' => $filters['status'] ?? null,
            ],

            'cycles' => TransferCycle::query()
                ->orderByDesc('id')
                ->get(['id', 'name']),

            'services' => Service::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name']),

            'offices' => Office::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name']),

            'rows' => $this->reportQuery($filters)
                ->paginate(25)
                ->withQueryString(),

            'canExport' => $request->user()->can('export reports'),
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $this->authorizeExport($request);

        $filters = $this->filters($request);
        $report = $this->reportType($filters);

        $filename = $report === 'preferences'
            ? 'office-preferences.csv'
            : 'service-applications.csv';

        return response()->streamDownload(
            function () use ($filters, $report): void {
                $output = fopen('php://output', 'w');

                /*
                 * UTF-8 BOM helps Excel recognize Sinhala text.
                 */
                fwrite($output, "\xEF\xBB\xBF");

                fputcsv($output, $this->headings($report));

                foreach ($this->reportQuery($filters)->cursor() as $row) {
                    $values = array_map(
                        fn ($value) => $this->csvCell($value),
                        $this->rowValues($row, $report)
                    );

                    fputcsv($output, $values);
                }

                fclose($output);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    public function pdf(Request $request)
    {
        $this->authorizeExport($request);

        $filters = $this->filters($request);
        $report = $this->reportType($filters);

        /*
         * Keep the synchronous PDF manageable for this MVP.
         * CSV remains available for larger result sets.
         */
        $rows = $this->reportQuery($filters)
            ->limit(1001)
            ->get();

        if ($rows->count() > 1000) {
            return back()->withErrors([
                'report' => 'This PDF is limited to 1,000 rows. Please narrow the filters or export CSV.',
            ]);
        }

        $html = view('pdf.reports.management-report', [
            'title' => $this->reportTitle($report),
            'headings' => $this->headings($report),
            'rows' => $rows->map(
                fn ($row) => $this->rowValues($row, $report)
            ),
            'generatedAt' => now('Asia/Colombo')->format('Y-m-d H:i'),
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'tempDir' => storage_path('app'),
            'default_font' => 'kaputaunicode',
        ]);

        $mpdf->WriteHTML($html);

        $pdf = $mpdf->Output('', 'S');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="management-report.pdf"',
        ]);
    }
}
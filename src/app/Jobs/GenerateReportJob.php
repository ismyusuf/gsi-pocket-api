<?php

namespace App\Jobs;

use App\Models\Expense;
use App\Models\Income;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $pocketId,
        private string $userId,
        private string $type,
        private string $date,
        private string $filename,
    ) {}

    public function handle(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['No', 'ID', 'Amount', 'Notes', 'Date'];

        if ($this->type === 'INCOME') {
            $records = Income::where('pocket_id', $this->pocketId)
                ->where('user_id', $this->userId)
                ->whereDate('created_at', $this->date)
                ->orderBy('created_at')
                ->get();

            $sheet->setTitle('Income Report');
        } else {
            $records = Expense::where('pocket_id', $this->pocketId)
                ->where('user_id', $this->userId)
                ->whereDate('created_at', $this->date)
                ->orderBy('created_at')
                ->get();

            $sheet->setTitle('Expense Report');
        }

        // Write header row
        $sheet->fromArray($headers, null, 'A1');

        // Style header row
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

        // Write data rows
        foreach ($records as $index => $record) {
            $row = $index + 2;
            $sheet->fromArray([
                $index + 1,
                $record->id,
                $record->amount,
                $record->notes ?? '-',
                $record->created_at->format('Y-m-d H:i:s'),
            ], null, 'A' . $row);
        }

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Ensure reports directory exists
        $reportDir = storage_path('app/reports');
        if (! is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }

        $path = $reportDir . '/' . $this->filename . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }
}

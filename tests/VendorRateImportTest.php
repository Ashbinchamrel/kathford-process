<?php
require_once __DIR__.'/../vendor/autoload.php';

use App\Services\VendorRateImport;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

final class VendorRateImportTest extends TestCase
{
    private string $path;
    protected function setUp(): void
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $this->path = tempnam(sys_get_temp_dir(), 'rate-import-');
    }
    protected function tearDown(): void
    {
        unlink($this->path);
        restore_error_handler(); restore_exception_handler();
    }
    private function workbook(array $rows): void
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray([VendorRateImport::HEADERS, ...$rows]);
        (new Xlsx($book))->save($this->path);
        $book->disconnectWorksheets();
    }
    public function test_excel_dates_and_optional_fields(): void
    {
        $this->workbook([['Paper','ream',450,46274,46638,'A4','Yes']]);
        $rows = (new VendorRateImport())->read($this->path);
        $this->assertCount(1, $rows);
        $this->assertTrue($rows[0]['is_active']);
        $this->assertMatchesRegularExpression('/^2026-/', $rows[0]['valid_from']);
        $this->assertArrayNotHasKey('review_on', $rows[0]);
    }
    public function test_invalid_later_row_rejects_whole_file_with_row_number(): void
    {
        $this->workbook([
            ['Paper','ream',450,'2026-01-01','2026-12-31'],
            ['Pens','pcs',-1,'2026-12-31','2026-01-01'],
        ]);
        try {
            (new VendorRateImport())->read($this->path);
            $this->fail('Invalid file accepted');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Row 3:', implode(' ', $e->errors()['rates_file']));
        }
    }
    public function test_duplicate_rows_are_rejected(): void
    {
        $row = ['Paper','ream',450,'2026-01-01','2026-12-31'];
        $this->workbook([$row,$row]);
        $this->expectException(ValidationException::class);
        (new VendorRateImport())->read($this->path);
    }
    public function test_legacy_review_column_is_ignored(): void
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray([
            ['item_name','unit','unit_rate','valid_from','valid_until','review_on','specification','is_active'],
            ['Paper','ream',450,'2026-01-01','2026-12-31','2020-01-01','A4','Yes'],
        ]);
        (new Xlsx($book))->save($this->path);
        $book->disconnectWorksheets();
        $rows = (new VendorRateImport())->read($this->path);
        $this->assertArrayNotHasKey('review_on', $rows[0]);
        $this->assertSame('A4', $rows[0]['specification']);
    }
}

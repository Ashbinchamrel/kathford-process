<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Planning\Document;
use App\Services\Planning\Access;
use App\Services\Planning\Governance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function preview(Request $r, Document $document)
    {
        Access::document($document, $r->user());
        abort_if($document->type === 'strategy', 422, 'Use the Strategic Plan matrix editor.');
        abort_unless(Governance::canWrite($r->user(), $document->type), 403);
        abort_unless($r->user()->can('planning.'.$document->type.'_create') && in_array($document->status, ['draft', 'returned']), 403);
        $r->validate(['file' => 'required|file|mimes:xlsx,csv,txt|max:5120', 'sheet' => 'nullable|string|max:100']);
        $file = $r->file('file');
        $reader = IOFactory::createReaderForFile($file->path());
        $reader->setReadDataOnly(true);
        $book = $reader->load($file->path());
        $sheet = $r->filled('sheet') ? $book->getSheetByName($r->sheet) : $book->getSheet(0);
        abort_unless($sheet, 422, 'Sheet not found.');
        abort_if($sheet->getHighestDataRow() > 201 || Coordinate::columnIndexFromString($sheet->getHighestDataColumn()) > 26, 422, 'Use at most 200 rows and 26 columns.');
        $rows = $sheet->toArray(null, false, false, false);
        $headers = array_map(fn ($x) => Str::snake(trim((string) $x)), array_shift($rows) ?? []);
        if (! in_array('title', $headers)) {
            throw ValidationException::withMessages(['file' => 'Use the Planning template with a title column. Your reference workbooks need their header rows and columns mapped to this format first.']);
        }
        $clean = [];
        foreach ($rows as $n => $row) {
            if (! array_filter($row, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }$line = array_combine($headers, array_pad($row, count($headers), null));
            $title = trim((string) ($line['title'] ?? ''));
            if (! $title || strlen($title) > 200) {
                throw ValidationException::withMessages(['file' => 'Row '.($n + 2).': title is required and must fit 200 characters.']);
            }$clean[] = ['title' => $title, 'description' => mb_substr((string) ($line['description'] ?? ''), 0, 5000), 'definition_of_done' => mb_substr((string) ($line['definition_of_done'] ?? ''), 0, 5000), 'programme' => mb_substr((string) ($line['programme'] ?? ''), 0, 100), 'batch' => mb_substr((string) ($line['batch'] ?? ''), 0, 100)];
        }
        abort_if(! $clean, 422, 'No rows to import.');
        $hash = hash('sha256', hash_file('sha256', $file->path()).'|'.$sheet->getTitle());
        $id = (string) Str::uuid();
        $existing = DB::table('planning_imports')->where('created_by', $r->user()->id)->where('hash', $hash)->first();
        if ($existing) {
            abort_if($existing->document_id, 422, 'This file was already imported.');
            $id = $existing->id;
        } else {
            DB::table('planning_imports')->insert(['id' => $id, 'created_by' => $r->user()->id, 'filename' => $file->getClientOriginalName(), 'hash' => $hash, 'rows' => json_encode($clean), 'created_at' => now(), 'updated_at' => now()]);
        }

        return view('planning.import', ['document' => $document, 'rows' => $clean, 'importId' => $id]);
    }

    public function confirm(Request $r, Document $document)
    {
        Access::document($document, $r->user());
        abort_if($document->type === 'strategy', 422, 'Use the Strategic Plan matrix editor.');
        abort_unless(Governance::canWrite($r->user(), $document->type), 403);
        abort_unless($r->user()->can('planning.'.$document->type.'_create'), 403);
        $r->validate(['import_id' => 'required|uuid']);
        DB::transaction(function () use ($r, $document) {
            $d = Document::lockForUpdate()->findOrFail($document->id);
            abort_unless(in_array($d->status, ['draft', 'returned']), 422);
            $import = DB::table('planning_imports')->where('id', $r->import_id)->where('created_by', $r->user()->id)->lockForUpdate()->first();
            abort_unless($import && ! $import->document_id, 422, 'Import unavailable or already applied.');
            abort_if($d->items()->count() + count(json_decode($import->rows, true)) > 200, 422, 'A document can contain at most 200 items.');
            foreach (json_decode($import->rows, true) as $row) {
                $d->items()->create($row);
            }$d->increment('version');
            DB::table('planning_imports')->where('id', $import->id)->update(['document_id' => $d->id, 'updated_at' => now()]);
            AuditLog::record($r->user(), 'planning.imported', $d, $import->filename);
        });

        return redirect()->route('planning.edit', $document)->with('success', 'Rows added to draft. Review dates, owners and targets before submission.');
    }

    public function template()
    {
        return response("title,description,definition_of_done,programme,batch\n\"Canvas course setup\",\"Prepare course materials\",\"AGS uploaded and reviewed\",BCA,2083\n", 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="planning-template.csv"']);
    }
}

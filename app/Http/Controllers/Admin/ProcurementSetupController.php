<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{VendorRate,Vendor,ChecklistQuestion,AuditLog};
use Illuminate\Http\Request;

class ProcurementSetupController extends Controller {
    public function rateTemplate() {
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $book->getActiveSheet()->fromArray(\App\Services\VendorRateImport::HEADERS);
        foreach (range('A','G') as $column) $book->getActiveSheet()->getColumnDimension($column)->setWidth(22);
        $book->getActiveSheet()->getStyle('A1:G1')->getFont()->setBold(true);
        return response()->streamDownload(function () use ($book) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'approved-vendor-rates.xlsx', ['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
    public function importRates(Request $request, \App\Services\VendorRateImport $importer) {
        $request->validate(['vendor_id'=>'required|exists:vendors,id','rates_file'=>'required|file|mimes:xlsx,xls,csv|max:5120']);
        $vendor = Vendor::active()->findOrFail($request->input('vendor_id'));
        try {
            $rows = $importer->read($request->file('rates_file')->getRealPath());
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            throw \Illuminate\Validation\ValidationException::withMessages(['rates_file'=>'The spreadsheet could not be read. Please use the Excel template.']);
        }
        \Illuminate\Support\Facades\DB::transaction(function () use ($rows, $vendor, $request) {
            foreach ($rows as $data) {
                $identity = array_intersect_key($data, array_flip(['item_name','unit','valid_from','valid_until']));
                $rate = VendorRate::updateOrCreate(['vendor_id'=>$vendor->id] + $identity, $data + ['approved_by'=>$request->user()->id]);
                AuditLog::record($request->user(),'vendor_rate.imported',$rate,$rate->item_name);
            }
        });
        return back()->with('success', count($rows).' approved rates imported for '.$vendor->name.'.');
    }
    public function index() {
        return view('admin.setup.procurement',['preparers'=>\App\Models\User::active()->with('role','permissions')->orderBy('name')->get()->filter(fn($user)=>$user->can('rfq.view') && $user->can('rfq.create')),'rates'=>VendorRate::with('vendor')->orderByDesc('id')->get(),'vendors'=>Vendor::active()->orderBy('name')->get(),'questions'=>ChecklistQuestion::orderBy('fulfillment_type')->orderBy('sort_order')->get()]);
    }
    public function saveAssignment(Request $request) {
        $data=$request->validate(['rfq_preparer_user_id'=>['nullable','exists:users,id']]);
        if (!empty($data['rfq_preparer_user_id'])) {
            $user=\App\Models\User::active()->findOrFail($data['rfq_preparer_user_id']);
            abort_unless($user->can('rfq.view') && $user->can('rfq.create'),422,'The assigned preparer needs RFQ view and create permissions.');
        }
        \App\Models\Setting::set('rfq_preparer_user_id',$data['rfq_preparer_user_id']??'','string','workflows');
        AuditLog::record($request->user(),'settings.rfq_preparer_updated',null,'RFQ preparation assignment updated');
        return back()->with('success','Future approved activities will be assigned to the selected preparer.');
    }
    public function saveRate(Request $request, ?VendorRate $vendorRate=null) {
        $data=$request->validate(['vendor_id'=>['required','exists:vendors,id'],'item_name'=>['required','string','max:255'],'unit'=>['required','string','max:50'],'unit_rate'=>['required','numeric','min:0.01'],'valid_from'=>['required','date'],'valid_until'=>['required','date','after_or_equal:valid_from'],'specification'=>['nullable','string','max:3000'],'is_active'=>['nullable','boolean']]);
        $data['is_active']=$request->boolean('is_active');$data['approved_by']=$request->user()->id;
        $vendorRate ? $vendorRate->update($data) : $vendorRate=VendorRate::create($data);
        AuditLog::record($request->user(),'vendor_rate.approved',$vendorRate,$vendorRate->item_name);
        return back()->with('success','Approved vendor rate saved. Existing quotations retain their recorded rates.');
    }
    public function deleteRate(Request $request,VendorRate $vendorRate) { $vendorRate->delete();AuditLog::record($request->user(),'vendor_rate.removed',$vendorRate,$vendorRate->item_name);return back()->with('success','Vendor rate removed from future selections.'); }
    public function saveQuestion(Request $request,?ChecklistQuestion $question=null) {
        $data=$request->validate(['fulfillment_type'=>['required','in:goods,service'],'label'=>['required','string','max:500'],'sort_order'=>['required','integer','min:0','max:999'],'is_required'=>['nullable','boolean'],'is_active'=>['nullable','boolean']]);
        $data['is_required']=$request->boolean('is_required');$data['is_active']=$request->boolean('is_active');
        $question ? $question->update($data) : $question=ChecklistQuestion::create($data);
        AuditLog::record($request->user(),'checklist_question.saved',$question,$question->label);
        return back()->with('success','Question saved. Existing checklist snapshots are preserved.');
    }
    public function deleteQuestion(Request $request,ChecklistQuestion $question) { $question->delete();AuditLog::record($request->user(),'checklist_question.removed',$question,$question->label);return back()->with('success','Question removed from future checklists.'); }
}

<?php
namespace App\Http\Controllers;
use App\Models\{ActivityForm,Rfq,PurchaseOrder,Payment,PaymentAuthorisation,ProcurementChecklist};
use App\Support\{DashboardReports,RecordVisibility};
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DashboardController extends Controller {
    public function index() {
        $user=Auth::user();$available=DashboardReports::available($user);
        $selected=array_values(array_intersect($user->dashboard_widgets ?? array_keys($available),array_keys($available)));
        $reports=[];
        foreach($selected as $key) {
            if($key==='my_forms') {
                $query=ActivityForm::where('creator_id',$user->id)->latest();
            } elseif(in_array($key,['verification','approval'])) {
                $query=ActivityForm::forUser($user)->where('status',$key==='verification'?'pending_verification':'pending_approval')->with('approvalChain.verifiers','approvalChain.approvers')->latest();
                $service=app(ApprovalService::class);
                $records=$query->get()->filter(fn($form)=>$key==='verification'?$service->canVerify($form,$user):$service->canApprove($form,$user));
                $reports[$key]=['label'=>$available[$key][0],'count'=>$records->count(),'rows'=>$records->take(3)->map(fn($r)=>['title'=>$r->form_number.' · '.$r->activity_name,'status'=>$r->statusLabel(),'url'=>route('activity-forms.show',$r)])];
                continue;
            } else {
                $class=match($key){'rfqs'=>Rfq::class,'purchase_orders'=>PurchaseOrder::class,'payments'=>Payment::class,'payment_authorisations'=>PaymentAuthorisation::class,'checklists'=>ProcurementChecklist::class};
                $query=RecordVisibility::apply($class::query(),$user)->latest();
            }
            $count=(clone $query)->count();
            $rows=$query->limit(3)->get()->map(function($r) use($key) {
                [$title,$route]=match($key){
                    'my_forms'=>[$r->form_number.' · '.$r->activity_name,'activity-forms.show'],
                    'rfqs'=>[$r->rfq_number.' · '.$r->title,'rfq.show'],
                    'purchase_orders'=>[$r->po_number,'purchase-orders.show'],
                    'payments'=>[$r->payment_number.' · '.($r->activity_name??''),'payments.show'],
                    'payment_authorisations'=>[$r->authorisation_number,'payment-authorisations.show'],
                    'checklists'=>[$r->purchaseOrder?->po_number.' · '.$r->vendorBill?->bill_number,'checklists.show'],
                };
                return ['title'=>$title,'status'=>method_exists($r,'statusLabel')?$r->statusLabel():ucfirst(str_replace('_',' ',$r->status)),'url'=>route($route,$r)];
            });
            $reports[$key]=['label'=>$available[$key][0],'count'=>$count,'rows'=>$rows];
        }
        foreach ($reports as $key => &$report) {
            $report['action_required'] = in_array($key, ['verification', 'approval'], true);
            $report['module_url'] = route(match ($key) {
                'my_forms', 'verification', 'approval' => 'activity-forms.index',
                'rfqs' => 'rfq.index', 'purchase_orders' => 'purchase-orders.index',
                'payments' => 'payments.index', 'payment_authorisations' => 'payment-authorisations.index',
                'checklists' => 'checklists.index',
            }, match ($key) {
                'verification' => ['status'=>'pending_verification'],
                'approval' => ['status'=>'pending_approval'],
                default => [],
            });
        }
        unset($report);
        return view('dashboard',compact('available','selected','reports'));
    }
    public function saveWidgets(Request $request) {
        $allowed=array_keys(DashboardReports::available($request->user()));
        $data=$request->validate(['widgets'=>['nullable','array'],'widgets.*'=>['string','distinct',Rule::in($allowed)]]);
        $request->user()->update(['dashboard_widgets'=>$data['widgets']??[]]);
        return back()->with('success','Your dashboard reports were saved.');
    }
}

<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Models\BankTransaction; use App\Models\ReconciliationSession; use App\Services\BankReconciliationService; use App\Support\ApiResponse; use Illuminate\Http\Request;
class BankReconciliationController extends Controller
{
 public function __construct(private readonly BankReconciliationService $service){}
 public function accounts(Request $r){return ApiResponse::data($this->service->accounts($r->validate(['search'=>'nullable|string','per_page'=>'nullable|integer|min:1|max:100'])));}
 public function storeAccount(Request $r){return ApiResponse::data($this->service->createAccount($r->validate(['bank_name'=>'required|string|max:120','account_name'=>'required|string|max:120','account_number'=>'required|string|max:40','currency'=>'nullable|string|size:3','opening_balance'=>'nullable|numeric'])),status:201);}
 public function showAccount(string $id){return ApiResponse::data($this->service->account($id));}
 public function import(Request $r){$d=$r->validate(['bank_account_id'=>'required|string','file'=>'required|file','period_start'=>'required|date','period_end'=>'required|date|after_or_equal:period_start','opening_balance'=>'nullable|numeric','closing_balance'=>'nullable|numeric','date_column'=>'nullable|string','description_column'=>'nullable|string','debit_column'=>'nullable|string','credit_column'=>'nullable|string','balance_column'=>'nullable|string','reference_column'=>'nullable|string']);return ApiResponse::data($this->service->import($this->service->account($d['bank_account_id']),$r->file('file'),$d),status:201);}
 public function transactions(Request $r){return ApiResponse::data($this->service->transactions($r->validate(['status'=>'nullable|string','bank_account_id'=>'nullable|string','per_page'=>'nullable|integer|min:1|max:100'])));}
 public function sessions(Request $r){return ApiResponse::data($this->service->sessions($r->validate(['per_page'=>'nullable|integer|min:1|max:100'])));}
 public function storeSession(Request $r){return ApiResponse::data($this->service->createSession($r->validate(['bank_account_id'=>'required|string','period_start'=>'required|date','period_end'=>'required|date|after_or_equal:period_start','opening_balance'=>'nullable|numeric','closing_balance'=>'nullable|numeric'])),status:201);}
 public function autoMatch(string $id){return ApiResponse::data($this->service->autoMatch(ReconciliationSession::findOrFail($id)));}
 public function match(Request $r,string $id){return ApiResponse::data($this->service->match(BankTransaction::findOrFail($id),$r->validate(['reconciliation_transaction_id'=>'required|string','matched_amount'=>'nullable|numeric'])));}
 public function exclude(Request $r,string $id){return ApiResponse::data($this->service->exclude(BankTransaction::findOrFail($id),$r->validate(['reason'=>'required|string|max:255'])['reason']));}
 public function complete(string $id){return ApiResponse::data($this->service->complete(ReconciliationSession::findOrFail($id)));} public function close(string $id){return ApiResponse::data($this->service->close(ReconciliationSession::findOrFail($id)));}
}

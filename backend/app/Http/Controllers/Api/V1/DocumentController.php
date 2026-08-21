<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller; use App\Models\Document; use App\Services\DocumentService; use App\Support\ApiResponse; use Illuminate\Http\Request; use Illuminate\Support\Facades\Storage;
class DocumentController extends Controller
{
 public function __construct(private readonly DocumentService $service){}
 public function index(Request $r){return ApiResponse::data($this->service->list($r->validate(['search'=>'nullable|string','status'=>'nullable|string','document_type'=>'nullable|string','per_page'=>'nullable|integer|min:1|max:100'])));}
 public function store(Request $r){$d=$r->validate(['document_name'=>'required|string|max:255','document_type'=>'required|string|max:30','category'=>'nullable|string|max:50','visibility'=>'nullable|in:PRIVATE,INTERNAL,PUBLIC','expires_at'=>'nullable|date','file'=>'required|file']);return ApiResponse::data($this->service->upload($r->file('file'),$d),status:201);}
 public function show(string $id){return ApiResponse::data($this->service->find($id));}
 public function update(Request $r,string $id){return ApiResponse::data($this->service->update($this->service->find($id),$r->validate(['document_name'=>'sometimes|string|max:255','category'=>'nullable|string|max:50','visibility'=>'nullable|in:PRIVATE,INTERNAL,PUBLIC','expires_at'=>'nullable|date','status'=>'sometimes|string'])));}
 public function delete(string $id){return ApiResponse::data($this->service->delete($this->service->find($id)));} public function restore(string $id){return ApiResponse::data($this->service->restore(Document::withTrashed()->findOrFail($id)));}
 public function download(string $id){$d=$this->service->find($id);$this->service->log($d,'downloaded');return Storage::disk($d->storage_disk)->download($d->storage_path,$d->original_filename,['Content-Type'=>$d->mime_type]);}
 public function preview(string $id){$d=$this->service->find($id);$this->service->log($d,'previewed');return response()->file(Storage::disk($d->storage_disk)->path($d->storage_path),['Content-Type'=>$d->mime_type]);}
 public function replace(Request $r,string $id){$d=$r->validate(['file'=>'required|file','change_note'=>'nullable|string|max:500']);return ApiResponse::data($this->service->replace($this->service->find($id),$r->file('file'),$d));}
 public function relation(Request $r,string $id){return ApiResponse::data($this->service->relate($this->service->find($id),$r->validate(['entity_type'=>'required|string|max:40','entity_id'=>'required|string','relation_type'=>'nullable|string|max:30'])),status:201);}
 public function relations(string $id){return ApiResponse::data($this->service->find($id)->relations);}
 public function deleteRelation(string $id,string $relationId){$this->service->removeRelation($this->service->find($id),$relationId);return ApiResponse::noContent();}
 public function archive(string $id){return ApiResponse::data($this->service->archive($this->service->find($id)));}
 public function verify(Request $r,string $id){return ApiResponse::data($this->service->verify($this->service->find($id),true,$r->validate(['note'=>'nullable|string|max:500'])['note']??null));} public function reject(Request $r,string $id){return ApiResponse::data($this->service->verify($this->service->find($id),false,$r->validate(['note'=>'required|string|max:500'])['note']));}
}

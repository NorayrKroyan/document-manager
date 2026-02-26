<?php

namespace App\Http\Controllers\DocumentManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentManager\StoreDocumentRequest;
use App\Http\Requests\DocumentManager\UpdateDocumentRequest;
use App\Models\Contact;
use App\Models\DocOwner;
use App\Models\DocType;
use App\Models\Document;
use App\Queries\DocumentManager\DocumentsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentsController extends Controller
{
    public function list(Request $request, DocumentsQuery $q): JsonResponse
    {
        $limit  = (int)$request->query('limit', 50);
        $page   = (int)$request->query('page', 1);
        $search = (string)$request->query('q', '');

        $limit = max(1, min(200, $limit));
        $page  = max(1, $page);

        $res = $q->list($search, $limit, $page);

        // If your query already returns file_url, keep it.
        // If it only returns "path" like "storage/...", front-end can use "/"+path.
        return response()->json([
            'ok'    => true,
            'rows'  => $res['rows'],
            'total' => $res['total'],
        ]);
    }

    public function lookups(): JsonResponse
    {
        $doctypes = DocType::query()
            ->select(['id_doctype as id', 'type_name as name', 'type_extension', 'require_expire'])
            ->orderBy('type_name')
            ->get();

        $docowners = DocOwner::query()
            ->select(['id_docowner as id', 'owner_name as name', 'table_reference'])
            ->orderBy('owner_name')
            ->get();

        return response()->json([
            'ok' => true,
            'doctypes' => $doctypes,
            'docowners' => $docowners,
        ]);
    }

    // Remote owner search: contacts + vehicles
    public function owners(Request $request): JsonResponse
    {
        $ownerTypeId = (int)$request->query('owner_type_id', 0);
        $q = trim((string)$request->query('q', ''));
        $limit = (int)$request->query('limit', 20);

        $limit = max(1, min(50, $limit));

        $ot = DocOwner::query()->where('id_docowner', $ownerTypeId)->first();
        if (!$ot) return response()->json(['ok' => true, 'rows' => []]);

        $ref = strtolower(trim((string)$ot->table_reference));

        if ($ref === 'contact') {
            $query = Contact::query()->select(['id_contact', 'fname', 'lname']);

            if ($q !== '') {
                $like = '%' . $q . '%';
                $parts = preg_split('/\s+/', $q);

                $query->where(function ($w) use ($like, $parts) {
                    $w->where('fname', 'like', $like)
                        ->orWhere('lname', 'like', $like);

                    if (count($parts) >= 2) {
                        $f = '%' . $parts[0] . '%';
                        $l = '%' . $parts[1] . '%';
                        $w->orWhere(function ($w2) use ($f, $l) {
                            $w2->where('fname', 'like', $f)->where('lname', 'like', $l);
                        });
                    }
                });
            }

            $rows = $query
                ->orderBy('fname')
                ->orderBy('lname')
                ->limit($limit)
                ->get()
                ->map(fn ($c) => [
                    'id'   => (int)$c->id_contact,
                    'name' => trim(($c->fname ?? '') . ' ' . ($c->lname ?? '')),
                ])
                ->values();

            return response()->json(['ok' => true, 'rows' => $rows]);
        }

        if ($ref === 'vehicle') {
            $query = \App\Models\Vehicle::query()
                ->where('is_deleted', 0)
                ->select(['id_vehicle', 'vehicle_name']);

            if ($q !== '') {
                $query->where('vehicle_name', 'like', '%' . $q . '%');
            }

            $rows = $query
                ->orderBy('vehicle_name')
                ->limit($limit)
                ->get()
                ->map(fn ($v) => [
                    'id' => (int)$v->id_vehicle,
                    'name' => (string)($v->vehicle_name ?? ''),
                ])
                ->values();

            return response()->json(['ok' => true, 'rows' => $rows]);
        }

        return response()->json(['ok' => true, 'rows' => []]);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $now = time();

        if (!$request->hasFile('file')) {
            return response()->json([
                'ok' => false,
                'errors' => ['file' => 'File is required'],
            ], 422);
        }

        $f = $request->file('file');
        $sizeBytes = (int)($f->getSize() ?? 0);

        $ext  = strtolower($f->getClientOriginalExtension() ?: 'bin');
        $name = 'file_' . Str::random(16) . '.' . $ext;

        // storage/app/public/document-manager/yy/mm/file_xxx.ext
        $sub = date('y/m');
        $diskRelPath = "document-manager/{$sub}/{$name}";

        Storage::disk('public')->putFileAs("document-manager/{$sub}", $f, $name);

        // DB path format matches your existing "storage/...." style
        $dbPath = 'storage/' . $diskRelPath;

        $doc = new Document();
        $doc->id_doctype      = (int)$data['id_doctype'];
        $doc->doc_name        = (string)$data['doc_name'];
        $doc->doc_description = $data['doc_description'] ?? null;
        $doc->id_docowner     = (int)$data['id_docowner'];
        $doc->id_owner        = (int)$data['id_owner'];
        $doc->doc_expiration  = $data['doc_expiration'] ?? null;
        $doc->date_created    = $now;
        $doc->date_modified   = $now;
        $doc->is_deleted      = 0;

        $doc->path = $dbPath;
        $doc->document_size = $sizeBytes;

        $doc->save();

        return response()->json(['ok' => true, 'id_document' => (int)$doc->id_document]);
    }

    public function update(int $id, UpdateDocumentRequest $request): JsonResponse
    {
        $doc = Document::query()->where('id_document', $id)->where('is_deleted', 0)->first();
        if (!$doc) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }

        $data = $request->validated();
        $now = time();

        $doc->id_doctype      = (int)$data['id_doctype'];
        $doc->doc_name        = (string)$data['doc_name'];
        $doc->doc_description = $data['doc_description'] ?? null;
        $doc->id_docowner     = (int)$data['id_docowner'];
        $doc->id_owner        = (int)$data['id_owner'];
        $doc->doc_expiration  = $data['doc_expiration'] ?? null;
        $doc->date_modified   = $now;

        if ($request->hasFile('file')) {
            // delete old file if it was ours (storage/...)
            $oldPath = (string)($doc->path ?? '');
            if ($oldPath !== '' && str_starts_with($oldPath, 'storage/')) {
                $oldDiskPath = substr($oldPath, strlen('storage/')); // remove prefix
                if ($oldDiskPath && Storage::disk('public')->exists($oldDiskPath)) {
                    Storage::disk('public')->delete($oldDiskPath);
                }
            }

            $f = $request->file('file');
            $sizeBytes = (int)($f->getSize() ?? 0);

            $ext  = strtolower($f->getClientOriginalExtension() ?: 'bin');
            $name = 'file_' . Str::random(16) . '.' . $ext;

            $sub = date('y/m');
            $diskRelPath = "document-manager/{$sub}/{$name}";

            Storage::disk('public')->putFileAs("document-manager/{$sub}", $f, $name);

            $doc->path = 'storage/' . $diskRelPath;
            $doc->document_size = $sizeBytes;
        }

        $doc->save();

        return response()->json(['ok' => true, 'id_document' => (int)$doc->id_document]);
    }

    public function destroy(int $id): JsonResponse
    {
        $doc = Document::query()->where('id_document', $id)->where('is_deleted', 0)->first();
        if (!$doc) return response()->json(['ok' => true]);

        $doc->is_deleted = 1;
        $doc->date_modified = time();
        $doc->save();

        return response()->json(['ok' => true]);
    }
}

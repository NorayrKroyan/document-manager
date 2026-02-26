<?php

namespace App\Queries\DocumentManager;

use App\Models\Document;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DocumentsQuery
{
    public function list(string $search, int $limit, int $page, string $status = 'active'): array
    {
        $q = Document::query()
            ->with([
                'doctype:id_doctype,type_name,require_expire,type_extension',
                'docowner:id_docowner,owner_name,table_reference',
            ]);

        $status = strtolower(trim($status));

        if ($status === 'deleted') {
            $q->where('is_deleted', 1);
        } else {
            // default = active
            $q->where('is_deleted', 0);
        }

        if ($search !== '') {
            $s = '%' . $search . '%';
            $q->where(function ($w) use ($s) {
                $w->where('doc_name', 'like', $s)
                    ->orWhere('path', 'like', $s)
                    ->orWhere('doc_description', 'like', $s);
            });
        }

        /** @var LengthAwarePaginator $p */
        $p = $q->orderByDesc('id_document')
            ->paginate($limit, ['*'], 'page', $page);

        $docs = collect($p->items());

        $ownerNameMap = $this->buildOwnerNameMap($docs);

        $rows = $docs->map(function (Document $d) use ($ownerNameMap) {
            $idOwner = (int)$d->id_owner;

            return [
                'id_document'     => (int)$d->id_document,
                'path'            => (string)$d->path,
                'file_url'        => $this->buildFileUrl((string)$d->path),
                'id_doctype'      => (int)$d->id_doctype,
                'doctype_name'    => (string)($d->doctype?->type_name ?? ''),
                'require_expire'  => (int)($d->doctype?->require_expire ?? 0),
                'type_extension'  => (string)($d->doctype?->type_extension ?? ''),
                'doc_name'        => (string)$d->doc_name,
                'doc_description' => (string)($d->doc_description ?? ''),
                'id_docowner'     => (int)$d->id_docowner,
                'owner_type_name' => (string)($d->docowner?->owner_name ?? ''),
                'id_owner'        => $idOwner,
                'owner_name'      => $ownerNameMap[$this->ownerKey((int)$d->id_docowner, $idOwner)]
                    ?? ($idOwner ? ('#' . $idOwner) : null),
                'doc_expiration'  => $d->doc_expiration ? $d->doc_expiration->format('Y-m-d') : null,
                'is_deleted'      => (int)($d->is_deleted ?? 0),
                'date_created'    => (int)$d->date_created,
                'date_modified'   => $d->date_modified ? (int)$d->date_modified : null,
                'document_size'   => $d->document_size ? (int)$d->document_size : null,
            ];
        })->values();

        return [
            'rows'  => $rows,
            'total' => (int)$p->total(),
        ];
    }

    private function buildOwnerNameMap($docs): array
    {
        $idsCarrier = [];
        $idsClient  = [];
        $idsContact = [];
        $idsVehicle = [];

        foreach ($docs as $d) {
            $type = (int)($d->id_docowner ?? 0);
            $id   = (int)($d->id_owner ?? 0);
            if (!$type || !$id) continue;

            if ($type === 4) $idsCarrier[] = $id;
            if ($type === 6) $idsClient[]  = $id;
            if ($type === 1) $idsContact[] = $id;
            if ($type === 2) $idsVehicle[] = $id;
        }

        $idsCarrier = array_values(array_unique($idsCarrier));
        $idsClient  = array_values(array_unique($idsClient));
        $idsContact = array_values(array_unique($idsContact));
        $idsVehicle = array_values(array_unique($idsVehicle));

        $map = [];

        if (!empty($idsCarrier)) {
            $rows = DB::table('carrier')
                ->where('is_deleted', 0)
                ->whereIn('id_carrier', $idsCarrier)
                ->get(['id_carrier', 'carrier_name']);

            foreach ($rows as $r) {
                $map[$this->ownerKey(4, (int)$r->id_carrier)] = (string)($r->carrier_name ?? '');
            }
        }

        if (!empty($idsClient)) {
            $rows = DB::table('clients')
                ->where('is_deleted', 0)
                ->whereIn('client_id', $idsClient)
                ->get(['client_id', 'client_name']);

            foreach ($rows as $r) {
                $map[$this->ownerKey(6, (int)$r->client_id)] = (string)($r->client_name ?? '');
            }
        }

        if (!empty($idsContact)) {
            $rows = DB::table('contact')
                ->where('is_deleted', 0)
                ->whereIn('id_contact', $idsContact)
                ->get(['id_contact', 'first_name', 'last_name']);

            foreach ($rows as $r) {
                $name = trim(((string)($r->first_name ?? '')) . ' ' . ((string)($r->last_name ?? '')));
                $map[$this->ownerKey(1, (int)$r->id_contact)] = $name;
            }
        }

        if (!empty($idsVehicle)) {
            $rows = DB::table('vehicle')
                ->where('is_deleted', 0)
                ->whereIn('id_vehicle', $idsVehicle)
                ->get(['id_vehicle', 'vehicle_name', 'vehicle_number']);

            foreach ($rows as $r) {
                $name = (string)($r->vehicle_name ?? '');
                $num  = trim((string)($r->vehicle_number ?? ''));

                if ($num !== '') $name = trim($name) . ' (' . $num . ')';

                $map[$this->ownerKey(2, (int)$r->id_vehicle)] = $name;
            }
        }

        return $map;
    }

    private function ownerKey(int $docOwnerId, int $ownerId): string
    {
        return $docOwnerId . ':' . $ownerId;
    }

    private function buildFileUrl(string $path): ?string
    {
        $p = trim($path);
        if ($p === '') return null;

        if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) return $p;

        return '/' . ltrim($p, '/');
    }
}

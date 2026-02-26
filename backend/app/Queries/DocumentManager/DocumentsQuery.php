<?php

namespace App\Queries\DocumentManager;

use App\Models\Document;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DocumentsQuery
{
    public function list(string $search, int $limit, int $page): array
    {
        $q = Document::query()
            ->where('is_deleted', 0)
            ->with([
                'doctype:id_doctype,type_name,require_expire,type_extension',
                'docowner:id_docowner,owner_name,table_reference',
            ]);

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

        /** @var \Illuminate\Support\Collection<int, Document> $docs */
        $docs = collect($p->items());

        // ---- Build owner_name map in batch (fast, no joins) ----
        $ownerNameMap = $this->buildOwnerNameMap($docs);

        $rows = $docs->map(function (Document $d) use ($ownerNameMap) {
            $idOwner = (int) $d->id_owner;

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
                'owner_name'      => $ownerNameMap[$this->ownerKey((int)$d->id_docowner, $idOwner)] ?? ($idOwner ? ('#' . $idOwner) : null),
                'doc_expiration'  => $d->doc_expiration ? $d->doc_expiration->format('Y-m-d') : null,
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

    /**
     * Build a lookup map:
     * key = "{id_docowner}:{id_owner}"
     * value = human name (carrier_name, client_name, "fname lname", vehicle_name)
     */
    private function buildOwnerNameMap($docs): array
    {
        // Group ids by owner type
        $idsCarrier = [];
        $idsClient  = [];
        $idsContact = [];
        $idsVehicle = [];

        foreach ($docs as $d) {
            $type = (int) ($d->id_docowner ?? 0);
            $id   = (int) ($d->id_owner ?? 0);
            if (!$type || !$id) continue;

            if ($type === 4) $idsCarrier[] = $id; // Carrier
            if ($type === 6) $idsClient[]  = $id; // Client (clients table)
            if ($type === 1) $idsContact[] = $id; // Contact
            if ($type === 2) $idsVehicle[] = $id; // Vehicle
        }

        $idsCarrier = array_values(array_unique($idsCarrier));
        $idsClient  = array_values(array_unique($idsClient));
        $idsContact = array_values(array_unique($idsContact));
        $idsVehicle = array_values(array_unique($idsVehicle));

        $map = [];

        // Carrier
        if (!empty($idsCarrier)) {
            $rows = DB::table('carrier')
                ->where('is_deleted', 0)
                ->whereIn('id_carrier', $idsCarrier)
                ->get(['id_carrier', 'carrier_name']);

            foreach ($rows as $r) {
                $k = $this->ownerKey(4, (int)$r->id_carrier);
                $map[$k] = (string)($r->carrier_name ?? '');
            }
        }

        // Client
        if (!empty($idsClient)) {
            $rows = DB::table('clients')
                ->where('is_deleted', 0)
                ->whereIn('client_id', $idsClient)
                ->get(['client_id', 'client_name']);

            foreach ($rows as $r) {
                $k = $this->ownerKey(6, (int)$r->client_id);
                $map[$k] = (string)($r->client_name ?? '');
            }
        }

        // Contact (your schema uses fname/lname)
        if (!empty($idsContact)) {
            $rows = DB::table('contact')
                ->where('is_deleted', 0)
                ->whereIn('id_contact', $idsContact)
                ->get(['id_contact', 'first_name', 'last_name']);

            foreach ($rows as $r) {
                $name = trim(((string)($r->first_name ?? '')) . ' ' . ((string)($r->last_name ?? '')));
                $k = $this->ownerKey(1, (int)$r->id_contact);
                $map[$k] = $name;
            }
        }

        // Vehicle
        if (!empty($idsVehicle)) {
            $rows = DB::table('vehicle')
                ->where('is_deleted', 0)
                ->whereIn('id_vehicle', $idsVehicle)
                ->get(['id_vehicle', 'vehicle_name', 'vehicle_number']);

            foreach ($rows as $r) {
                $name = (string)($r->vehicle_name ?? '');
                $num  = trim((string)($r->vehicle_number ?? ''));

                if ($num !== '') {
                    $name = trim($name) . ' (' . $num . ')';
                }

                $k = $this->ownerKey(2, (int)$r->id_vehicle);
                $map[$k] = $name;
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

<?php

namespace App\Http\Controllers\DocumentManager;

use App\Http\Controllers\Controller;
use App\Models\DocOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnersController extends Controller
{
    public function search(Request $request)
    {
        $ownerTypeId = (int) $request->query('owner_type_id', 0);
        $q           = trim((string) $request->query('q', ''));
        $id          = (int) $request->query('id', 0);
        $limit       = (int) $request->query('limit', 20);

        $limit = max(1, min(100, $limit));

        $table = DocOwner::query()
            ->where('id_docowner', $ownerTypeId)
            ->value('table_reference');

        $table = strtolower(trim((string) $table));
        if ($table === '') {
            return response()->json(['ok' => true, 'rows' => []]);
        }

        $rows = match ($table) {
            'carrier' => $this->carrier($q, $limit, $id),
            'clients' => $this->clients($q, $limit, $id),
            'contact' => $this->contact($q, $limit, $id),
            'vehicle' => $this->vehicle($q, $limit, $id),
            default   => collect(),
        };

        return response()->json(['ok' => true, 'rows' => $rows]);
    }

    private function carrier(string $q, int $limit, int $id)
    {
        $qb = DB::table('carrier')
            ->where('is_deleted', 0)
            ->selectRaw('id_carrier as id, carrier_name as name');

        if ($id > 0) $qb->where('id_carrier', $id);
        elseif ($q !== '') $qb->where('carrier_name', 'like', "%$q%");

        return $qb->orderBy('carrier_name')->limit($limit)->get();
    }

    private function clients(string $q, int $limit, int $id)
    {
        $qb = DB::table('clients')
            ->where('is_deleted', 0)
            ->selectRaw('client_id as id, client_name as name');

        if ($id > 0) $qb->where('client_id', $id);
        elseif ($q !== '') $qb->where('client_name', 'like', "%$q%");

        return $qb->orderBy('client_name')->limit($limit)->get();
    }

    private function contact(string $q, int $limit, int $id)
    {
        $qb = DB::table('contact')
            ->where('is_deleted', 0)
            ->selectRaw("
                id_contact as id,
                TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) as name
            ");

        if ($id > 0) {
            $qb->where('id_contact', $id);
        } elseif ($q !== '') {
            $qb->where(function ($w) use ($q) {
                $w->where('first_name', 'like', "%$q%")
                    ->orWhere('last_name', 'like', "%$q%");
            });
        }

        return $qb->orderBy('first_name')->orderBy('last_name')->limit($limit)->get();
    }

    private function vehicle(string $q, int $limit, int $id)
    {
        $qb = DB::table('vehicle')
            ->where('is_deleted', 0)
            ->selectRaw("
            id_vehicle as id,
            TRIM(
                CONCAT(
                    COALESCE(vehicle_name,''),
                    CASE
                        WHEN vehicle_number IS NULL OR vehicle_number = '' THEN ''
                        ELSE CONCAT(' (', vehicle_number, ')')
                    END
                )
            ) as name
        ");

        if ($id > 0) {
            $qb->where('id_vehicle', $id);
        } elseif ($q !== '') {
            $qb->where(function ($w) use ($q) {
                $w->where('vehicle_name', 'like', "%$q%")
                    ->orWhere('vehicle_number', 'like', "%$q%");
            });
        }

        return $qb
            ->orderBy('vehicle_name')
            ->limit($limit)
            ->get();
    }
}

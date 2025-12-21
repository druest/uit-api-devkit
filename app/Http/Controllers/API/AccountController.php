<?php

namespace App\Http\Controllers\API;

use App\Models\AccountsDocUpload;
use App\Models\Address;
use App\Models\Driver;
use App\Models\Unit;
use App\Models\UnitAgreementHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AccountController extends BaseController
{
    public function storeUnit(Request $request)
    {
        $input = $request->all();

        $unit = Unit::updateOrCreate(
            ['id' => $input['id'] ?? null],
            [
                'plate_full'            => $input['plate_full']            ?? null,
                'plate_region'          => $input['plate_region']          ?? null,
                'plate_number'          => $input['plate_number']          ?? null,
                'plate_suffix'          => $input['plate_suffix']          ?? null,
                'ownership'             => $input['ownership']             ?? 'owned',
                'vendor_id'             => $input['vendor_id']             ?? null,
                'manufacturer'          => $input['manufacturer']          ?? null,
                'manufactured_year'     => $input['manufactured_year']     ?? null,
                'frame_number'          => $input['frame_number']          ?? null,
                'machine_number'        => $input['machine_number']        ?? null,
                'type'                  => $input['type']                  ?? null,
                'tire_count'            => $input['tire_count']            ?? null,
                'bodywork'              => $input['bodywork']              ?? null,
                'color'                 => $input['color']                 ?? null,
                'is_active'             => 1,
                'length'                => $input['length']                ?? null,
                'width'                 => $input['width']                 ?? null,
                'height'                => $input['height']                ?? null,
                'tax_due_date'          => $input['tax_due_date']          ?? null,
                'keur_expiry_date'      => $input['keur_expiry_date']      ?? null,
                'stnk_expiry_date'      => $input['stnk_expiry_date']      ?? null,
                'bpkb_file'             => $input['bpkb_file']             ?? null,
                'stnk_file'             => $input['stnk_file']             ?? null,
                'insurance_expiry_date' => $input['insurance_expiry_date'] ?? null,
                'created_by'            => auth()->id(),
                'updated_by'            => auth()->id(),
                'created_at'            => $input['created_at']            ?? now(),
                'updated_at'            => $input['updated_at']            ?? now(),
            ]
        );

        if (!empty($input['arrDocument'])) {
            AccountsDocUpload::where([
                'ref_id' => $unit->id,
            ])->delete();

            foreach ($input['arrDocument'] as $doc) {
                $filepath = $doc['file_path'] ?? null;
                if (isset($doc['base64']) && $doc['base64']) {
                    $filepath = $this->storeBase64($doc['base64'], $doc['name']);
                }
                AccountsDocUpload::create([
                    'ref_type'        => $doc['ref_type']        ?? null,
                    'ref_id'          => $unit->id,
                    'name'            => $doc['name']            ?? null,
                    'category'        => $doc['category']        ?? null,
                    'doc_number'      => $doc['doc_number']      ?? null,
                    'effective_date'  => $doc['effective_date']  ?? null,
                    'expiry_date'     => $doc['expiry_date']     ?? null,
                    'remarks'         => $doc['remarks']         ?? null,
                    'file_path'       => $filepath,
                    'created_date'    => now(),
                    'created_by'      => auth()->id(),
                ]);
            }
        }

        if (!empty($input['agreementStatus'])) {
            UnitAgreementHistory::where('unit_id', $input['unit_id'])
                ->update(['is_active' => 0]);

            UnitAgreementHistory::create([
                'unit_id'           => $input['unit_id'],
                'agreement_status'  => $input['agreementStatus']['agreementStatus'] ?? null,
                'customer_id'       => $input['agreementStatus']['customer_id']     ?? null,
                'docs_id'           => $input['agreementStatus']['docs_id']         ?? null,
                'remarks'           => $input['agreementStatus']['remarks']         ?? null,
                'created_date'      => now(),
                'created_by'        => auth()->id(),
                'is_active'         => 1,
            ]);
        }

        return response()->json([
            'message' => 'Unit stored successfully',
            'id' => $unit->id,
        ]);
    }

    public function storeDriver(Request $request)
    {
        $input = $request->all();

        $driver = Driver::updateOrCreate(
            ['id' => $input['id'] ?? null],
            [
                'name'                  => $input['name']                  ?? null,
                'nik'                   => $input['nik']                   ?? null,
                'education'             => $input['education']             ?? null,
                'email'                 => $input['email']                 ?? null,
                'marriage_status'       => $input['marriage_status']       ?? null,
                'joined_date'           => $input['joined_date']           ?? null,
                'religion'              => $input['religion']              ?? null,
                'birth_date'            => $input['birth_date']            ?? null,
                'address'               => $input['address']               ?? null,
                'phone'                 => $input['phone']                 ?? null,
                'photo'                 => $input['photo']                 ?? null,
                'ownership'             => $input['ownership']             ?? null,
                'vendor_id'             => $input['vendor_id']             ?? null,
                'ktp_photo'             => $input['ktp_photo']             ?? null,
                'sim_photo'             => $input['sim_photo']             ?? null,
                'house_photo'           => $input['house_photo']           ?? null,
                'google_maps_link'      => $input['google_maps_link']      ?? null,
                'bank_name'             => $input['bank_name']             ?? null,
                'bank_account_number'   => $input['bank_account_number']   ?? null,
                'bank_account_name'     => $input['bank_account_name']     ?? null,
                'status'                => $input['status']                ?? null,
                'emergency_contact'     => $input['emergency_contact']     ?? null,
                'emergency_name'        => $input['emergency_name']        ?? null,
                'emergency_relation'    => $input['emergency_relation']    ?? null,
                'notes'                 => $input['notes']                 ?? null,
                'created_by'            => $input['created_by']            ?? null,
                'updated_by'            => $input['updated_by']            ?? null,
            ]
        );

        if (!empty($input['address_data'])) {
            Address::where([
                'ref_type' => 'driver_add',
                'ref_id' => $driver->id,
            ])->delete();

            foreach ($input['address_data'] as $add) {
                Address::create([
                    'ref_type'     => 'driver_add',
                    'ref_id'       => $driver->id,
                    'category'     => $add['category']     ?? null,
                    'address'      => $add['address']      ?? null,
                    'city'         => $add['city']         ?? null,
                    'province'     => $add['province']     ?? null,
                    'postal_code'  => $add['postal_code']  ?? null,
                    'phone'        => $add['phone']        ?? null,
                    'email'        => $add['email']        ?? null,
                ]);
            }
        }

        if (!empty($input['arrDocument'])) {
            AccountsDocUpload::where([
                'ref_id' => $driver->id,
            ])->delete();

            foreach ($input['arrDocument'] as $doc) {
                $filepath = $doc['file_path'] ?? null;
                if (isset($doc['base64']) && $doc['base64']) {
                    $filepath = $this->storeBase64($doc['base64'], $doc['name']);
                }
                AccountsDocUpload::create([
                    'ref_type'        => $doc['ref_type']        ?? null,
                    'ref_id'          => $driver->id,
                    'name'            => $doc['name']            ?? null,
                    'category'        => $doc['category']        ?? null,
                    'doc_number'      => $doc['doc_number']      ?? null,
                    'effective_date'  => $doc['effective_date']  ?? null,
                    'expiry_date'     => $doc['expiry_date']     ?? null,
                    'remarks'         => $doc['remarks']         ?? null,
                    'file_path'       => $filepath,
                    'created_date'    => now(),
                    'created_by'      => auth()->id(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Driver stored successfully',
            'id' => $driver->id,
        ]);
    }

    public function getUnit($id)
    {
        $driver = Unit::with(['vendor', 'creator', 'updater'])->findOrFail($id);

        $getPersonalDocs = AccountsDocUpload::where([
            'ref_type' => 'unit_personal',
            'ref_id' => $id,
        ])->get()->map(function ($doc) {
            $doc->url = asset(ltrim($doc->file_path, '/'));
            return $doc;
        });

        return response()->json([
            'message' => 'Unit details retrieved.',
            'data' => [
                ...$driver->toArray(),
                'personal_docs'  => $getPersonalDocs->toArray(),
            ],
        ]);
    }

    public function getDriver($id)
    {
        $driver = Driver::with(['vendor', 'creator', 'updater'])->findOrFail($id);

        $getAddress = Address::where([
            'ref_type' => 'driver_add',
            'ref_id' => $id,
        ])->get();

        $getPersonalDocs = AccountsDocUpload::where([
            'ref_type' => 'driver_personal',
            'ref_id' => $id,
        ])->get()->map(function ($doc) {
            $doc->url = asset(ltrim($doc->file_path, '/'));
            return $doc;
        });

        $getAgreement = AccountsDocUpload::where([
            'ref_type' => 'driver_agreement',
            'ref_id' => $id,
        ])->get()->map(function ($doc) {
            $doc->url = asset(ltrim($doc->file_path, '/'));
            return $doc;
        });

        return response()->json([
            'message' => 'Driver details retrieved.',
            'data' => [
                ...$driver->toArray(),
                'address_data'   => $getAddress->toArray(),
                'personal_docs'  => $getPersonalDocs->toArray(),
                'agreement_docs' => $getAgreement->toArray(),
            ],
        ]);
    }

    private function storeBase64($base64, $filename)
    {
        if (preg_match('/^data:\w+\/\w+;base64,/', $base64)) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }

        $decoded = base64_decode($base64);

        if (!Storage::disk('public')->exists('uploads')) {
            Storage::disk('public')->makeDirectory('uploads');
        }

        $path = 'uploads/' . $filename;
        Storage::disk('public')->put($path, $decoded);

        return Storage::url($path);
    }
}

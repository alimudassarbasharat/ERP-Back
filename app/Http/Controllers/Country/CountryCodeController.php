<?php

namespace App\Http\Controllers\Country;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CountryCodeController extends Controller
{
    public function index()
    {
        try {
            $countryCodes = DB::table('country_codes')
                ->when(DB::getSchemaBuilder()->hasColumn('country_codes', 'is_active'), function ($q) {
                    $q->where('is_active', true);
                })
                ->orderBy(DB::getSchemaBuilder()->hasColumn('country_codes', 'country_name') ? 'country_name' : 'id')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Country codes fetched successfully',
                'data' => $countryCodes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch country codes',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}



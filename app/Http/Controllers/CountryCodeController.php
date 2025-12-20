<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CountryCodeController extends Controller
{
    public function index()
    {
        try {
            $countryCodes = DB::table('country_codes')
                ->where('is_active', true)
                ->orderBy('country_name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Country codes fetched successfully',
                'data' => $countryCodes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch country codes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 
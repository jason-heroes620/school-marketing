<?php

namespace App\Http\Controllers;

use App\Models\SchoolAccounts;
use App\Models\SchoolResults;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $count = [];

        try {
            $user = Auth::id();
            $account_id = SchoolAccounts::where('user_id', $user)->first();

            $count = SchoolResults::select([DB::raw('count(*) as count'), "school_result_status"])
                ->where('school_account_id', $account_id['school_account_id'])
                ->where('is_main', 1)
                ->groupBy('school_result_status')
                ->get();
        } catch (Exception $e) {
            Log::error('Error getting dashboard count' . $e);
        }
        return Inertia::render('Dashboard', [
            'count' => $count
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\SchoolAccounts;
use App\Models\SchoolResults;
use App\Models\SettingGroups;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $user = Auth::id();
        $account_id = SchoolAccounts::where('user_id', $user)->first();

        $types = SchoolResults::select([DB::raw('count(type_of_school) as value'), 'type_of_school as name'])
            ->leftJoin('type_of_school', 'school_results.school_result_id', 'type_of_school.school_result_id')
            ->where('school_result_status', 'C')
            ->where('school_account_id', $account_id['school_account_id'])
            ->groupBy('type_of_school')
            ->orderBy('value', 'desc')
            ->limit(5)
            ->get()->toArray();
        $fee_group_id = SettingGroups::where('setting_group_short', 'fees')->first();

        $fees = SchoolResults::select([DB::raw('sum(fee_amount)/count(*) as value'), 'setting', 'radius'])
            ->leftJoin('fees', function ($join) {
                $join->on('school_results.school_result_id', 'fees.school_result_id');
            })
            ->leftJoin('settings', function ($join) use ($fee_group_id) {
                $join->on('settings.sort_order', 'fees.fee_type')
                    ->where('settings.setting_group_id', $fee_group_id['setting_group_id']);
            })
            ->where('school_result_status', 'C')
            ->where('school_account_id', $account_id['school_account_id'])
            ->groupBy('radius', 'setting')
            ->get()->toArray();
        $types = $this->filterAndCleanArrayName($types);
        $fees = $this->filterAndCleanArrayValue($fees);


        return Inertia::render("Reports", [
            'types' => $types,
            'fees' => $fees
        ]);
    }

    private function filterAndCleanArrayValue($array)
    {
        $filteredData = array_filter($array, function ($item) {
            // Check if the 'name' key exists and its value is not null
            return isset($item['value']) && $item['value'] !== null;
        });
        $reindexedObjects = array_values($filteredData);

        return $reindexedObjects;
    }

    private function filterAndCleanArrayName($array)
    {
        $filteredData = array_filter($array, function ($item) {
            // Check if the 'name' key exists and its value is not null
            return isset($item['name']) && $item['name'] !== null;
        });
        $reindexedObjects = array_values($filteredData);

        return $reindexedObjects;
    }
}

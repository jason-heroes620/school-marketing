<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Results;
use App\Models\ResultsFromWeb;
use App\Models\SchoolAccounts;
use App\Models\SchoolResults;
use App\Models\SettingGroups;
use App\Models\Settings;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Uid\UuidV8;

class ProfileController extends Controller
{
    protected $schoolResultController;

    public function __construct(SchoolResultsController $schoolResultController)
    {
        $this->schoolResultController = $schoolResultController;
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // $user->delete();
        $user->update([
            'password' => ''
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function mySchool(Request $request)
    {
        $user = Auth::id();
        $account_id = SchoolAccounts::where('user_id', $user)->first();
        $groups = SettingGroups::where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        foreach ($groups as $group) {
            $group['settings'] = Settings::where('setting_group_id', $group['setting_group_id'])
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->get();
        }

        $school_result_id = SchoolResults::select("school_result_id")
            ->where('school_account_id', $account_id['school_account_id'])
            ->where('is_main', 0)
            ->first();

        $existingData = "";
        $getExistingData = [];
        if ($school_result_id) {
            $getExistingData = Results::select("results")
                ->where('school_result_id', $school_result_id['school_result_id'])
                ->first();
        }

        $settings = SettingGroups::select("setting_group_short")
            ->where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->get();

        $dynamicFields = collect($settings)->pluck('setting_group_short')->mapWithKeys(fn($group) => [$group => []])
            ->toArray();

        $existingData = $getExistingData ? json_decode($getExistingData['results']) : $dynamicFields;
        $web = ResultsFromWeb::select('results')->where('school_result_id', $school_result_id['school_result_id'])->first();

        $id = $school_result_id['school_result_id'];

        return Inertia::render('Profile/MySchool', compact('groups', 'existingData', 'dynamicFields', 'web', 'id'));
    }

    public function updateMySchool(Request $request)
    {
        try {
            Results::updateOrCreate(
                ['school_result_id' => $request->input('id')],
                [
                    'results' => json_encode($request->input('data'))
                ]
            );

            $this->schoolResultController->updateDatabase($request->input('data'), $request->input('id'));


            return response()->json(['success'], 200);
        } catch (Exceptions $e) {
            Log::error($e);
            return response()->json(['error', $e], 400);
        }
    }
}

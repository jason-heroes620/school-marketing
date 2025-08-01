<?php

namespace App\Http\Controllers;

use App\Models\AgeGroups;
use App\Models\CurriculumModel;
use App\Models\EducationPhilosophy;
use App\Models\Facilities;
use App\Models\Fees;
use App\Models\NoOfStudents;
use App\Models\OtherInformations;
use App\Models\Results;
use App\Models\ResultsFromWeb;
use App\Models\SchoolAccounts;
use App\Models\SchoolResults;
use App\Models\SettingGroups;
use App\Models\Settings;
use App\Models\TeacherStudentRatio;
use App\Models\Themes;
use App\Models\TypeOfSchool;
use App\Services\GeminiService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Js;
use Symfony\Component\Uid\UuidV8;

use function PHPUnit\Framework\isArray;
use function PHPUnit\Framework\isEmpty;

class SchoolResultsController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function getNearByList(Request $request)
    {
        $latitude = 3.0527144;
        $longitude = 101.6717487;
        $radius = 2000; // 5 km in meters
        $apiKey = config('custom.google_key');
        $types = 'school|preschool';
        $keyword = 'kindergarten|childcare|nursery|preschool';

        // Get the page token from the request, if it exists
        // $pageToken = $request->query('page_token');

        // Build the base URL
        $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?";
        $url .= "location={$latitude},{$longitude}";
        $url .= "&radius={$radius}";
        $url .= "&type={$types}";
        $url .= "&key={$apiKey}";
        $url .= "&keyword={$keyword}"; // Add keyword for finer filtering

        // If a page token is provided, append it to the URL
        $pageToken = $request->query('page_token');
        if ($pageToken) {
            $url .= "&pagetoken={$pageToken}";
        }

        $educationCenters = [];
        $nextPageToken = null; // To store the next_page_token from Google API

        try {
            $response = Http::get($url);
            $data = $response->json();

            if ($data['status'] === 'OK' && !empty($data['results'])) {
                foreach ($data['results'] as $place) {
                    $centerData = [
                        'name' => $place['name'],
                        'address' => $place['vicinity'] ?? $place['formatted_address'] ?? 'N/A',
                        'latitude' => $place['geometry']['location']['lat'],
                        'longitude' => $place['geometry']['location']['lng'],
                        'place_id' => $place['place_id'],
                        'rating' => $place['rating'] ?? null,
                        'user_ratings_total' => $place['user_ratings_total'] ?? null,
                    ];

                    // Optional: Save or update the education center in the database
                    // EducationCenter::updateOrCreate(
                    //     ['place_id' => $centerData['place_id']],
                    //     $centerData
                    // );

                    $educationCenters[] = $centerData;
                }

                // Check for next_page_token
                if (isset($data['next_page_token'])) {
                    $nextPageToken = $data['next_page_token'];
                }
            }

            // Manually create a paginator for the results
            // Since Google API returns up to 20 results per page,
            // we'll simulate pagination for Inertia.
            // We need to know if there's a next page token to show "next" link.
            $perPage = 20; // Google API's default page size
            $currentPage = $request->query('page', 1);

            // We only have the current page's results and a next_page_token.
            // We can't know the total number of items or last page without making
            // all API calls, which is inefficient.
            // So, we'll use a "simple paginate" like approach for Inertia,
            // focusing on "Previous" and "Next" links based on `nextPageToken`.

            // Create a fake pagination collection for Inertia
            // This is a workaround as we don't have a total count from Google API
            $paginatedResults = new \Illuminate\Pagination\LengthAwarePaginator(
                $educationCenters,
                $nextPageToken ? ($currentPage * $perPage) + 1 : ($currentPage * $perPage), // A simple heuristic for total
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            // Manually add the next_page_token to the pagination data if it exists
            if ($nextPageToken) {
                $paginatedResults->appends(['page_token' => $nextPageToken]);
            }

            // We need to ensure that the `page_token` parameter is included in pagination links
            // when navigating to the next page, but not for the previous page (which goes back to the initial search or previous token).
            // Inertia will handle building the URLs.

            return Inertia::render('Nearby', [
                'educationCenters' => $paginatedResults, // Pass the paginated collection
                'presetLocation' => [
                    'lat' => $latitude,
                    'lng' => $longitude,
                ],
                'currentPageToken' => $pageToken, // Pass current token for reference if needed
                'nextPageToken' => $nextPageToken, // Pass next token explicitly
            ]);
        } catch (\Exception $e) {
            return Inertia::render('EducationCenters/Index', [
                'educationCenters' => (new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, $currentPage))->toArray(), // Empty paginator
                'error' => 'Failed to fetch education centers: ' . $e->getMessage(),
                'presetLocation' => [
                    'lat' => $latitude,
                    'lng' => $longitude,
                ],
                'currentPageToken' => $pageToken,
                'nextPageToken' => null,
            ]);
        }
    }

    public function searchPage()
    {
        $user = Auth::user();

        $center = SchoolAccounts::select(['latitude', 'longitude'])
            ->where('user_id', $user->id)
            ->first();

        $latitude = $center['latitude'];
        $longitude = $center['longitude'];

        $groups = SettingGroups::where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        foreach ($groups as $group) {
            $group['settings'] = Settings::where('setting_group_id', $group['setting_group_id'])
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->get();
        }
        return Inertia::render('Search', [
            'groups' => $groups,
            'longitude' => $longitude,
            'latitude' => $latitude
        ]);
    }



    // private function getGoogleList($latitude, $longitude, $radius, $nextPageToken)
    // {
    //     $apiKey = config('custom.google_key');
    //     $types = 'school|education';
    //     $search = "kindergarten";

    //     // Get the page token from the request, if it exists


    //     // Build the base URL
    //     $url = "https://places.googleapis.com/v1/places:searchText";

    //     $educationCenters = [];

    //     try {
    //         $response = Http::withHeaders(
    //             [
    //                 "X-Goog-Api-Key" => $apiKey,
    //                 'X-Goog-FieldMask' => 'places.displayName,places.formattedAddress,places.location,places.id,places.rating,places.userRatingCount,nextPageToken',
    //                 "Content-Type" => "application/json"
    //             ]
    //         )->post($url, [
    //             'textQuery' => $search,
    //             'pageSize' => 20,
    //             'pageToken' => $nextPageToken,
    //             'locationBias' => [
    //                 'circle' => [
    //                     'center' => [
    //                         'latitude' => $latitude,
    //                         'longitude' => $longitude
    //                     ],
    //                     'radius' => $radius,
    //                 ]
    //             ]
    //         ]);
    //         // dd($response->json());
    //         $data = $response->json();
    //         // dd($data['places']);
    //         // if ($data['status'] === 'OK' && !empty($data['results'])) {
    //         if ($data['places']) {
    //             foreach ($data['places'] as $place) {
    //                 $centerData = [
    //                     // 'name' => $place['name'],
    //                     // 'address' => $place['vicinity'] ?? $place['formatted_address'] ?? 'N/A',
    //                     // 'latitude' => $place['geometry']['location']['lat'],
    //                     // 'longitude' => $place['geometry']['location']['lng'],
    //                     // 'place_id' => $place['place_id'],
    //                     // 'rating' => $place['rating'] ?? null,
    //                     // 'user_ratings_total' => $place['user_ratings_total'] ?? null,
    //                     'name' => $place['displayName']['text'],
    //                     'address' => $place['vicinity'] ?? $place['formattedAddress'] ?? 'N/A',
    //                     'latitude' => $place['location']['latitude'],
    //                     'longitude' => $place['location']['longitude'],
    //                     'place_id' => $place['id'],
    //                     'rating' => $place['rating'] ?? null,
    //                     'user_ratings_total' => $place['userRatingCount'] ?? null,

    //                 ];

    //                 // Optional: Save or update the education center in the database
    //                 // EducationCenter::updateOrCreate(
    //                 //     ['place_id' => $centerData['place_id']],
    //                 //     $centerData
    //                 // );

    //                 $educationCenters[] = $centerData;
    //             }

    //             // Check for next_page_token
    //             if (isset($data['nextPageToken'])) {
    //                 $nextPageToken = $data['nextPageToken'];
    //             }
    //         }

    //         // Manually add the next_page_token to the pagination data if it exists
    //         // if ($nextPageToken) {
    //         //     $paginatedResults->appends(['page_token' => $nextPageToken]);
    //         // }

    //         return compact('educationCenters', 'nextPageToken');
    //     } catch (\Exception $e) {
    //         return [];
    //     }
    // }

    public function axiosGoogleRequest(Request $request)
    {
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');


        $radius = $request->input('radius', 1000); // in meters
        $nextPageToken = $request->input('next_page_token');
        $types = 'school|preschool';
        $keywords = 'kindergarten|childcare|nursery|preschool';
        $apiKey =  config('custom.google_key');

        $user = Auth::id();
        $account_id = SchoolAccounts::where('user_id', $user)->first();

        $responseData = [];
        $school_results = SchoolResults::where('school_account_id', $account_id['school_account_id'])
            ->where('radius', $radius)
            ->get();

        if (count($school_results) > 0) {
            Log::info('get from database');
            $responseData['results'] =
                SchoolResults::select(["school_result_id", "school_account_id", "place_id", "name", "geometry", 'rating', "school_result_status as status"])
                ->where('school_account_id', $account_id['school_account_id'])
                ->where('radius', "<=", $radius)
                ->get();
            // SchoolResults::where('school_account_id', $account_id['school_account_id'])
            // ->where('radius', "<=", $radius)
            // ->get();
        } else {
            Log::info('get from google');
            $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?';

            $params = [
                'location' => "$latitude,$longitude",
                'radius' => $radius,
                'type' => $types,
                'keyword' => $keywords,
                'key' => $apiKey,
            ];

            if ($nextPageToken) {
                $params = [
                    'pagetoken' => $nextPageToken,
                    'key' => $apiKey,
                ];
            }

            $response = Http::get($url, $params);

            $responseData = $response->json(); // or $response->json()['results']
            $school_result_id = "";
            // Now modify the array
            foreach ($responseData['results'] as &$result) {
                $status = SchoolResults::select("school_result_status")
                    ->where('school_account_id', $account_id['school_account_id'])
                    ->where('place_id', $result['place_id'])
                    ->first();

                $result['status'] = $status ? $status->school_result_status : '';

                $getSchoolResultId = SchoolResults::select("school_result_id")
                    ->where('school_account_id', $account_id['school_account_id'])
                    ->where('place_id', $result['place_id'])
                    ->first();
                if (!$getSchoolResultId) {
                    // $school_result_id = UuidV8::v4();
                    $school = SchoolResults::create([
                        'school_result_id' => $school_result_id,
                        'place_id' => $result['place_id'],
                        'name' => $result['name'],
                        'rating' => $result['rating'] ?? null,
                        'school_account_id' => $account_id['school_account_id'],
                        'radius' => $radius,
                        'geometry' => $result['geometry']
                    ]);
                    Log::info('r');
                    Log::info($school);
                    $school_result_id = $school['school_result_id'];
                    $this->scrapeSchoolData($result['name'], $result['place_id'], $school_result_id);
                } else {
                    $school_result_id = $getSchoolResultId['school_result_id'];

                    if ($getSchoolResultId['run_crawl'] === 0) {
                        $this->scrapeSchoolData($result['name'], $result['place_id'], $school_result_id);
                    }
                }
                SchoolResults::where('school_result_id', $school_result_id)
                    ->where('place_id', $result['place_id'])
                    ->update([
                        'run_crawl' => 1
                    ]);
            }
            unset($result); // Unset reference
        }

        return response()->json($responseData);
    }

    public function getSchoolResultById(Request $request)
    {
        $user = Auth::id();
        $account_id = SchoolAccounts::where('user_id', $user)->first();
        $school_result_id = SchoolResults::select("school_result_id")->where('school_account_id', $account_id['school_account_id'])
            ->where('place_id', $request->id)
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
        return response()->json([
            'existingData' => $existingData,
            'dynamicFields' => $dynamicFields,
            'web' => $web ? ($web['results']) : ''
        ]);
    }

    public function update(Request $request)
    {
        try {
            $user = Auth::id();
            $school = $request->input('school');
            $account_id = SchoolAccounts::where('user_id', $user)->first();
            $school_result_id = "";
            $getSchoolResultId = SchoolResults::select("school_result_id")->where('school_account_id', $account_id['school_account_id'])
                ->where('place_id', $school['place_id'])
                ->first();
            if (!$getSchoolResultId) {
                $school_result_id = UuidV8::v4();
                SchoolResults::create([
                    'school_result_id' => $school_result_id,
                    'place_id' => $school['place_id'],
                    'name' => $school['name'],
                    'school_account_id' => $account_id['school_account_id'],
                ]);
            } else {
                $school_result_id = $getSchoolResultId['school_result_id'];
            }

            Results::updateOrCreate(
                ['school_result_id' => $school_result_id],
                [
                    'results' => json_encode($request->input('data'))
                ]
            );

            $this->updateDatabase($request->input('data'), $school_result_id);


            return response()->json(['success'], 200);
        } catch (Exceptions $e) {
            Log::error($e);
            return response()->json(['error', $e], 400);
        }
    }

    private function updateDatabase($data, $school_result_id)
    {
        foreach ($data as $k => $arr) {
            switch ($k) {
                case 'type_of_school':
                    TypeOfSchool::where('school_result_id', $school_result_id)->delete();
                    foreach ($arr as $v) {
                        if ($v !== null && strlen($v) < 100) {
                            TypeOfSchool::create([
                                'school_result_id' => $school_result_id,
                                'type_of_school' => $v
                            ]);
                        }
                    }
                    break;
                case 'education_philosophy':
                    EducationPhilosophy::where('school_result_id', $school_result_id)->delete();
                    foreach ($arr as $v) {
                        if ($v !== null && strlen($v) < 100) {
                            EducationPhilosophy::create([
                                'school_result_id' => $school_result_id,
                                'education_philosophy' => $v
                            ]);
                        }
                    }
                    break;
                case 'curriculum_models':
                    CurriculumModel::where('school_result_id', $school_result_id)->delete();
                    foreach ($arr as $v) {
                        if ($v !== null && strlen($v) < 100) {
                            CurriculumModel::create([
                                'school_result_id' => $school_result_id,
                                'curriculum_model' => $v
                            ]);
                        }
                    }
                    break;
                case 'age_groups':
                    AgeGroups::where('school_result_id', $school_result_id)->delete();
                    foreach ($arr as $v) {
                        AgeGroups::create([
                            'school_result_id' => $school_result_id,
                            'age_group' => $v
                        ]);
                    }
                    break;
                case 'teacher_student_ratio':
                    TeacherStudentRatio::where('school_result_id', $school_result_id)->delete();
                    foreach ($arr as $i => $v) {
                        if ($v !== null && strlen($v) < 100) {
                            TeacherStudentRatio::create([
                                'school_result_id' => $school_result_id,
                                'teacher_student_ratio' => $v,
                                'sort_order' => $i
                            ]);
                        }
                    }
                    break;
                case 'fees':
                    Fees::where('school_result_id', $school_result_id)->delete();
                    if (is_array($arr)) {
                        foreach ($arr as $i => $v) {
                            Log::info('$v =>');
                            Log::info($v);
                            if (!empty($v) && strlen($v) < 100) {
                                try {
                                    if (is_float((float)$v) && $v != 0) {
                                        Fees::create([
                                            'school_result_id' => $school_result_id,
                                            'fee_type' => $i,
                                            'fee_amount' => $v,
                                        ]);
                                    }
                                } catch (Exception $e) {
                                    Log::error('error in fee entry');
                                    throw new \Exception('Database entry error: ' . $e);
                                }
                            }
                        }
                    }
                    break;
                case 'facilities':
                    Facilities::where('school_result_id', $school_result_id)->delete();
                    foreach ($arr as $i => $v) {
                        if ($v !== null && strlen($v) < 100) {
                            Facilities::create([
                                'school_result_id' => $school_result_id,
                                'facility' => $v,
                            ]);
                        }
                    }
                    break;
                case 'other_information':
                    OtherInformations::where('school_result_id', $school_result_id)->delete();
                    if (!empty($arr)) {
                        OtherInformations::create([
                            'school_result_id' => $school_result_id,
                            'other_information' => $arr,
                        ]);
                    }
                    break;

                case 'no_of_students':
                    foreach ($arr as $i => $v) {
                        $nos = NoOfStudents::where('school_result_id', $school_result_id)->first();
                        Log::info('nos =>');
                        Log::info($nos);
                        if (!$nos) {
                            NoOfStudents::create([
                                'school_result_id' => $school_result_id,
                                'no_of_student' => $v,
                            ]);
                        } else {
                            if (date('Y', strtotime($nos['created_at'])) === date('Y')) {
                                $nos->update(
                                    ['no_of_student' => $v],
                                );
                            } else {
                                NoOfStudents::create([
                                    'school_result_id' => $school_result_id,
                                    'no_of_student' => $v,
                                ]);
                            }
                        }
                    }
                    break;
                case 'theme':
                    Themes::where('school_result_id', $school_result_id)->delete();
                    if (!empty($arr)) {
                        Themes::create([
                            'school_result_id' => $school_result_id,
                            'theme' => $arr,
                        ]);
                    }
                    break;
                default:
                    break;
            }
        }
    }

    public function scrapeSchoolData($name, $placeId, $school_result_id)
    {
        // $name = "Little Creche The Earth Bukit Jalil Preschool and Childcare Centre";
        // $placeId = "ChIJLTHH6alLzDERjiHLiIeM8M0";

        $googleApiKey = config('custom.google_key');
        $placeResponse = Http::get("https://maps.googleapis.com/maps/api/place/details/json", [
            'place_id' => $placeId,
            'fields' => 'name,website',
            'key' => $googleApiKey,
        ]);
        Log::info('place response =>' . $placeResponse);
        $website = $placeResponse['result']['website'] ?? null;

        if (!$website) {
            Log::error("School website not found.");
            return;
        }

        $data = $this->geminiService->extractSchoolDetails($name, $website);

        //$this->updateDatabase($data, $school_result_id);
        ResultsFromWeb::updateOrcreate(
            ['school_result_id' => $school_result_id],
            [
                'results' => json_encode($data),
            ]
        );

        Log::info($data);
    }

    public function setComplete(Request $request)
    {
        try {
            $user = Auth::id();
            $account_id = SchoolAccounts::where('user_id', $user)->first();

            $result = SchoolResults::where('school_account_id', $account_id['school_account_id'])
                ->where('place_id',  $request->id)
                ->update(
                    [
                        'school_result_status' => 'C'
                    ]
                );

            return response()->json(['success' => $result], 200);
        } catch (Exception $e) {
            Log::error('Error update complete. ' . $e);
            return response()->json(['error'], 400);
        }
    }
}

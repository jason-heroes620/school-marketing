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
use App\Models\Tadikas;
use App\Models\TeacherStudentRatio;
use App\Models\Themes;
use App\Models\TypeOfSchool;
use App\Services\GeminiService;
use App\Services\GeminiService2;
use App\Services\IdentifyChain;
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
use Illuminate\Support\Str;

use function PHPUnit\Framework\isArray;
use function PHPUnit\Framework\isEmpty;

class SchoolResultsController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService2 $geminiService)
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
            ->where('is_main', 1)
            ->get();

        if (count($school_results) > 0) {
            Log::info('get from database');
            $results = SchoolResults::select(["school_result_id", "school_account_id", "place_id", "name", "geometry", 'rating', "school_result_status as status", "run_crawl"])
                ->where('school_account_id', $account_id['school_account_id'])
                ->where('radius', "<=", $radius)
                ->where('is_main', 1)
                ->get();
            $responseData['results'] = $results;
            Log::info($results);
            // SchoolResults::where('school_account_id', $account_id['school_account_id'])
            // ->where('radius', "<=", $radius)
            // ->get();
            foreach ($results as &$result) {
                if ($result['run_crawl'] === 0) {
                    Log::info('run crawl if 0');
                    // $this->scrapeSchoolData($result['name'], $result['place_id'], $school_result_id);
                    $this->geminiQuery($result['name'], $result['school_result_id']);

                    SchoolResults::where('school_result_id', $result['school_result_id'])
                        ->where('place_id', $result['place_id'])
                        ->update([
                            'run_crawl' => 1
                        ]);
                }
            }
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
                    // $this->scrapeSchoolData($result['name'], $result['place_id'], $school_result_id);
                    $this->geminiQuery($result['name'], $school_result_id);
                } else {
                    $school_result_id = $getSchoolResultId['school_result_id'];

                    if ($getSchoolResultId['run_crawl'] === 0) {
                        // $this->scrapeSchoolData($result['name'], $result['place_id'], $school_result_id);
                        $this->geminiQuery($result['name'], $school_result_id);
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
        try {
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
        } catch (Exception $e) {
            return response()->json(['error' => $e]);
        }
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

    public function updateDatabase($data, $school_result_id)
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

    public function scrapeSchoolData()
    {
        $name = "Little Caliphs Islamic Kindergarten & Playschool Bandar Sri Permaisuri";
        $placeId = "ChIJjaFkuPA1zDERYy8RXc3zQEo";

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

        // $data = $this->geminiService->extractSchoolDetails($name, $website);

        //$this->updateDatabase($data, $school_result_id);
        // ResultsFromWeb::updateOrcreate(
        //     ['school_result_id' => $school_result_id],
        //     [
        //         'results' => ($data),
        //     ]
        // );

        // Log::info($data);
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

    public function query(GeminiService2 $gemini)
    {
        $name = "Little Caliphs Islamic Kindergarten & Playschool Bandar Sri Permaisuri";
        $query = "Research about {$name}, including fees, opening hours, and educational program";

        $response = $gemini->ask($query);

        if (!$response) {
            return response()->json(['error' => 'Gemini failed to return a valid response'], 500);
        }

        // Save to DB
        Log::info($response);

        return response()->json(['query' => $query, 'response' => $response]);
    }

    private function geminiQuery($name, $school_result_id)
    {
        Log::info('run gemini query');
        $query = "Research about {$name} in Klang Valley, Malaysia, including fees and rates, opening hours, and educational program and other details";

        $response = $this->geminiService->ask($query);

        if (!$response) {
            return response()->json(['error' => 'Gemini failed to return a valid response'], 500);
        }

        // Save to DB
        Log::info($response);

        // return response()->json(['query' => $query, 'response' => $response]);

        ResultsFromWeb::updateOrcreate(
            ['school_result_id' => $school_result_id],
            [
                'results' => ($response),
            ]
        );

        $parsed = $this->extractStructuredInfo($response);
        $record = Results::updateOrCreate(
            ['school_result_id' => $school_result_id],
            ['results' => json_encode($parsed)]
        );
    }

    private function extract_max_rm_from_match($content, $keywords)
    {
        foreach ($keywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                // Find the closest sentence containing the keyword
                $sentences = preg_split('/(?<=[.])\s+/', $content);
                foreach ($sentences as $sentence) {
                    if (stripos($sentence, $keyword) !== false) {
                        if (preg_match_all('/RM\s?([\d,]+)/i', $sentence, $matches)) {
                            $values = array_map(fn($v) => (int) str_replace(',', '', $v), $matches[1]);
                            return max($values);
                        }
                    }
                }
            }
        }
        return null;
    }

    private function extractFees($fees_map, $blocks)
    {
        $results = [];

        foreach ($fees_map as $keywords) {
            $results[] = $this->extract_max_rm_from_match($blocks, $keywords);
        }
        return $results;
    }

    private function extractStructuredInfo(string $content): array
    {
        $attributes = config('school_attributes');
        $result = [];

        // $blocks = preg_split('/\n\s*\d+\.\s/', $content, -1, PREG_SPLIT_NO_EMPTY);
        // $pattern = '/(Tuition|Full day|Registration|Deposits?|Materials|Uniforms|Books?|Discounts?).*?RM ?(\d{1,3}(?:,\d{3})*)/i';
        // $parsed_fees = [];
        Log::info($attributes['fees']);
        foreach ($attributes as $group => $options) {
            $result[$group] = [];

            if ($group === 'fees') {
                $result[$group] = $this->extractFees($attributes['fees'], $content);
                continue;
            }

            $result[$group] = [];

            if (empty($options)) {
                continue;
            }

            foreach ($options as $option) {
                if (Str::contains(Str::lower($content), Str::lower($option))) {
                    $result[$group][] = $option;
                }
            }
        }

        return $result;
    }

    public function chain()
    {
        $knownChains = [
            'Smart Reader Kids' => ['smart reader kids', 'smart reader'],
            'Q-dees' => ['q-dees', 'qdees'],
            'Little Caliphs' => ['little caliphs', 'lc ', 'LC '], // 'lc ' with a space to avoid false positives
            'R.E.A.L Kids' => ['r.e.a.l kids', 'real kids'],
            'Brainy Bunch' => ['brainy bunch'],
            'Big Apple EduWorld' => ['big apple'],
            'Kinderland' => ['kinderland'],
            'Eduwis' => ['eduwis'],
            'Beaconhouse' => ['beaconhouse'],
            'Kumon' => ['kumon'],
            'Eye Level' => ['eye level'],
            'UCMAS' => ['ucmas'],
            '3Q MRC' => ['3q mrc', 'mrc jawi'],
            'Alfa & Friends' => ['alfa & friends', 'alfa and friends'],
            'The Olive Trees' => ['the olive trees'],
            'Prestij Mulia' => ['prestij mulia'],
            'Anak Cerdik Soleh' => ['anak cerdik soleh'], // Identified as a local chain
            'Anak Ceria Soleh' => ['anak ceria soleh'],   // Identified as a local chain
            'Naluri Bestari' => ['naluri bestari'],     // Identified as a local chain
            'Didik Literasi' => ['didik literasi'],     // Identified as a local chain
            'Tunas Fitrah' => ['tunas fitrah'],         // Identified as a local chain
            'Fonik Millennium' => ['fonik millennium'], // Identified as a local chain
            'Bahtera Ilmu' => ['bahtera ilmu'],         // Identified as a local chain
            'Soleh Squad Islamic Preschool' => ['soleh squad islamic preschool', 'ssip'], // Identified as a local chain
            'Tadika Montessori Sinar Gemilang' => ['montessori sinar gemilang'], // Identified as a local chain
            'Tadika Masmurni' => ['tadikamas murni', 'masmurni'], // Identified as a local chain
            'Nadi Intelek' => ['nadi intelek'],         // Identified as a local chain
            'UIAM (University-affiliated)' => ['universiti islam antarabangsa malaysia', 'uiam'],
            'Tadika KEMAS / JPN (Government)' => ['tadika rakyat', 'kemas', 'jpn'], // Government-run
            'RoboThink' => ['robothink'],
            'Chumbaka' => ['chumbaka'],
            'Kidocode' => ['kidocode'],
            'World of Robotics' => ['world of robotics'],
            'Bricks 4 Kidz' => ['bricks 4 kidz'],
            'The Little Gym' => ['the little gym'],
            // Add more chains and their keywords as you identify them
        ];




        $tadikas = Tadikas::where('updated', 0)->limit(100)->get();
        // $apiKey = config('custom.gemini_api_key');
        // $identifier = new IdentifyChain($apiKey);

        // foreach ($tadikas as $t) {
        //     echo "Identifying chain for '{$t['school_name']}'...\n";
        //     $result = $identifier->identifyChain($t['school_name']);

        //     echo "--- Result ---\n";
        //     if (isset($result['error'])) {
        //         echo $result['error'] . "\n";
        //     } else {
        //         echo "School: {$t['school_name']}\n";
        //         echo "Is Chain: " . ($result['is_chain'] ? 'Yes' : 'No') . "\n";
        //         echo "Chain Name: " . ($result['chain_name'] ?: 'N/A') . "\n";
        //     }
        //     echo "-------------------\n\n";
        // }


        // foreach ($tadikas as $t) {
        //     echo "Identifying chain for '{$t['school_name']}'...\n";
        //     $result = $identifier->identifyChain($t['school_name']);

        //     echo "--- Result ---\n";
        //     if (isset($result['error'])) {
        //         echo $result['error'] . "\n";
        //     } else {
        //         echo "School: {$t['school_name']}\n";
        //         echo "Is Chain: " . ($result['is_chain'] ? 'Yes' : 'No') . "\n";
        //         echo "Chain Name: " . ($result['chain_name'] ?: 'N/A') . "\n";
        //         Log::info($t['school_name'] . ' == ' . $result['chain_name']);
        //         if ($result['is_chain']) {
        //             Tadikas::where('id', $t['id'])
        //                 ->update([
        //                     'chain_name' => $result['chain_name'],
        //                     'updated' => 1
        //                 ]);
        //         } else {
        //             Tadikas::where('id', $t['id'])
        //                 ->update([
        //                     'updated' => 1
        //                 ]);
        //         }
        //     }
        //     echo "-------------------\n\n";
        // }

        foreach ($tadikas as $t) {
            $schoolId = $t['id'];
            $schoolName = strtolower($t['school_name']); // Convert to lowercase for case-insensitive comparison
            $foundChain = '';

            // Iterate through known chains to find a match
            foreach ($knownChains as $chainName => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strpos($schoolName, $keyword) !== false) {
                        $foundChain = $chainName;
                        break 2; // Exit both inner and outer loops once a match is found
                    }
                }
            }
            Log::info($t['school_name'] . ' == ' . $foundChain);
            Tadikas::where('id', $schoolId)
                ->update([
                    'chain_name' => $foundChain,
                    'updated' => 1
                ]);
        }
    }
}

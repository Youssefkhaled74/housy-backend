<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\City;
use App\Models\State;
use App\Models\Country;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NewCheckoutController extends Controller
{
    public function indexCities(){
        $cities = City::all();
        return response()->json([
            'data' => $cities,
            'success' => true,
            'status' => 200,
            'message' => 'Cities fetched successfully'
        ]);
    }

    public function indexStates(){
        $states = State::all();
        return response()->json([
            'data' => $states,
            'success' => true,
            'status' => 200,
            'message' => 'States fetched successfully'
        ]);
    }

    public function indexCountries(){
        $countries = Country::all();
        return response()->json([
            'data' => $countries,
            'success' => true,
            'status' => 200,
            'message' => 'Countries fetched successfully'
        ]);
    }

    public function getCityByStateId($stateId){
        $city = City::where('state_id', $stateId)->get();
        return response()->json([
            'data' => $city,
            'success' => true,
            'status' => 200,
            'message' => 'City fetched successfully'
        ]);
    }
}
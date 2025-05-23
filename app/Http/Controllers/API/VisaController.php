<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Destination;
use App\Models\VisaType;

class VisaController extends Controller
{
    public function destinations(Request $request)
    {
        $perPage = $request->input('per_page', 9); // default to 10 per page
        $destinations = Destination::select('id', 'name', 'code', 'flag')->paginate($perPage);
    
        return response()->json($destinations);
    }

    public function visasTypes($id)
    {
        $visasTypes = Destination::find($id)->visas;
        return response()->json(["data" => $visasTypes]);
    }

    public function PassengersVisasInfo(Request $request)
    {
        $visa_type_id = $request->visa_type_id;
        $adults = $request->adults ?? 0;
        $children = $request->children ?? 0;

        $visa = VisaType::find($visa_type_id);

        if ($visa) {
            return response()->json([
                "data" => [
                    "adult" => [
                        "visa_type_id" => $visa->id,
                        "unit_price" => $visa->adult_price,
                        "count" => $adults,
                        "documents" => $visa->documents ?? [], 
                    ],
                    "child" => [
                        "visa_type_id" => $visa->id,
                        "unit_price" => $visa->child_price,
                        "count" => $children,
                        "documents" => $visa->documents ?? [], 
                    ],
                    "total_price" => ($adults * $visa->adult_price) + ($children * $visa->child_price)
                ]
            ]);
        }

        return response()->json(["data" => null], 404);
    }

    public function totalVisaPrice(Request $request)
    {
        $total = 0;
        $visa_type_id = $request->visa_type_id;
        $adults = $request->adults;
        $children = $request->children;
        $visa = VisaType::find($visa_type_id);

        if ($visa) {
            $total = $adults * $visa->adult_price + $children * $visa->child_price;
        }

        return response()->json(["data" => ["adults" => $adults, "children" => $children, "total" => $total]]);
    }

}

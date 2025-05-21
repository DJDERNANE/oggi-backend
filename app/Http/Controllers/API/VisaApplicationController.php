<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VisaApplication;
use App\Models\VisaApplicationFile;
use App\Models\VisaType;
use App\Models\User;

class VisaApplicationController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(["message" => "Unauthorized"], 401);
        }

        $visaApplications = VisaApplication::where('user_id', $user->id)
            ->with('visaApplicationFiles', 'visaType', 'visaType.destination') // Assuming you have a relationship with files
            ->get();
            logger($visaApplications);
        $visaCount = $visaApplications->count();
        return response()->json(["data" => $visaApplications, "visaCount" => $visaCount]);
    }

    public function store(Request $request)
    {
       
    
        try {
            $userId = auth()->id();
            $visaApplications = [];
    
            // Process each visa application
            foreach ($request->applications as $index => $applicationData) {
                // logger($request->applicationData['files']); // Log full request for debugging
                // Check if the required fields exist
                if (!isset($applicationData['name'], $applicationData['fammily_name'], $applicationData['passport_number'], $applicationData['departure_date'], $applicationData['visa_type_id'])) {
                    return response()->json(["error" => "Missing required fields for application index $index"], 400);
                }
    
                // Find the visa type
                $visa = VisaType::find($applicationData['visa_type_id']);
                if (!$visa) {
                    return response()->json(["error" => "Visa type ID {$applicationData['visa_type_id']} not found."], 404);
                }
    
                // Create visa application
                $visaApplication = VisaApplication::create([
                    'user_id' => $userId,
                    'name' => $applicationData['name'],
                    'fammily_name' => $applicationData['fammily_name'],
                    'passport_number' => $applicationData['passport_number'],
                    'departure_date' => $applicationData['departure_date'],
                    'visa_type_id' => $applicationData['visa_type_id'],
                    'price' => $applicationData['price'] ?? 0,
                    'status' => 'pending',
                ]);
    
                // 🔍 **Debugging files array**
                if (!isset($applicationData['files'])) {
                    logger("No files key found for application index: $index");
                } else {
                    logger("Files for application index: $index", $applicationData['files']);
                }
    
                // **Handle file uploads**
                if (!empty($applicationData['files']) && is_array($applicationData['files'])) {
                    foreach ($applicationData['files'] as $file) {
                        if ($file instanceof \Illuminate\Http\UploadedFile) {
                            try {
                                $path = $file->store("visa_documents/{$userId}", 'public');
    
                                VisaApplicationFile::create([
                                    'visa_application_id' => $visaApplication->id,
                                    'file_path' => $path,
                                    'name' => $file->getClientOriginalName(),
                                    'type' => $file->getClientMimeType(),
                                    'size' => $file->getSize(),
                                ]);
                            } catch (\Exception $e) {
                                return response()->json(["error" => "Failed to upload files for {$applicationData['name']}."], 500);
                            }
                        } else {
                            logger("Invalid file format at index $index");
                        }
                    }
                }
    
                // Store the application response
                $visaApplications[] = $visaApplication;
            }
    
            return response()->json([
                "message" => "Visa applications submitted successfully.",
                "applications" => $visaApplications
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(["error" => "Validation error", "details" => $e->errors()], 422);
        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(["error" => "An unexpected error occurred."], 500);
        }
    }

    
}

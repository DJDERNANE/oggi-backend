<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VisaApplication;
use App\Models\VisaApplicationFile;
use App\Models\VisaType;

class VisaApplicationController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(["message" => "Unauthorized"], 401);
        }

        $visaApplications = VisaApplication::where('user_id', $user->id)
            ->with('visaApplicationFiles') // Assuming you have a relationship with files
            ->get();

        return response()->json(["data" => $visaApplications]);
    }

    public function store(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'applications' => 'required|array|min:1', // Ensure at least one application is submitted
                'applications.*.name' => 'required|string',
                'applications.*.fammily_name' => 'required|string',
                'applications.*.passport_number' => 'required|string',
                'applications.*.departure_date' => 'required|date',
                'applications.*.visa_type_id' => 'required|exists:visa_types,id',
                'applications.*.files.*' => 'file|max:2048', // Each file max 2MB
            ]);

            $userId = auth()->id();
            $visaApplications = [];

            // Process each visa application
            foreach ($request->applications as $applicationData) {
                $visa = VisaType::find($applicationData['visa_type_id']);

                if (!$visa) {
                    return response()->json(["error" => "Visa type ID {$applicationData['visa_type_id']} not found."], 404);
                }

                // Create visa application entry
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
                logger("files ::: ");
                logger($applicationData['files']);
                if ($request->hasFile('files')) {
                    logger("Files:");
                    logger($request->file('files'));
                    foreach ($request->file('files') as $index => $filesArray) {
                        foreach ($filesArray as $file) {
                            logger("File #{$index}:");
                            logger([
                                'original_name' => $file->getClientOriginalName(),
                                'mime_type' => $file->getMimeType(),
                                'size' => $file->getSize(),
                            ]);
                        }
                    }
                }
                // Store uploaded files for this application
                if (!empty($applicationData['files'])) {
                    foreach ($applicationData['files'] as $file) {
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

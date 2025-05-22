<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserDoc;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
class UserDocsController extends Controller
{
    public function mainUserDocs()
    {
        try {
            $userDocs = UserDoc::where('user_id', auth()->id())->where('type',  'main')->get();

            return response()->json(["data" => $userDocs], 200);
        } catch (\Exception $e) {
            // Log the error
            logger('Failed to cget user document', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function temporaryUserDocs()
    {
        try {
            $userDocs = UserDoc::where('user_id', auth()->id())->where('type',  'temporary')->get();

            return response()->json(["data" => $userDocs], 200);
        } catch (\Exception $e) {
            // Log the error
            logger('Failed to cget user document', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function updateUserDoc(Request $request)
    {
        logger("request :: ");
        logger($request);
        try {
            $file = $request->file('file');

            // Store new file
            $path = $file->store('user_docs/' . auth()->id(), 'public');

            // Check if document exists for user and name
            $userDoc = UserDoc::where('user_id', auth()->id())
                ->where('name', $request->input('name'))
                ->first();

            if ($userDoc) {
                // Delete old file if exists
                if ($userDoc->path && Storage::disk('public')->exists($userDoc->path)) {
                    Storage::disk('public')->delete($userDoc->path);
                }

                // Update record
                $userDoc->path = $path;
                $userDoc->save();
            } else {
                response()->json([
                    'status' => 'false',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'User document saved successfully.',
                'data' => $userDoc,
            ]);
        } catch (\Exception $e) {
            // Log the error
            logger('Failed to update user document', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }





    public function zipAndDownload()
    {
        $user = auth()->user();
        $clientName = str_replace(' ', '_', $user->name);
        $zipFileName = "{$clientName}.zip";
        $zipFilePath = storage_path("app/public/{$zipFileName}");
    
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $folderPath = storage_path("app/public/user_docs/{$user->id}");
    
            if (file_exists($folderPath)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($folderPath),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
    
                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = 'user_docs/' . $user->id . '/' . substr($filePath, strlen($folderPath) + 1);
    
                        $zip->addFile($filePath, $relativePath);
                    }
                }
            }
    
            $zip->close();
        } else {
            return back()->with('error', 'Failed to create ZIP file.');
        }
    
        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }
}

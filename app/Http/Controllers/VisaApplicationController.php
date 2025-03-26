<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VisaApplication;
use ZipArchive;

class VisaApplicationController extends Controller
{
    public function download(Request $request)
    {
        $filePath = public_path('storage/' . $request->query('file'));

        if (file_exists($filePath)) {
            return response()->download($filePath);
        }

        abort(404, 'File not found.');
    }


    public function downloadAllFiles(VisaApplication $visaApplication)
    {
        if (!$visaApplication->visaApplicationFiles()->exists()) {
            return back()->with('error', 'No files found.');
        }

        $clientName = str_replace(' ', '_', $visaApplication->name .'_' . $visaApplication->fammily_name);
        $zipFileName = "{$clientName}.zip";
        $zipFilePath = storage_path("app/public/{$zipFileName}");

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($visaApplication->visaApplicationFiles as $file) {
                $filePath = storage_path("app/public/{$file->file_path}");
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, basename($filePath));
                }
            }
            $zip->close();
        } else {
            return back()->with('error', 'Failed to create ZIP file.');
        }

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }
}

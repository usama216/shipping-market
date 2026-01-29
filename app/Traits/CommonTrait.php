<?php
namespace App\Traits;


use Illuminate\Support\Str;

trait CommonTrait
{
    public function sendSuccess($message, $data = '')
    {
        return response()->json([
            'status' => 200,
            'message' => $message,
            'data' => $data,
        ]);
    }
    public function sendError($error_message, $code = '', $data = null)
    {

        return response()->json([
            'status' => 400,
            'message' => $error_message,
            'data' => $data,
        ]);
    }

    public function sendWarning($status, $error_message, $data = '')
    {

        return response()->json([
            'status' => $status,
            'message' => $error_message,
            'data' => $data,
        ]);
    }

    /**
     * Store a file in public storage.
     * 
     * @param \Illuminate\Http\UploadedFile $file The uploaded file
     * @param string $folder The folder name (e.g., 'package_items', 'invoices')
     * @return string|null The path relative to storage symlink (e.g., 'package_items/filename.jpg')
     */
    public function addFile($file, $folder)
    {
        // #region agent log
        $logFile = base_path('.cursor/debug.log');
        $logData = json_encode([
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'A',
            'location' => 'CommonTrait.php:44',
            'message' => 'addFile entry',
            'data' => [
                'fileExists' => $file !== null,
                'fileClass' => $file ? get_class($file) : 'null',
                'originalName' => $file ? $file->getClientOriginalName() : null,
                'folder' => $folder
            ],
            'timestamp' => round(microtime(true) * 1000)
        ]) . "\n";
        file_put_contents($logFile, $logData, FILE_APPEND);
        // #endregion

        if (!$file || !$file->isValid()) {
            // #region agent log
            $logData = json_encode([
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'B',
                'location' => 'CommonTrait.php:52',
                'message' => 'File validation failed',
                'data' => [
                    'file' => $file ? $file->getClientOriginalName() : 'null',
                    'isValid' => $file ? $file->isValid() : false
                ],
                'timestamp' => round(microtime(true) * 1000)
            ]) . "\n";
            file_put_contents($logFile, $logData, FILE_APPEND);
            // #endregion
            \Log::error('Invalid file uploaded', [
                'file' => $file ? $file->getClientOriginalName() : 'null',
                'isValid' => $file ? $file->isValid() : false
            ]);
            return null;
        }

        if ($file->getClientOriginalExtension() === 'exe') {
            return null;
        }

        // Clean up folder path - remove any legacy 'storage/app/public/' prefix
        $folder = str_replace(['storage/app/public/', 'storage/app/public'], '', $folder);
        $folder = trim($folder, '/');
        
        // Ensure folder is not empty - if it is, use 'files' as default
        if (empty($folder)) {
            $folder = 'files';
        }

        $extension = $file->getClientOriginalExtension();
        $fileName = Str::random(15) . '.' . $extension;

        // Use Storage facade directly for more reliable file handling
        try {
            $storage = \Illuminate\Support\Facades\Storage::disk('public');
            
            // Ensure the directory exists
            if (!$storage->exists($folder)) {
                $storage->makeDirectory($folder);
            }
            
            // #region agent log
            $realPath = $file->getRealPath();
            $tempPath = $file->getPathname();
            $isFile = is_file($realPath);
            $fileSize = $realPath ? filesize($realPath) : 0;
            $logData = json_encode([
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'C',
                'location' => 'CommonTrait.php:79',
                'message' => 'Before put - file path check',
                'data' => [
                    'realPath' => $realPath,
                    'tempPath' => $tempPath,
                    'isFile' => $isFile,
                    'fileSize' => $fileSize,
                    'folder' => $folder,
                    'fileName' => $fileName,
                    'originalName' => $file->getClientOriginalName(),
                    'isValid' => $file->isValid(),
                    'getMimeType' => $file->getMimeType(),
                    'getSize' => $file->getSize()
                ],
                'timestamp' => round(microtime(true) * 1000)
            ]) . "\n";
            file_put_contents($logFile, $logData, FILE_APPEND);
            // #endregion
            
            // Use Storage::put() with file contents instead of putFileAs
            // This works even when getRealPath() returns false
            // Read file contents and store directly
            $fileContents = file_get_contents($file->getPathname());
            
            // #region agent log
            $logData = json_encode([
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'H',
                'location' => 'CommonTrait.php:143',
                'message' => 'File contents read',
                'data' => [
                    'contentsSize' => strlen($fileContents),
                    'pathname' => $file->getPathname(),
                    'fileExists' => file_exists($file->getPathname())
                ],
                'timestamp' => round(microtime(true) * 1000)
            ]) . "\n";
            file_put_contents($logFile, $logData, FILE_APPEND);
            // #endregion
            
            if ($fileContents === false) {
                // #region agent log
                $logData = json_encode([
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'I',
                    'location' => 'CommonTrait.php:150',
                    'message' => 'Failed to read file contents',
                    'data' => [
                        'pathname' => $file->getPathname(),
                        'fileExists' => file_exists($file->getPathname())
                    ],
                    'timestamp' => round(microtime(true) * 1000)
                ]) . "\n";
                file_put_contents($logFile, $logData, FILE_APPEND);
                // #endregion
                throw new \Exception('Failed to read uploaded file contents');
            }
            
            // Store the file contents directly
            $fullPath = $folder . '/' . $fileName;
            $path = $storage->put($fullPath, $fileContents);
            
            // #region agent log
            $logData = json_encode([
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'D',
                'location' => 'CommonTrait.php:165',
                'message' => 'After put',
                'data' => [
                    'returnedPath' => $path,
                    'pathEmpty' => empty($path),
                    'fullPath' => $fullPath,
                    'folder' => $folder,
                    'fileName' => $fileName
                ],
                'timestamp' => round(microtime(true) * 1000)
            ]) . "\n";
            file_put_contents($logFile, $logData, FILE_APPEND);
            // #endregion
            
            // Storage::put() returns true on success or false on failure
            // If it returns false, the upload failed
            if ($path === false) {
                // #region agent log
                $logData = json_encode([
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'J',
                    'location' => 'CommonTrait.php:210',
                    'message' => 'Storage::put returned false',
                    'data' => [
                        'fullPath' => $fullPath,
                        'folder' => $folder,
                        'fileName' => $fileName
                    ],
                    'timestamp' => round(microtime(true) * 1000)
                ]) . "\n";
                file_put_contents($logFile, $logData, FILE_APPEND);
                // #endregion
                throw new \Exception('Failed to store file: Storage::put() returned false');
            }
            
            // Storage::put() returns true on success, so use the fullPath we constructed
            if ($path === true) {
                $path = $fullPath;
            }
            
            // Ensure path is not empty
            if (empty($path)) {
                // #region agent log
                $logData = json_encode([
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'E',
                    'location' => 'CommonTrait.php:180',
                    'message' => 'Empty path returned from put',
                    'data' => [
                        'folder' => $folder,
                        'fileName' => $fileName,
                        'originalName' => $file->getClientOriginalName(),
                        'tempPath' => $file->getRealPath(),
                        'fullPath' => $fullPath ?? 'not set'
                    ],
                    'timestamp' => round(microtime(true) * 1000)
                ]) . "\n";
                file_put_contents($logFile, $logData, FILE_APPEND);
                // #endregion
                \Log::error('File storage returned empty path', [
                    'folder' => $folder,
                    'fileName' => $fileName,
                    'originalName' => $file->getClientOriginalName(),
                    'tempPath' => $file->getRealPath()
                ]);
                return null;
            }
            
            // Verify file was actually stored
            if (!$storage->exists($path)) {
                \Log::error('File was not stored after storeAs', [
                    'path' => $path,
                    'folder' => $folder,
                    'fileName' => $fileName
                ]);
                return null;
            }
        } catch (\Exception $e) {
            // #region agent log
            $logData = json_encode([
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'F',
                'location' => 'CommonTrait.php:102',
                'message' => 'Exception caught in addFile',
                'data' => [
                    'error' => $e->getMessage(),
                    'errorClass' => get_class($e),
                    'folder' => $folder,
                    'fileName' => $fileName,
                    'trace' => $e->getTraceAsString()
                ],
                'timestamp' => round(microtime(true) * 1000)
            ]) . "\n";
            file_put_contents($logFile, $logData, FILE_APPEND);
            // #endregion
            \Log::error('File storage failed', [
                'folder' => $folder,
                'fileName' => $fileName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }

        // #region agent log
        $logData = json_encode([
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'G',
            'location' => 'CommonTrait.php:114',
            'message' => 'addFile success exit',
            'data' => [
                'returnedPath' => $path,
                'folder' => $folder,
                'fileName' => $fileName
            ],
            'timestamp' => round(microtime(true) * 1000)
        ]) . "\n";
        file_put_contents($logFile, $logData, FILE_APPEND);
        // #endregion

        // Returns path like 'package_items/filename.jpg'
        // Access via URL: /storage/package_items/filename.jpg
        return $path;
    }

    /**
     * Delete a file from public storage.
     * 
     * @param string|null $path The file path (e.g., 'package_items/filename.jpg')
     */
    public function deleteFile($path)
    {
        if ($path) {
            // Clean up legacy path prefixes if present
            $path = str_replace(['storage/app/public/', 'storage/app/public'], '', $path);
            $path = ltrim($path, '/');

            // Delete from public disk using Storage facade
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
        }
    }

    public function sendPushNotification($message, $data, $emails, $custom_notification = false)
    {
        $content = [
            "en" => $message,
        ];

        $fields = [
            'app_id' => env("ONESIGNAL_APPID"),
            'include_external_user_ids' => $emails,
            'channel_for_external_user_ids' => 'push',
            'data' => $data,
            'contents' => $content,
        ];

        if ($custom_notification) {
            $fields["ios_sound"] = "notification.mp3";
            $fields["android_sound"] = "notification";
            $fields["android_channel_id"] = '6fec68b4-dd0c-45eb-acd9-84bacf99804f';
        }

        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . env("ONESIGNAL_APIKEY"),
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        info($response);
        return $response;
    }

    function generateRandomNumberFormat()
    {
        do {
            $part1 = rand(1, 9);
            $part2 = rand(1000, 9999);
            $part3 = rand(10, 99);
            $packageId = "{$part1}-{$part2}-{$part3}";
        } while (\App\Models\Package::where('package_id', $packageId)->exists());

        return $packageId;
    }

    public function stringifyToArray($items)
    {
        return json_decode($items, true);
    }
}

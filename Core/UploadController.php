<?php

declare(strict_types=1);

namespace Core;

class UploadController
{
    /**
     * Handle user profile photo upload.
     */
    public function handleUserPhoto(?array $file): array
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'No file uploaded or upload error.'];
        }

        $settingsRepo = new \Modules\Admin\SettingsRepository();
        $maxSizeMB = (int)$settingsRepo->get('user_max_upload_size', '2');
        $allowedExtStr = $settingsRepo->get('user_allowed_extensions', 'jpg,png,jpeg');
        $allowedExtensions = array_map('trim', explode(',', strtolower($allowedExtStr)));

        $maxSizeBytes = $maxSizeMB * 1024 * 1024;
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['size'] > $maxSizeBytes) {
            return ['success' => false, 'message' => "File size exceeds {$maxSizeMB}MB limit."];
        }

        if (!in_array($extension, $allowedExtensions)) {
            return ['success' => false, 'message' => 'Format not allowed. Allowed: ' . strtoupper($allowedExtStr)];
        }

        // 2. Prepare Directory
        $uploadDir = __DIR__ . '/../storage/uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // 3. Generate Secure Filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . Session::get('user_id') . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        // 4. Square Crop & Resize (1:1 Ratio as per PRD)
        // We use GD for this. If GD not available, we just move the file.
        if (extension_loaded('gd')) {
            $this->resizeToSquare($file['tmp_name'], $targetPath, 400);
        } else {
            move_uploaded_file($file['tmp_name'], $targetPath);
        }

        return [
            'success' => true,
            'path' => 'storage/uploads/profiles/' . $filename
        ];
    }

    /**
     * Resize/Crop image to square.
     */
    private function resizeToSquare(string $sourcePath, string $targetPath, int $size): void
    {
        $info = getimagesize($sourcePath);
        $type = $info[2];

        switch ($type) {
            case IMAGETYPE_JPEG: $source = imagecreatefromjpeg($sourcePath); break;
            case IMAGETYPE_PNG: $source = imagecreatefrompng($sourcePath); break;
            default: return;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // Crop to square
        $minSide = min($width, $height);
        $x = (int)(($width - $minSide) / 2);
        $y = (int)(($height - $minSide) / 2);

        $thumb = imagecreatetruecolor($size, $size);
        
        // Handle PNG transparency
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        imagecopyresampled($thumb, $source, 0, 0, $x, $y, $size, $size, $minSide, $minSide);

        switch ($type) {
            case IMAGETYPE_JPEG: imagejpeg($thumb, $targetPath, 90); break;
            case IMAGETYPE_PNG: imagepng($thumb, $targetPath, 9); break;
        }

        imagedestroy($source);
        imagedestroy($thumb);
    }
}

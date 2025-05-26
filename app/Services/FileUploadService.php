<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
  /**
   * Upload a single file to the specified directory
   */
  public function uploadFile(UploadedFile $file, string $directory): string
  {
    return $file->store($directory, 'public');
  }

  /**
   * Upload multiple files to the specified directory
   */
  public function uploadFiles(array $files, string $directory): array
  {
    $uploadedFiles = [];

    foreach ($files as $file) {
      $uploadedFiles[] = $this->uploadFile($file, $directory);
    }

    return $uploadedFiles;
  }

  /**
   * Delete a single file
   */
  public function deleteFile(?string $filePath): void
  {
    if (empty($filePath)) {
      return;
    }

    Storage::disk('public')->delete($filePath);
  }

  /**
   * Delete multiple files
   */
  public function deleteFiles(array $filePaths): void
  {
    foreach ($filePaths as $filePath) {
      $this->deleteFile($filePath);
    }
  }

  /**
   * Replace old files with new ones (upload new, delete old)
   */
  public function replaceFiles(array $newFiles, array $oldFilePaths, string $directory): array
  {
    // Delete old files
    $this->deleteFiles($oldFilePaths);

    // Upload new files
    return $this->uploadFiles($newFiles, $directory);
  }

  /**
   * Replace a single file (upload new, delete old)
   */
  public function replaceFile(UploadedFile $newFile, ?string $oldFilePath, string $directory): string
  {
    // Delete old file if exists
    $this->deleteFile($oldFilePath);

    // Upload new file
    return $this->uploadFile($newFile, $directory);
  }

  /**
   * Get full URL for a file path
   */
  public function getFileUrl(?string $filePath): ?string
  {
    if (empty($filePath)) {
      return null;
    }

    return asset('storage/' . $filePath);
  }

  /**
   * Get full URLs for multiple file paths
   */
  public function getFileUrls(array $filePaths): array
  {
    return array_map(fn($path) => $this->getFileUrl($path), $filePaths);
  }
}

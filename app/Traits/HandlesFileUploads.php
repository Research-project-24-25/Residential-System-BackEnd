<?php

namespace App\Traits;

use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;

trait HandlesFileUploads
{
  protected function getFileUploadService(): FileUploadService
  {
    return app(FileUploadService::class);
  }

  /**
   * Handle single image upload for properties
   */
  protected function handlePropertyImages(array $images): array
  {
    return $this->getFileUploadService()->uploadFiles($images, 'property-images');
  }

  /**
   * Handle resident profile image upload
   */
  protected function handleResidentProfileImage(UploadedFile $image): string
  {
    return $this->getFileUploadService()->uploadFile($image, 'resident-images');
  }

  /**
   * Handle maintenance request images upload
   */
  protected function handleMaintenanceImages(array $images): array
  {
    return $this->getFileUploadService()->uploadFiles($images, 'maintenance-images');
  }

  /**
   * Handle meeting document upload
   */
  protected function handleMeetingDocument(UploadedFile $document): string
  {
    return $this->getFileUploadService()->uploadFile($document, 'meeting-documents');
  }

  /**
   * Remove old property images
   */
  protected function removePropertyImages(array $imagePaths): void
  {
    $this->getFileUploadService()->deleteFiles($imagePaths);
  }

  /**
   * Remove old resident profile image
   */
  protected function removeResidentProfileImage(?string $imagePath): void
  {
    $this->getFileUploadService()->deleteFile($imagePath);
  }

  /**
   * Remove old maintenance images
   */
  protected function removeMaintenanceImages(array $imagePaths): void
  {
    $this->getFileUploadService()->deleteFiles($imagePaths);
  }

  /**
   * Remove meeting document
   */
  protected function removeMeetingDocument(?string $documentPath): void
  {
    $this->getFileUploadService()->deleteFile($documentPath);
  }

  /**
   * Replace property images (delete old, upload new)
   */
  protected function replacePropertyImages(array $newImages, array $oldImagePaths): array
  {
    return $this->getFileUploadService()->replaceFiles($newImages, $oldImagePaths, 'property-images');
  }

  /**
   * Replace resident profile image (delete old, upload new)
   */
  protected function replaceResidentProfileImage(UploadedFile $newImage, ?string $oldImagePath): string
  {
    return $this->getFileUploadService()->replaceFile($newImage, $oldImagePath, 'resident-images');
  }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service to convert PDF shipping labels to ZPL format for thermal printers
 */
class LabelZPLConverter
{
    /**
     * Convert PDF label data to ZPL format
     * 
     * @param string $pdfData Base64 encoded PDF data or binary PDF data
     * @param int $dpi DPI for image conversion (default: 203 for 4x6 labels)
     * @return string ZPL commands
     */
    public function convertPdfToZpl(string $pdfData, int $dpi = 203): string
    {
        try {
            // Decode if base64
            if (base64_encode(base64_decode($pdfData, true)) === $pdfData) {
                $pdfData = base64_decode($pdfData);
            }

            // Convert PDF to image
            $imageData = $this->pdfToImage($pdfData, $dpi);
            
            if (!$imageData) {
                throw new \Exception('Failed to convert PDF to image');
            }

            // Convert image to ZPL
            $zpl = $this->imageToZpl($imageData, $dpi);

            return $zpl;
        } catch (\Exception $e) {
            Log::error('PDF to ZPL conversion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Convert PDF to image (PNG)
     * 
     * @param string $pdfData Binary PDF data
     * @param int $dpi DPI for rendering
     * @return string Binary PNG image data
     */
    private function pdfToImage(string $pdfData, int $dpi): ?string
    {
        // Try Imagick first (best quality)
        if (extension_loaded('imagick')) {
            return $this->pdfToImageImagick($pdfData, $dpi);
        }

        // Fallback: Use GD with PDF rendering (if available)
        if (function_exists('imagecreatefromstring')) {
            // For GD, we'd need to use a library like Smalot\PdfParser
            // For now, let's use a simpler approach with Imagick or fallback
            return $this->pdfToImageFallback($pdfData, $dpi);
        }

        throw new \Exception('No image processing library available (Imagick or GD required)');
    }

    /**
     * Convert PDF to image using Imagick
     */
    private function pdfToImageImagick(string $pdfData, int $dpi): ?string
    {
        try {
            // Save PDF to temporary file
            $tempPdf = tempnam(sys_get_temp_dir(), 'label_') . '.pdf';
            file_put_contents($tempPdf, $pdfData);

            $imagick = new \Imagick();
            $imagick->setResolution($dpi, $dpi);
            $imagick->readImage($tempPdf . '[0]'); // Read first page only
            $imagick->setImageFormat('png');
            $imagick->setImageCompressionQuality(100);
            
            // Get image data
            $imageData = $imagick->getImageBlob();
            
            $imagick->clear();
            $imagick->destroy();
            
            // Clean up temp file
            @unlink($tempPdf);

            return $imageData;
        } catch (\Exception $e) {
            Log::error('Imagick PDF conversion failed', ['error' => $e->getMessage()]);
            @unlink($tempPdf ?? '');
            return null;
        }
    }

    /**
     * Fallback PDF to image conversion
     * Uses a simpler approach if Imagick is not available
     */
    private function pdfToImageFallback(string $pdfData, int $dpi): ?string
    {
        // For now, return null and let the error be handled
        // In production, you might want to use a service like CloudConvert API
        // or install a PHP PDF library like Smalot\PdfParser
        throw new \Exception('Imagick extension required for PDF to ZPL conversion. Please install php-imagick extension.');
    }

    /**
     * Convert image to ZPL format
     * 
     * @param string $imageData Binary PNG image data
     * @param int $dpi DPI of the image
     * @return string ZPL commands
     */
    private function imageToZpl(string $imageData, int $dpi): string
    {
        // Create image resource from binary data
        $image = imagecreatefromstring($imageData);
        
        if (!$image) {
            throw new \Exception('Failed to create image from data');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Convert image to monochrome (black and white)
        $monochrome = imagecreatetruecolor($width, $height);
        imagecopy($monochrome, $image, 0, 0, 0, 0, $width, $height);
        
        // Convert to grayscale and apply threshold
        imagefilter($monochrome, IMG_FILTER_GRAYSCALE);
        imagefilter($monochrome, IMG_FILTER_CONTRAST, -100);
        imagefilter($monochrome, IMG_FILTER_BRIGHTNESS, -50);

        // Calculate bytes per row (8 pixels per byte for monochrome)
        $bytesPerRow = (int)ceil($width / 8);
        $totalBytes = $bytesPerRow * $height;

        // Start ZPL commands
        $zpl = "^XA\n"; // Start of label
        $zpl .= "^FO0,0\n"; // Field Origin (top-left corner)
        
        // Convert image to ZPL GRF (Graphic Field) format
        // Format: ^GFA,total_bytes,bytes_per_row,width,data
        $zpl .= "^GFA," . $totalBytes . "," . $bytesPerRow . "," . $width . ",";
        
        // Convert image to ZPL GRF data (ASCII hex)
        $grfData = $this->imageToZplGrf($monochrome, $width, $height, $bytesPerRow);
        $zpl .= $grfData;
        
        $zpl .= "^FS\n"; // Field Separator
        $zpl .= "^XZ\n"; // End of label

        imagedestroy($image);
        imagedestroy($monochrome);

        return $zpl;
    }

    /**
     * Convert image to ZPL GRF (Graphic Field) format
     * Converts image to 1-bit monochrome and encodes as ASCII hex
     */
    private function imageToZplGrf($image, int $width, int $height, int $bytesPerRow): string
    {
        $grfData = '';
        
        for ($y = 0; $y < $height; $y++) {
            $rowBytes = str_repeat("\0", $bytesPerRow);
            
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                
                // Convert to grayscale
                $gray = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
                
                // Threshold: black if gray < 128
                $isBlack = $gray < 128;
                
                if ($isBlack) {
                    $byteIndex = (int)floor($x / 8);
                    $bitIndex = 7 - ($x % 8);
                    $rowBytes[$byteIndex] = chr(ord($rowBytes[$byteIndex]) | (1 << $bitIndex));
                }
            }
            
            // Convert row bytes to ASCII hex
            for ($i = 0; $i < $bytesPerRow; $i++) {
                $grfData .= sprintf('%02X', ord($rowBytes[$i]));
            }
        }
        
        return $grfData;
    }
}

<?php

namespace App\Utils;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class S3Storage
{
    public $s3;
    public $bucket;

    public function __construct()
    {
        // Get credentials from environment variables
        $this->bucket = getenv('S3_BUCKET');
        $region = getenv('S3_REGION') ?: 'us-east-1';

        // Create S3 client
        $this->s3 = new S3Client([
            'version' => 'latest',
            'region'  => $region,
        ]);
    }

    /**
     * Upload a file to S3
     *
     * @param string $filePath Local file path
     * @param string $key S3 object key (path in bucket)
     * @return string The URL of the uploaded file
     * @throws \Exception
     */
    public function uploadFile($filePath, $key)
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$filePath}");
            }

            $result = $this->s3->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'Body'   => fopen($filePath, 'r'),
                'ACL'    => 'public-read',
            ]);

            return $result['ObjectURL'];
        } catch (S3Exception $e) {
            error_log("S3 Upload Error: " . $e->getMessage());
            throw new \Exception("Failed to upload file to S3: " . $e->getMessage());
        }
    }

    /**
     * Download a file from S3
     *
     * @param string $key S3 object key
     * @param string $savePath Local path to save file
     * @return bool Success flag
     * @throws \Exception
     */
    public function downloadFile($key, $savePath)
    {
        try {
            $result = $this->s3->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'SaveAs' => $savePath,
            ]);

            return true;
        } catch (S3Exception $e) {
            error_log("S3 Download Error: " . $e->getMessage());
            throw new \Exception("Failed to download file from S3: " . $e->getMessage());
        }
    }

    /**
     * Delete a file from S3
     *
     * @param string $key S3 object key
     * @return bool Success flag
     */
    public function deleteFile($key)
    {
        try {
            $this->s3->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);

            return true;
        } catch (S3Exception $e) {
            error_log("S3 Delete Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if file exists in S3
     *
     * @param string $key S3 object key
     * @return bool
     */
    public function fileExists($key)
    {
        try {
            return $this->s3->doesObjectExist($this->bucket, $key);
        } catch (S3Exception $e) {
            error_log("S3 Check Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a pre-signed URL for an object (temporary download URL)
     *
     * @param string $key S3 object key
     * @param int $expires URL expiration time in seconds (default 1 hour)
     * @return string Presigned URL
     */
    public function getPresignedUrl($key, $expires = 3600)
    {
        try {
            $command = $this->s3->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key'    => $key
            ]);

            $request = $this->s3->createPresignedRequest($command, "+{$expires} seconds");
            return (string) $request->getUri();
        } catch (S3Exception $e) {
            error_log("S3 Presigned URL Error: " . $e->getMessage());
            return null;
        }
    }
}

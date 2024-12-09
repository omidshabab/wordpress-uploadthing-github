<?php
/*
Plugin Name: Uploadthing Integration
Plugin URI: https://omidshabab.com
Description: Integrates Uploadthing for WordPress media handling
Version: 1.0
Author: Omid Shabab
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
     exit;
}

class UploadthingIntegration
{
     private $uploadthing_secret;
     private $uploadthing_app_id;

     public function __construct()
     {
          $this->uploadthing_secret = getenv('UPLOADTHING_SECRET');
          $this->uploadthing_app_id = getenv('UPLOADTHING_APP_ID');

          add_filter('upload_dir', array($this, 'modify_upload_dir'));
          add_filter('wp_handle_upload', array($this, 'handle_upload'));
     }

     public function modify_upload_dir($uploads)
     {
          // Modify the upload directory to use Uploadthing
          $uploads['baseurl'] = 'https://uploadthing.com/f/' . $this->uploadthing_app_id;
          $uploads['basedir'] = sys_get_temp_dir(); // Temporary directory for processing
          return $uploads;
     }

     public function handle_upload($upload)
     {
          // Initialize cURL
          $curl = curl_init();

          // Prepare file for upload
          $file_path = $upload['file'];
          $file_type = $upload['type'];

          // Set cURL options
          curl_setopt_array($curl, array(
               CURLOPT_URL => 'https://uploadthing.com/api/uploadFiles',
               CURLOPT_RETURNTRANSFER => true,
               CURLOPT_POST => true,
               CURLOPT_HTTPHEADER => array(
                    'X-Uploadthing-Api-Key: ' . $this->uploadthing_secret,
                    'Content-Type: multipart/form-data'
               ),
               CURLOPT_POSTFIELDS => array(
                    'file' => new CURLFile($file_path, $file_type)
               )
          ));

          // Execute upload
          $response = curl_exec($curl);
          $err = curl_error($curl);
          curl_close($curl);

          if ($err) {
               return new WP_Error('uploadthing_error', 'Failed to upload file to Uploadthing');
          }

          $result = json_decode($response, true);

          // Update the file URL to point to Uploadthing
          if (isset($result['fileUrl'])) {
               $upload['url'] = $result['fileUrl'];
          }

          return $upload;
     }
}

// Initialize the plugin
new UploadthingIntegration();

#!/usr/bin/env php
<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Example script for performing a backup and immediate restore of a site through the ACSF REST API.
// Two things are left up to the script user:
// - Including Guzzle, which is used by request();
//   e.g. by doing: 'composer init; composer require guzzlehttp/guzzle'
require 'vendor/autoload.php';

// Set config for each ACSF environment...
$config_dev = [
  // URL of a subsection inside the SF REST API; must end with sites/.
  'url' => '<to do>',
  'api_user' => '<to do>',
  'api_key' => '<to do>',
  'callback_url' => '<to do>'
];

$config_test = [
  // URL of a subsection inside the SF REST API; must end with sites/.
  'url' => '<to do>/',
  'api_user' => '<to do>',
  'api_key' => '<to do>',
  'callback_url' => '<to do>'
];

$config_prod = [
  // URL of a subsection inside the SF REST API; must end with sites/.
  'url' => '<to do>',
  'api_user' => '<to do>',
  'api_key' => '<to do>',
  'callback_url' => '<to do>'
];

// set the env config...
$config = $config_dev;

// Populate with the site ID to backup
$site_id = 1;

// Create a backup task...
$body = create_backup($site_id,$config);

// Check task was created...
check_task_created($body);

// Poll for a Completed status
check_task_status($body->task_id, $config);

// Get most recent backup
$backup_id = get_backup_list($site_id,$config);

// Initite a restore task
$body = restore_site_from_backup($site_id,$backup_id,$config);

// Check task was created...
check_task_created($body);

// Poll for a Completed status
check_task_status($body->task_id, $config);

// Restore a site from a backup_id
function restore_site_from_backup($site_id,$backup_id,$config) {

  echo 'Restoring backup ID: '. $backup_id .' for site ID: ' . $site_id, PHP_EOL;
  $url = $config["url"]."sites/".$site_id."/restore";
  $method = "POST";

  $caller_data = [
        'authuser' => 'authuser',
        'authkey' => 'authkey',
        'operation' => 'restore',
        'site_id' => $site_id,
        'status' => 'Success'
  ];

  // Populate callback_url with you own webhook endpoint...
  $form_params = [
    'target_site_id' => $site_id,
    'backup_id' =>$backup_id,
    'components' => ['database'],
    'callback_url' => $config["callback_url"],
    'callback_method' => 'POST',
    'caller_data' => json_encode($caller_data)
  ];

  $res = request($url, $method, $config, $form_params);
  $body = json_decode($res->getBody()->getContents());

  return $body;
}

// Get a list of backups for a site
function get_backup_list($site_id,$config) {

  echo 'Getting a list of backups for site ID: ', $site_id, PHP_EOL;
  $url = $config["url"]."sites/".$site_id."/backups";
  $method = "GET";
  $res = request($url, $method, $config);
  $body = json_decode($res->getBody()->getContents());

  print_r($body->backups);

  // Use the most recent backup....
  $backup_id = $body->backups[0]->id;

  return $backup_id;
}

// Create a backup....
function create_backup($site_id,$config) {

  // Create a backup...
  echo "Creating backup for site ID: ", $site_id , PHP_EOL;
  $url = $config["url"]."sites/".$site_id."/backup";
  $method = "POST";
  $message = "Creating pre MDS update backup for site ID: ".$site_id;
  $caller_data = json_encode(array(
        'authuser' => 'authuser',
        'authkey' => 'authkey',
        'operation' => 'backup',
        'site_id' => $site_id,
        'status' => 'Success')
  );

  // Populate callback_url with you own webhook endpoint...
  $form_params = [
    'label' => $message,
    'components' => ['database'],
    'callback_url' => $config["callback_url"],
    'callback_method' => 'POST',
    'caller_data' => $caller_data
  ];

  $res = request($url, $method, $config, $form_params);
  $body = json_decode($res->getBody()->getContents());

  return $body;
}


// Check task status...
function check_task_status($task_id, $config) {

  // try 1000 times before failing....
  $retryCount = 1000;
  $time_pre = microtime(true);

  echo 'Checking status of task ID: ', $task_id, PHP_EOL;
  do {
      sleep(10);

      // Poll for backup progress
      $url = $config["url"]."wip/task/".$task_id."/status";
      $method = "GET";
      $res = request($url, $method, $config);
      $body = json_decode($res->getBody()->getContents());

      $status_colour = ($body->wip_task->status_string == "Completed") ? "32m" : "33m"; 
      $status_colour = ($body->wip_task->status_string == "In Progress") ? "93m" : "33m"; 

      echo "Current task status: \033[". $status_colour . $body->wip_task->status_string . "\033[0m",PHP_EOL;

      if ($body->wip_task->status_string === 'Completed') {
          $time_post = microtime(true);
          $exec_time = $time_post - $time_pre;
          echo "\033[32mSuccessfully created/restored backup in " . number_format($exec_time,2) . "ms.\033[0m", PHP_EOL;
          $retry = FALSE;
      } else {
          echo 'Retrying notification in 10 sec', PHP_EOL;
          $retryCount--;
          $retry = $retryCount > 0;
      }
  } while ($retry);

  return NULL;
}

// Helper function to return API user and key.
function get_request_auth($config) {
  return [
    'auth' => [$config['api_user'], $config['api_key']],
  ];
}

// Helper function to check task creation.
function check_task_created($body) {
// Check task was successfully created...
  if(!isset($body->task_id)) {
    echo "\033[31mError: " . $body->message . "\033[0m", PHP_EOL;
    exit();
  } else {
    echo "\033[32mTask ID: " . $body->task_id . " successfully created. \033[0m", PHP_EOL;
  }
}

// Sends a request using the guzzle HTTP library; prints out any errors.
function request($url, $method, $config, $form_params = []) {
  // We are setting http_errors => FALSE so that we can handle them ourselves.
  // Otherwise, we cannot differentiate between different HTTP status codes
  // since all 40X codes will just throw a ClientError exception.
  $client = new Client(['http_errors' => FALSE]);

  $parameters = get_request_auth($config);
  if ($form_params) {
    $parameters['form_params'] = $form_params;
  }

  try {
    $res = $client->request($method, $url, $parameters);
    return $res;
  }
  catch (RequestException $e) {
    printf("Request exception!\nError message %s\n", $e->getMessage());
  }

  return NULL;
}

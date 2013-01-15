<?php
/**
 * @file
 * Add watermarks to images.
 */

// ImageMagick is required to process images.
if (!extension_loaded('imagick')) {
  echo renderMsg('error', array(
    'heading' => 'ImageMagick is not installed!',
    'body' => 'Images cannot be processed without ImageMagick.',
  ));
  return;
}

// Connect to Amazon Simple Queue Service.
try {
  $sqs = new AmazonSQS();
}
catch (Exception $e) {
  echo renderMsg('error', array(
    'heading' => 'Unable to connect to Amazon Simple Queue Service!',
    'body' => var_export($e->getMessage(), TRUE),
  ));
  return;
}

$queue_url = getAwsSqsQueueUrl($sqs, UARWAWS_SQS_QUEUE);
$received_sqs_response = $sqs->receive_message($queue_url, array(
  'MaxNumberOfMessages' => 1,
));

if (!$received_sqs_response->isOK()) {
  echo renderMsg('error', array(
    'heading' => 'Unable to get messages from SQS queue!',
    'body' => getAwsError($received_sqs_response),
  ));
  return;
}

$image_filename = (string) $received_sqs_response->body->ReceiveMessageResult->Message->Body;

if (!$image_filename) {
  echo renderMsg('info', array(
    'heading' => 'No images to process.',
    'body' => 'Upload images that need watermarking before processing the queue.',
  ));
  return;
}
else {
  echo renderMsg('info', "Processing $image_filename");
}

// Get the receipt handle; required when deleting a message.
$receipthandle = (string) $received_sqs_response->body->ReceiveMessageResult->Message->ReceiptHandle;
echo renderMsg('info', array(
  'heading' => 'ReceiptHandle:',
  'body' => substr($receipthandle, 0, 80) . ' ...',
));

// Connect to Amazon S3.
try {
  $s3 = new AmazonS3();
}
catch (Exception $e) {
  echo renderMsg('error', array(
    'heading' => 'Unable to connect to Amazon S3!',
    'body' => var_export($e->getMessage(), TRUE),
  ));
  return;
}

// Create temporary file based on original filename.
$file_name_array = explode('.', $image_filename);
$temporary_file_name = tempnam(sys_get_temp_dir(), array_pop($file_name_array));

// Download image from S3 for watermarking.
$file_resource = fopen($temporary_file_name, 'w+');
$s3_get_response = $s3->get_object(UARWAWS_S3_BUCKET, $image_filename, array(
  'fileDownload' => $temporary_file_name,
));
fclose($file_resource);

if ($s3_get_response->isOK()) {
  echo renderMsg('success', array(
    'body' => 'Downloaded image from Amazon S3.',
  ));
}
else {
  echo renderMsg('error', array(
    'heading' => 'Unable to download image from Amazon S3!',
    'body' => getAwsError($s3_get_response),
  ));
  return;
}

// Get the content type of the file; required to upload watermarked image to S3.
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$content_type = finfo_file($finfo, $temporary_file_name);
finfo_close($finfo);

// Attempt to watermark image.
try {
  $image_to_be_watermarked = new Imagick($temporary_file_name);
}
catch (Exception $e) {
  echo renderMsg('error', array(
    'heading' => 'Unable to read processed file as an image!',
    'body' => var_export($e->getMessage(), TRUE),
  ));
  return;
}
addWatermark($image_to_be_watermarked);
$image_to_be_watermarked->writeImages($temporary_file_name, TRUE);

// Upload watermarked image to S3.
$s3_create_response = $s3->create_object(UARWAWS_S3_BUCKET, $image_filename, array(
  'fileUpload' => $temporary_file_name,
  'contentType' => $content_type,
  'acl' => AmazonS3::ACL_PUBLIC,
));

if ($s3_create_response->isOK()) {
  echo renderMsg('success', array(
    'body' => 'Uploaded watermarked image to Amazon S3.',
  ));
}
else {
  echo renderMsg('error', array(
    'heading' => 'Unable to upload watermarked image to Amazon S3!',
    'body' => getAwsError($s3_create_response),
  ));
  return;
}

// Image has been processed; delete from SQS queue.
$sqs_delete_response = $sqs->delete_message($queue_url, $receipthandle);
if ($sqs_delete_response->isOK()) {
  echo renderMsg('success', array(
    'body' => 'Deleted message from queue.',
  ));
}
else {
  echo renderMsg('error', array(
    'heading' => 'Unable to delete message from queue!',
    'body' => getAwsError($sqs_delete_response),
  ));
  return;
}

// Connect to Amazon SimpleDB.
try {
  $sdb = new AmazonSDB();
}
catch (Exception $e) {
  echo renderMsg('error', array(
    'heading' => 'Unable to connect to Amazon SimpleDB!',
    'body' => var_export($e->getMessage(), TRUE),
  ));
  return;
}

// Update SimpleDB item to reflect that image has been watermarked.
$keypairs = array(
  'watermark' => 'y',
);
$sdb_put_response = $sdb->put_attributes(UARWAWS_SDB_DOMAIN, $image_filename, $keypairs);
if ($sdb_put_response->isOK()) {
  echo renderMsg('success', array(
    'body' => 'Item updated in Amazon SimpleDB.',
  ));
}
else {
  echo renderMsg('error', array(
    'heading' => 'Unable to update item in Amazon SimpleDB!',
    'body' => getAwsError($sdb_put_response),
  ));
  return;
}

// Connect to Amazon CloudWatch.
try {
  $cw = new AmazonCloudWatch();
}
catch (Exception $e) {
  echo renderMsg('error', array(
    'heading' => 'Unable to connect to Amazon CloudWatch Service!',
    'body' => var_export($e->getMessage(), TRUE),
  ));
  return;
}

// Processed file count metric.
$cw_put_metric_response = $cw->put_metric_data('Watermark', array(
  array(
    'MetricName' => 'ProcessedFiles',
    'Unit' => 'Count',
    'Value' => 1,
  ),
));

if ($cw_put_metric_response->isOK()) {
  echo renderMsg('success', array(
    'body' => 'Processed file metric added to CloudWatch.',
  ));
}
else {
  echo renderMsg('error', array(
    'heading' => 'Unable to update process file count with CloudWatch',
    'body' => getAwsError($cw_put_metric_response),
  ));
  return;
}

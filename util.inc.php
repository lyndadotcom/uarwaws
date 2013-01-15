<?php
/**
 * @file
 * Utility functions.
 */

/**
 * Extract error from CFSimpleXML response.
 *
 * @param CFSimpleXML $response
 * @return string
 */
function getAwsError($response) {
  $message = '';
	if (isset($response->body)) {
    if (isset($response->body->Errors->Error)) {
      $error = $response->body->Errors->Error;
      if (isset($error->Code)) {
        $message .= $error->Code . ': ';
      }
      $message .= $error->Message;
    }
    else if (isset($response->body->Message)) {
      if (isset($response->body->Code)) {
        $message .= $response->body->Code . ': ';
      }
      $message .= $response->body->Message;
    }
  }
	return $message;
}

/**
 * Get queue full URL.
 *
 * @param AmazonSQS $sqs
 * @param string $queue_name
 * @return string
 */
function getAwsSqsQueueUrl($sqs, $queue_name) {
  $sqs_queue_url = $sqs->get_queue_url($queue_name);
  $queue_url = (string) $sqs_queue_url->body->GetQueueUrlResult->QueueUrl;
  return $queue_url;
}

/**
 * Render an HTML message.
 *
 * @param string $type
 *   Message type; error, ok, question, warning, info.
 * @param mixed $content
 *   string - The contents of the message.
 *   array - Keys heading, body.
 *
 * @return string
 *   HTML message.
 */
function renderMsg($type, $content) {
  switch ($type) {
    case 'error': {
      $heading = 'Error:';
      break;
    }
    case 'success': {
      $heading = 'Success:';
      break;
    }
    case 'info': {
      $heading = 'Information:';
      break;
    }
  }
  if (is_array($content)) {
    if (isset($content['heading'])) {
      $heading = $content['heading'];
    }
    if (isset($content['body'])) {
      $body = $content['body'];
    }
  }
  else {
    $body = $content;
  }
  $html = '<div class="alert alert-' . $type . '">';
  if ('' != $heading) {
    $html .= '<strong>' . $heading . '</strong> ';
  }
  $html .= $body;
  $html .= '</div>';
  return $html;
}

/**
 * Add a watermark to an image passed by reference.
 * 
 * @param Imagick $image
 */
function addWatermark(&$image) {
  $watermark = new Imagick();
  $watermark->readImage('img/watermarked.png');

  $image_width = $image->getImageWidth();
  $image_height = $image->getImageHeight();

  $watermark_width = $watermark->getImageWidth();
  $watermark_height = $watermark->getImageHeight();

  // Scale watermark width, but only if needed.
  if ($image_width < $watermark_width) {
    $watermark->scaleImage($image_width, 0);
    // New watermark size.
    $watermark_width = $watermark->getImageWidth();
    $watermark_height = $watermark->getImageHeight();
  }

  // Scale watermark height, but only if needed.
  if ($image_height < $watermark_height) {
    $watermark->scaleImage(0, $image_height);
    // New watermark size.
    $watermark_width = $watermark->getImageWidth();
    $watermark_height = $watermark->getImageHeight();
  }

  // Determine watermark position.
  $x = ($image_width - $watermark_width) / 2;
  $y = ($image_height - $watermark_height) / 2;
  $image->compositeImage($watermark, imagick::COMPOSITE_OVER, $x, $y);
}

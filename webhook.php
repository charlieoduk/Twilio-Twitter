<?php

require __DIR__ . "/vendor/autoload.php";
use Twilio\Rest\Client as TwilioClient;

$ini_file        = parse_ini_file( 'app.ini' );
$consumer_secret = $ini_file[ "consumer_secret" ];
$screen_name     = $ini_file[ "screen_name" ];


if( $_SERVER[ 'REQUEST_METHOD' ] === 'GET' && isset( $_GET[ 'crc_token' ] ) ) {
  $signature = hash_hmac( 'sha256', $_REQUEST[ 'crc_token' ], $consumer_secret, true );
  $response['response_token'] = 'sha256='.base64_encode( $signature );
  echo json_encode( $response );
}

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
  // Retrieve the request's body and parse it as JSON:
  $input = file_get_contents('php://input');
  $event = json_decode($input);

  if ( $event->tweet_create_events ) {
    $event_details = $event->tweet_create_events[0];
    $event_triggered_by = $event_details->user->screen_name;

    process_event_details( $event_details, $event_triggered_by );
  }
}

/**
 * Process event details to determine what event was triggered
 * 
 * @param object $event_details      - event object
 * @param string $event_triggered_by - name of the person who triggered the event
 * 
 * @return void
 */
function process_event_details( $event_details, $event_triggered_by ) {

  global $screen_name;

  if ( $event_details->in_reply_to_status_id ) {
    $reply_text = str_replace( "@".$screen_name, "", $event_details->text );
    $message    = "@".$event_triggered_by. " replied to your tweet: '".$reply_text."'";
    send_sms($message);

  } elseif ( ( strpos( $event_details->text, 'RT @'.$screen_name ) !== false ) ) {
    $message = "@".$event_triggered_by. " retweeted : ".$event_details->text;
    send_sms( $message );

  } elseif ( ( strpos( $event_details->text, '@'.$screen_name ) !== false ) ) {
    $message = "@".$event_triggered_by. " mentioned you in their tweet: '".$event_details->text."'";
    send_sms( $message );
  }
}

/**
 * Send an SMS about an event 
 * 
 * @param string $message - message corresponding to an event
 * 
 * @return void
 */
function send_sms( $message )
{
  global $ini_file;
  $twilio_sid = $ini_file["twilio_sid"];
  $twilio_token = $ini_file["twilio_token"];

  $twilio = new TwilioClient( $twilio_sid, $twilio_token );

  $my_twilio_number = $ini_file["twilio_number"];

  $twilio->messages->create(
      // Where to send a text message
      "YOUR NUMBER",
      array(
          "from" => $my_twilio_number,
          "body" => $message
      )
  );
}

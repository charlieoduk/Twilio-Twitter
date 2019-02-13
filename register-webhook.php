<?php

require __DIR__ . "/vendor/autoload.php";

use Abraham\TwitterOAuth\TwitterOAuth;

$ini_file = parse_ini_file("app.ini");

$access_token         = $ini_file[ "access_token" ];
$access_token_secret  = $ini_file[ "access_token_secret" ];
$consumer_key         = $ini_file[ "consumer_key" ];
$consumer_secret      = $ini_file[ "consumer_secret" ];

/* Create a TwitterOauth object */
$twitter_connection   = new TwitterOAuth( $consumer_key, $consumer_secret, $access_token, $access_token_secret);
$webhook_url          = "https://c8a7320f.ngrok.io/webhook.php";

/* Remove any previous webhooks */
$existing_webhooks    = $twitter_connection->get( 'account_activity/all/development/webhooks' );

foreach ( $existing_webhooks as $webhook ) {
   $twitter_connection->delete( 'account_activity/all/development/webhooks/' . $webhook->id );
}

$content              = $twitter_connection->post("account_activity/all/development/webhooks", [ "url" => $webhook_url ]);


if ( $content->id ) {
    // subscribe user to our app
    $content = $twitter_connection->post( "account_activity/all/development/subscriptions" );
    echo "Successfully registered the webhook ".$webhook_url." and subscribed yourself to your Twitter app.";
} else {
    echo ( $content->errors[0]->message );
}

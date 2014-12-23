<?php

require 'config.php';

// make sure the URL contains the secret identifier 
// and check charge in the Stripe API
// to ensure the request is coming from Stripe
if ($verification == "secret" || $verification == "both") {
    if ($_GET['secret'] != $secret) {
        die('Invalid secret identifier');
    }
}

// parse the Stripe webhook request
$input = @file_get_contents("php://input");
$event_json = json_decode($input);

// for extra security, optionally retrieve from the Stripe API
if ($verification == "API" || $verification == "both") {
    $event_id = $event_json->id;
    $event = getStripeEvent($event_id, $stripe['secret_key']);
}
// if we don't go to stripe, just use the supplied json
else {
    $event = $event_json;
}

// we only care about successful payments
if ($event->type != 'charge.succeeded') {
    die('Not a charge notification. Quitting.');
};

// grab a random gif
$url = 'https://fitztrev.github.io/make-it-rain/gifs.json';
$gifs = json_decode(file_get_contents($url));
$gifs = array_merge($gifs, $custom_gifs);
$gif = $gifs[array_rand($gifs)];

// get the dollar amount
$amount = number_format($event->data->object->amount / 100, 2);

// send the notification to our chat room
// hipchat
if ($hipchat['auth_token']) {
    $url = sprintf(
        'https://api.hipchat.com/v2/room/%s/notification?auth_token=%s',
        $hipchat['room_id'],
        $hipchat['auth_token']
    );
    $data = array(
        'message' => sprintf('$%s <br><img src="%s">', $amount, $gif),
        'color'   => 'green',
        'notify'  => true,
    );
    sendNotification($url, $data);
}

// slack
if ($slack['webhook_url']) {
    $data = array(
        'text'         => sprintf('$%s - <%s>', $amount, $gif),
        'username'     => 'Just got paid',
        'icon_emoji'   => ':heavy_dollar_sign:',
        'unfurl_links' => true,
    );
    sendNotification($slack['webhook_url'], $data);
}

function sendNotification($url, $data)
{
    $data_string = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: '.strlen($data_string),
        )
    );

    // send the request and close the handle
    $body = curl_exec($ch);
    curl_close($ch);
}

function getStripeEvent($id, $secret_key) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/events/{$id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$secret_key:");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $output = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return json_decode($output);
}


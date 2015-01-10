<?php

require 'config.php';

// make sure the URL contains the secret identifier
// to ensure the request is coming from Stripe
if ($_GET['secret'] != $secret) {
    die('Invalid secret identifier');
}

// parse the Stripe webhook request
$input = @file_get_contents("php://input");
$event_json = json_decode($input);

// we only care about successful payments
if ($event_json->type != 'charge.succeeded') {
    die('Not a charge notification. Quitting.');
};

// grab a random gif
$url = 'https://fitztrev.github.io/make-it-rain/gifs.json';
$gifs = json_decode(file_get_contents($url));
$gifs = array_merge($gifs, $custom_gifs);
$gif = $gifs[array_rand($gifs)];

// get currency sign to use (right now, just USD and INR supported. More to follow? **cough**pullrequest**cough**)
$dollar_sign = ($event_json->data->object->currency == 'inr') ? '&#x20b9;' : '$';
$amount = $dollar_sign . number_format($event_json->data->object->amount / 100, 2);

// send the notification to our chat room
// hipchat
if ($hipchat['auth_token']) {
    $url = sprintf(
        'https://api.hipchat.com/v2/room/%s/notification?auth_token=%s',
        $hipchat['room_id'],
        $hipchat['auth_token']
    );
    $data = array(
        'message' => sprintf('%s <br><img src="%s">', $amount, $gif),
        'color'   => 'green',
        'notify'  => true,
    );
    sendNotification($url, $data);
}

// slack
if ($slack['webhook_url']) {
    $data = array(
        'text'         => sprintf('%s - <%s>', $amount, $gif),
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

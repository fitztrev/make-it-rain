# Make It Rain

Get a random "make it rain" gif every time you get paid through Stripe.

Works with HipChat + Slack.

![screenshot](https://i.imgur.com/gU7gFBW.gif)

## How to use

1) Copy `config.sample.php` to `config.php`

2) Update it with either your Hipchat or Slack API info (see below). Optionally, add your Stripe API key if you wish to verify your webhooks with Stripe.

3) Add a webhook to [your Stripe account](https://dashboard.stripe.com/account/webhooks)

    https://YOUR_SITE_HERE.com/make-it-rain/webhook.php?secret=abc123

* Your `secret` is set in `config.php` and known only to Stripe so nobody can ping that URL and give you a false notification.

#### For Hipchat

1. Go to <https://hipchat.com/admin/rooms>
2. Click your room and copy "API ID" to the channel setting in `config.php`
3. In "Tokens" for that room, create a token with label "Just got paid" and copy it to `config.php`

#### For Slack

1. Go to <https://my.slack.com/services/new/incoming-webhook>
2. Create a webhook for your desired channel
3. Copy the webhook URL to `config.php`

#### Webhook verification

It's important to secure your webhook endpoints so that a malicious attacker cannot send false notifications.

`verification` is a variable that can be set to one of the following:

* `secret` (default) to verify webhooks only with your secret
* `API` to verify webhooks by checking Stripe's API for the charge
* `both` to validate both of the above.

## Contributing gifs

New gifs are welcomed and encouraged. Check the [existing ones](https://github.com/fitztrev/make-it-rain/tree/gh-pages), grab the [`gh-pages`](https://github.com/fitztrev/make-it-rain/tree/gh-pages) branch of this repo, and submit a pull request to add one.

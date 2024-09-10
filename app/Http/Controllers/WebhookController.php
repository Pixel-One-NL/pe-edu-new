<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function eduframe(Request $request)
    {
        Log::info('Received webhook from Eduframe', $request->all());
        Log::debug('Webhook headers:', $request->headers->all());

        $eduframeSignature = $request->header('eduframe-signature-v1');
        $payload           = $request->getContent();

        $primaryWebhookSecret   = env('EDUFRAME_PRIMARY_WEBHOOK_SECRET');
        $secondaryWebhookSecret = env('EDUFRAME_SECONDARY_WEBHOOK_SECRET');

        /**
         * This is what a webhook header looks like:
         * 
         * eduframe-signature-v1:
         *   t = 1717747771,
         *   signature = 735b82e1e24769d02a39548f3bcc96ebd55f61b24a799fa98b786b53e56a1295,
         *   signature = d21d5148bc92ffa9334f2800720083c8073f68160c45f24e52061dccf76dd005
         * 
         * We need to verify the signature to make sure the webhook is coming from Eduframe.
         */

        $webhookTimestamp   = str_replace(' ', '', str_replace('t=', '', explode(',', $eduframeSignature)[0]));
        $primarySignature   = str_replace(' ', '', str_replace('signature=', '', explode(',', $eduframeSignature)[1]));
        $secondarySignature = str_replace(' ', '', str_replace('signature=', '', explode(',', $eduframeSignature)[2]));

        Log::debug('Webhook timestamp:' . $webhookTimestamp);
        Log::debug('Primary signature:' . $primarySignature);
        Log::debug('Secondary signature:' . $secondarySignature);

        $primarySignatureIsValid   = hash_equals($primarySignature, hash_hmac('sha256', $webhookTimestamp . '.' . $payload, $primaryWebhookSecret));
        $secondarySignatureIsValid = hash_equals($secondarySignature, hash_hmac('sha256', $webhookTimestamp . '.' . $payload, $secondaryWebhookSecret));

        Log::debug('Primary signature is valid: ' . ($primarySignatureIsValid ? 'yes' : 'no'));
        Log::debug('Secondary signature is valid: ' . ($secondarySignatureIsValid ? 'yes' : 'no'));

        if ($primarySignatureIsValid && $secondarySignatureIsValid) {
            Log::info('Webhook signature is valid');
            // Do something with the payload
        } else {
            Log::error('Webhook signature is invalid');
        }
    }
}

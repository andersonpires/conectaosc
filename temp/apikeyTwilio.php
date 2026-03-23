<?php
require_once dirname(__DIR__) . '/bootstrap/runtime.php';

$twilioAccountSid = bootstrap_env('TWILIO_ACCOUNT_SID', '');
$twilioAuthToken = bootstrap_env('TWILIO_AUTH_TOKEN', '');
$apikeyTwilio = bootstrap_env('TWILIO_API_CREDENTIALS', '');

if ($apikeyTwilio === '' && $twilioAccountSid !== '' && $twilioAuthToken !== '') {
    $apikeyTwilio = $twilioAccountSid . ':' . $twilioAuthToken;
}

<?php
header('Content-Type: application/json');

$supabase_url = 'https://ownjmawswuygfhltlzts.supabase.co';
$supabase_key = 'sb_publishable_5ceuA5WElQ_dB31Oddj1bg_Pa-7uFZz';

$ch = curl_init($supabase_url . "/rest/v1/ventas?select=*");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $supabase_key",
    "Authorization: Bearer $supabase_key"
]);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>

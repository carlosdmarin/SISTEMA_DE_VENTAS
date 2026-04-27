<?php
header('Content-Type: application/json');

$supabase_url = 'https://ownjmawswuygfhltlzts.supabase.co';
$supabase_key = 'sb_publishable_5ceuA5WElQ_dB31Oddj1bg_Pa-7uFZz';

$ch = curl_init($supabase_url . "/rest/v1/ventas?select=*&limit=5");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $supabase_key",
    "Authorization: Bearer $supabase_key"
]);
$respuesta = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo json_encode([
    'http_code' => $http_code,
    'datos' => json_decode($respuesta),
    'error' => $http_code !== 200 ? $respuesta : null
]);
?>

<?php
$supabase_url = getenv('SUPABASE_URL');
$supabase_key = getenv('SUPABASE_KEY');

echo "URL: " . ($supabase_url ? "✅ OK" : "❌ FALTA") . "<br>";
echo "KEY: " . ($supabase_key ? "✅ OK" : "❌ FALTA") . "<br><br>";

if ($supabase_url && $supabase_key) {
    $ch = curl_init($supabase_url . "/rest/v1/ventas?limit=1");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key"
    ]);
    $respuesta = curl_exec($ch);
    echo "Respuesta de Supabase: " . $respuesta;
    curl_close($ch);
} else {
    echo "❌ Las variables no están configuradas en Render";
}
?>

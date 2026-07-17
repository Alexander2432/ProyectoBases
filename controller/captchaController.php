<?php

session_start();
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

$alfabeto = "ABCDEFGHJKMNPQRSTUVWXYZ23456789";

$codigo = "";
for ($i = 0; $i < 5; $i++) {
    $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
}

$_SESSION["captcha_resultado"] = $codigo;
$_SESSION["captcha_generado"]  = time();

echo json_encode([
    "codigo" => str_split($codigo),
]);

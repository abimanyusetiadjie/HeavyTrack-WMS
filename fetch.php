<?php
$html = file_get_contents('https://heavytrack-wms-production.up.railway.app/admin/login');
preg_match_all('/<link[^>]+href="([^"]+)"/i', $html, $matches);
print_r($matches[1]);

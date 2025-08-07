<?php
use Hashids\Hashids;


// ใช้ค่า salt จาก .env
$salt = $_ENV['HASHIDS_SALT'] ?? 'default-salt';
$hashids = new Hashids($salt, 30);

/**
 * เข้ารหัส ID
 */
function encodeId($id) {
    global $hashids;
    return $hashids->encode($id);
}

/**
 * ถอดรหัส ID
 */
function decodeId($hashedId) {
    global $hashids;
    $decoded = $hashids->decode($hashedId);
    return !empty($decoded) ? $decoded[0] : null;
}

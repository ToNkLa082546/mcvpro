<?php
require_once realpath(__DIR__ . '/../vendor/autoload.php');


$dotenv = Dotenv\Dotenv::createImmutable(realpath(__DIR__ . '/../'));
$dotenv->load();


date_default_timezone_set('Asia/Bangkok');

// เปิดใช้งาน session ที่ไฟล์นี้ไฟล์เดียว
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// เปิดการแสดงผล Error ทั้งหมด (สำหรับตอนพัฒนาเท่านั้น)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// กำหนด ROOT_PATH เพื่อให้การเรียกไฟล์ในที่ต่างๆ ง่ายขึ้น
define('ROOT_PATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);

// โหลดไฟล์เชื่อมต่อฐานข้อมูล ซึ่งจะสร้างตัวแปร $pdo
require_once ROOT_PATH . 'core/connect.php';
require_once ROOT_PATH . 'app/helpers/hash_helper.php';

// =====================================================================
// === Autoloader ที่สมบูรณ์ ===
// =====================================================================
spl_autoload_register(function ($className) {
    $baseDir = ROOT_PATH;
    $file = null;
    $classNameWithExt = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

    // รายการ Path ที่จะค้นหา
    $paths = [
        $baseDir . 'core/' . strtolower($className) . '.php',
        $baseDir . 'app/models/' . $classNameWithExt,
        $baseDir . 'app/services/' . $classNameWithExt,
        $baseDir . 'app/controllers/' . $classNameWithExt,
    ];
    
    // เพิ่ม Path สำหรับ Controller ในโฟลเดอร์ย่อย
    $controllerSubFolders = ['auth', 'admin', 'customer', 'project', 'home', 'profile','quotations', 'users','activities'];
    foreach ($controllerSubFolders as $folder) {
        $paths[] = $baseDir . 'app/controllers/' . $folder . '/' . $classNameWithExt;
    }

    // วนลูปหาไฟล์
    foreach ($paths as $path) {
        if (file_exists($path)) {
            $file = $path;
            break;
        }
    }

    if ($file) {
        require_once $file;
    }
});


// =====================================================================
// === Router Logic ===
// =====================================================================
$controllerName = 'LoginController';
$methodName = 'index';
$params = [];

$requestUri = trim(strtok($_SERVER['REQUEST_URI'], '?'), '/');
$basePath = 'mcvpro/public';
$path = '';

if (strpos($requestUri, $basePath) === 0) {
    $path = substr($requestUri, strlen($basePath));
}
$path = trim($path, '/');

if (!empty($path)) {
    $urlParts = explode('/', $path);
    $firstSegment = pathinfo($urlParts[0], PATHINFO_FILENAME);

    if (!empty($path)) {
    $urlParts = explode('/', $path);


    if ($urlParts[0] === 'activities' && isset($urlParts[1]) && $urlParts[1] === 'getProjectsByCustomerJson' && isset($urlParts[2])) {
    $controllerName = 'ActivitiesController';
    $methodName = 'getProjectsByCustomerJson';
    $params = [$urlParts[2]]; 
    }    

    // ✅ เงื่อนไขที่ 1: ตรวจจับ API ของเราก่อนเป็นอันดับแรก
    if ($urlParts[0] === 'notifications' && isset($urlParts[1]) && $urlParts[1] === 'read' && isset($urlParts[2]) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $controllerName = 'NotificationsController';
        $methodName = 'markAsRead';
        $params = [$urlParts[2]];

    } 
    // ✅ เงื่อนไขที่ 2: ใช้ elseif เพื่อให้ไม่ทำงานซ้ำซ้อน
    elseif (isset($urlParts[0]) && $urlParts[0] === 'auth') {
        $controllerSlug = $urlParts[1] ?? 'login';
        $methodSlug = $urlParts[2] ?? 'index';
        $params = isset($urlParts[3]) ? array_slice($urlParts, 3) : [];
        $controllerName = ucfirst($controllerSlug) . 'Controller';
        $methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $methodSlug))));

    }
    elseif ($urlParts[0] === 'admin' && isset($urlParts[1])) {
        $controllerSlug = $urlParts[1]; // ตัวที่ 2 คือ Controller เช่น 'users'
        $methodSlug = $urlParts[2] ?? 'index'; // ตัวที่ 3 คือ Method เช่น 'view', 'delete'
        $params = isset($urlParts[3]) ? array_slice($urlParts, 3) : []; // พารามิเตอร์เริ่มจากตัวที่ 4
    } 
    // ✅ เงื่อนไขที่ 3: สำหรับ Controller อื่นๆ
    elseif (isset($urlParts[0]) && in_array($urlParts[0], ['customers', 'projects', 'profile', 'admin', 'home', 'quotations', 'users','activities'])) {
        $controllerSlug = $urlParts[0];
        $methodSlug = $urlParts[1] ?? 'index';
        $params = isset($urlParts[2]) ? array_slice($urlParts, 2) : [];
        $controllerName = ucfirst($controllerSlug) . 'Controller';
        $methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $methodSlug))));

    } 
    // ✅ เงื่อนไขที่ 4: สำหรับ Route ทั่วไป (ถ้ามี)
    else {
        
        $controllerSlug = $urlParts[0] ?? 'home';
        $methodSlug = $urlParts[1] ?? 'index';
        $params = isset($urlParts[2]) ? array_slice($urlParts, 2) : [];
        $controllerName = ucfirst($controllerSlug) . 'Controller';
        $methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $methodSlug))));
    }
}

    // 2. สร้างชื่อ Controller และ Method พื้นฐาน
    $controllerName = ucfirst($controllerSlug) . 'Controller';
    $methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $methodSlug))));

    
    if ($controllerName === 'QuotationsController' && $methodSlug === 'pdf') {
    $methodName = 'downloadPdf';
    }

    // จัดการ POST Request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controllerName === 'LoginController') $methodName = 'authenticate';
        if ($controllerName === 'RegisterController' && $methodName === 'index') $methodName = 'submit';
        if ($controllerName === 'RegisterController' && $methodName === 'verify') $methodName = 'checkotp';
        if ($controllerName === 'PasswordController' && $methodName === 'request') $methodName = 'request';
        if ($controllerName === 'PasswordController' && $methodName === 'update') $methodName = 'update';
        if ($controllerName === 'ProjectsController' && $methodSlug === 'claim') $methodName = 'claim';
        if ($controllerName === 'ProjectsController' && $methodName === 'store') $methodName = 'store';
        if ($controllerName === 'CustomersController' && $methodName === 'update') $methodName = 'update';
        if ($controllerName === 'ProfileController' && $methodSlug === 'upload') $methodName = 'upload';
        if ($controllerName === 'ProfileController' && $methodSlug === 'update') $methodName = 'update';
        if ($controllerName === 'ProfileController' && $methodSlug === 'security') $methodName = 'security';
        if ($controllerName === 'ProfileController' && $methodSlug === 'change-password') $methodName = 'changePasswordForm';
        if ($controllerName === 'ProfileController' && $methodSlug === 'update-password') $methodName = 'updatePassword';
        if ($methodSlug === 'unassign-project') $methodName = 'unassignProject';
        if ($methodSlug === 'edit') $methodName = 'revise';

    }


}


// =====================================================================
// ===  Controller และ Method ===
// =====================================================================
if (!class_exists($controllerName)) {
    die("404 Not Found - Controller class '{$controllerName}' not found. Please check file and class name.");
}

$controller = new $controllerName($pdo);

if (!method_exists($controller, $methodName)) {
    die("404 Not Found - Method '{$methodName}' not found in '{$controllerName}'.");
}

call_user_func_array([$controller, $methodName], $params);
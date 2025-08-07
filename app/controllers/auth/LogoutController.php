<?php
class LogoutController {
    public function index() {

        $logger = LoggerService::getLogger('logout');
        if (isset($_SESSION['user_id'])) {
                $isTimeout = isset($_GET['reason']) && $_GET['reason'] === 'timeout';
                if ($isTimeout) {
                    $logger->info('User session timed out and was logged out automatically.', ['user_id' => $_SESSION['user_id']]);
                } else {
                    $logger->info('User logged out manually.', ['user_id' => $_SESSION['user_id']]);
                }
            }
    // ✅ บันทึก Log ก่อนที่จะทำลาย Session
    // เราใช้ ?? 'N/A' เผื่อกรณีที่ session ถูกทำลายไปก่อนแล้ว
    $logger->info('User logged out', ['user_id' => $_SESSION['user_id'] ?? 'N/A']);

        session_start();
        session_unset(); // เคลียร์ตัวแปรทั้งหมด
        session_destroy(); // ลบ session

        $redirectUrl = "/mcvpro/public/login";
        if (isset($isTimeout) && $isTimeout) {
            $redirectUrl .= "?status=timeout";
        }

        header("Location: " . $redirectUrl);
        exit;
    }
}

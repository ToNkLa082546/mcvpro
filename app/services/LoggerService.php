<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class LoggerService
{
    /**
     * @var array เก็บ instance ของ logger แต่ละ channel
     */
    private static $loggers = [];

    /**
     * สร้างและคืนค่า instance ของ Logger ตาม channel ที่ระบุ
     * @param string $channel ชื่อของ Log channel (เช่น 'customer', 'project', 'auth')
     * @return Logger
     */
    public static function getLogger(string $channel = 'app'): Logger
    {
        // ถ้ายังไม่เคยสร้าง logger สำหรับ channel นี้ ให้สร้างขึ้นมาใหม่
        if (!isset(self::$loggers[$channel])) {
            
            // 1. สร้าง Logger channel ตามชื่อที่ส่งเข้ามา
            $logger = new Logger($channel);

            // 2. กำหนดรูปแบบของ Log (เหมือนเดิม)
            $formatter = new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                "Y-m-d H:i:s",
                true,
                true
            );

            // 3. สร้าง Handler เพื่อบอกว่าจะให้บันทึก Log ลงที่ไหน
            $streamHandler = new StreamHandler(ROOT_PATH . 'logs/' . $channel . '.log', Logger::DEBUG);
            $streamHandler->setFormatter($formatter);

            $logger->pushHandler($streamHandler);

            // 4. เก็บ logger ที่สร้างใหม่ไว้ใน array เพื่อเรียกใช้ครั้งต่อไปได้เลย
            self::$loggers[$channel] = $logger;
        }

        return self::$loggers[$channel];
    }
}
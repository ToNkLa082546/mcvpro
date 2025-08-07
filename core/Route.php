<?php

class Route {

    /**
     * Redirect ไปยัง URL ที่กำหนด
     * @param string $url URL ที่จะ Redirect ไป
     */
    public static function redirect($url) {
        header("Location: " . $url);
        exit();
    }
    

}
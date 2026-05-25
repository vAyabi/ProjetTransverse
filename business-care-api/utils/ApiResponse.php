<?php
class ApiResponse {
    public static function success($data = null, $message = "", $status_code = 200) {
        http_response_code($status_code);
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ]);
    }
    
    public static function error($message = "", $status_code = 400) {
        http_response_code($status_code);
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
    }
    
    public static function notFound($message = "Resource not found") {
        self::error($message, 404);
    }
}
<?php
function methodIsAllowed($operation) {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($operation) {
        case 'read':
            return $method === 'GET';
        case 'create':
            return $method === 'POST';
        case 'update':
            return $method === 'PUT' || $method === 'POST'; // Certains serveurs ne supportent pas PUT
        case 'delete':
            return $method === 'DELETE' || $method === 'POST'; // Certains serveurs ne supportent pas DELETE
        default:
            return false;
    }
}

function verifyPositiveInteger($value) {
    return is_numeric($value) && intval($value) > 0 && intval($value) == $value;
}
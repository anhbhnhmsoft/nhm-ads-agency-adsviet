<?php

namespace App\Core;

final class RestResponse
{
    public static function success(array $data = [], string $message = 'Success', int $status = 200)
    {
        return response()->json([
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function validation(array $errors, string $message = 'Validation Error', int $status = 422)
    {
        return response()->json([
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    public static function error(string $message = 'Error', int $status = 400)
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }


}

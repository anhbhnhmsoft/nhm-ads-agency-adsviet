<?php

namespace App\Core;

final class RestResponse
{
    public static function success(mixed $data = [], string $message = 'Success', int $status = 200)
    {
        return response()->json([
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function validation(mixed $errors, string $message = 'common_error.validation_failed', int $status = 422)
    {
        return response()->json([
            'message' => __($message),
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

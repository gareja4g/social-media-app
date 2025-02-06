<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * Return a success response with data.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendSuccess($data, $message = 'Request successful', $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data
        ], $statusCode);
    }

    /**
     * Return an error response.
     *
     * @param  string  $message
     * @param  int  $statusCode
     * @param  array  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendError($message, $statusCode = 400, $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors
        ], $statusCode);
    }
}

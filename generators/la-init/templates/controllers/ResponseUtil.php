<?php

namespace App\Utils;

class ResponseUtil
{
    /**
     * @param string $message
     * @param mixed  $data
     *
     * @return array
     */
    public static function makeResponse($message, $data)
    {

        if (isset($data['current_page'])) {
            return [
                'success' => true,
                'data'    => $data['data'],
                'current_page'    => $data['current_page'],
                'per_page'    => $data['per_page'],
                'total'    => $data['total'],
                'message' => $message,
            ];
        } else {
            return [
                'success' => true,
                'data'    => $data,
                'message' => $message,
            ];
        }
    }

    /**
     * @param string $message
     * @param array  $data
     *
     * @return array
     */
    public static function makeError($message, array $data = [])
    {
        $res = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($data)) {
            $res['data'] = $data;
        }

        return $res;
    }
}

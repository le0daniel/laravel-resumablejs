<?php

namespace le0daniel\Laravel\ResumableJs\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use le0daniel\Laravel\ResumableJs\Utility\Arrays;

final class ApiResponse implements Responsable
{
    private array $response;
    private int $statusCode;

    private function __construct(array $response, int $statusCode = 200)
    {
        $this->response = $response;
        $this->statusCode = $statusCode;
    }

    /**
     * @param null|Model|Collection|array $data
     * @return static
     */
    public static function successful($data = null): self
    {
        return new self(
            [
                'success' => true,
                'data' => $data,
            ]
        );
    }

    public static function error(string $message, int $statusCode): self
    {
        return new self(
            [
                'success' => false,
                'error' => $message,
            ],
            $statusCode
        );
    }

    public function toResponse($request)
    {
        return response()
            ->json(
                Arrays::filterNullValues($this->response),
                $this->statusCode
            );
    }
}

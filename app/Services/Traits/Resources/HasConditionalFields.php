<?php

declare(strict_types=1);

namespace App\Services\Traits\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;

trait HasConditionalFields
{
    protected function getConditionalData(Request $request): array
    {
        $data = [];

        foreach ($this->conditionalFields as $key => $field) {
            $value = $this->resource->{$field};

            if ($value instanceof Carbon) {
                $data[$key] = $value->toISOString();
            } elseif (is_bool($value)) {
                $data[$key] = $value;
            } elseif (is_null($value)) {
                $data[$key] = null;
            } else {
                $data[$key] = (string)$value;
            }
        }

        return $data;
    }
}
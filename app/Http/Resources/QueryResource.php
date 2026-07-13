<?php

namespace App\Http\Resources;

use App\Enums\QueryStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $status = QueryStatus::tryFrom((int) data_get($data, 'query_status_id'));

        return array_merge($data, [
            'query_status' => [
                'id' => $status?->value ?? data_get($data, 'query_status_id'),
                'name' => $status?->name,
                'label' => $status?->label(),
            ],
        ]);
    }
}
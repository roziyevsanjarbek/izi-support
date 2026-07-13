<?php

namespace App\Http\Controllers\Integrations\Zanjeer;

use App\Http\Controllers\Controller;
use App\Http\Resources\QueryResource;
use App\Services\ZanjeerService;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    public function __construct(protected ZanjeerService $zanjeerService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'string', 'max:50'],
            'filter' => ['nullable', 'array'],
            'filter.query_source_id' => ['nullable', 'integer'],
            'filter.state_number' => ['nullable', 'string', 'max:100'],
            'filter.custom_id' => ['nullable', 'string', 'max:100'],
            'filter.cargo_name' => ['nullable', 'string', 'max:255'],
            'filter.customer_id' => ['nullable', 'string', 'max:100'],
            'filter.carrier_id' => ['nullable', 'string', 'max:100'],
            'filter.query_status_id' => ['nullable', 'integer'],
            'filter.payment_method' => ['nullable', 'string', 'max:50'],
            'filter.loading_at' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $query = $this->buildQueryParams($validated, $user);
        $pagination = $this->zanjeerService->queries($query);

        data_set(
            $pagination,
            'data',
            collect(data_get($pagination, 'data', []))
                ->map(fn ($item) => (new QueryResource($item))->resolve())
                ->all()
        );

        return view('pages.integrations.zanjeer.query', [
            'items' => data_get($pagination, 'data', []),
            'pagination' => $pagination,
            'filters' => $validated['filter'] ?? [],
            'sort' => $validated['sort'] ?? '-id',
            'canCreateTasks' =>
                auth()->user()?->isSuperAdmin()
                || auth()->user()?->hasPermission('create_tasks'),
        ]);
    }

    private function buildQueryParams(array $validated, $user): array
    {
        $query = [];

        $query['sort'] = $validated['sort'] ?? '-id';
        $query['include'] = 'shipment_type,operations.user,sales,addresses,customer_view';

        if (!empty($validated['page'])) {
            $query['page'] = (int) $validated['page'];
        }

        $defaultFilters = [
            'addresses.city.country_code' => 'CN',
        ];

        $userFilters = $validated['filter'] ?? [];

        if (!empty($userFilters) && is_array($userFilters)) {
            $userFilters = array_filter(
                $userFilters,
                fn ($value) => $value !== null && $value !== ''
            );
        }

        $query['filter'] = array_merge($defaultFilters, $userFilters ?? []);

        if (
            !$user->isSuperAdmin()
            && !$user->hasPermission('create_tasks')
        ) {
            $query['filter']['operations.user.id'] = $user->id;
        }

        return $query;
    }
}
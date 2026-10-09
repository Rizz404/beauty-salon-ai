<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentSalon;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use App\Modules\Services\Actions\SaveService;
use App\Modules\Services\Enums\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServiceController extends Controller
{
    use ResolvesCurrentSalon;

    public function index(Request $request): View
    {
        $salon = $this->currentSalon($request);

        $services = $salon->services()->orderBy('category')->orderBy('sort_order')->get();

        return view('admin.services.index', compact('salon', 'services'));
    }

    public function create(Request $request): View
    {
        $salon = $this->currentSalon($request);

        return view('admin.services.form', [
            'salon' => $salon,
            'service' => new Service(['category' => Service::CATEGORY_HAIR, 'duration_minutes' => 60, 'deposit' => 0, 'is_active' => true]),
            'categories' => ServiceCategory::values(),
        ]);
    }

    public function store(ServiceRequest $request, SaveService $save): RedirectResponse
    {
        $service = $save->handle($this->currentSalon($request), $request->serviceAttributes());

        return redirect()->route('admin.services.index')->with('status', "Service \"{$service->name}\" created.");
    }

    public function edit(Request $request, Service $service): View
    {
        $salon = $this->currentSalon($request);
        Gate::authorize('update', $service);

        return view('admin.services.form', [
            'salon' => $salon,
            'service' => $service,
            'categories' => ServiceCategory::values(),
        ]);
    }

    public function update(ServiceRequest $request, Service $service, SaveService $save): RedirectResponse
    {
        $save->handle($this->currentSalon($request), $request->serviceAttributes(), $service);

        return redirect()->route('admin.services.index')->with('status', "Service \"{$service->name}\" updated.");
    }

    public function destroy(Request $request, Service $service): RedirectResponse
    {
        $this->currentSalon($request);
        Gate::authorize('delete', $service);

        $service->delete();

        return redirect()->route('admin.services.index')->with('status', 'Service deleted.');
    }
}

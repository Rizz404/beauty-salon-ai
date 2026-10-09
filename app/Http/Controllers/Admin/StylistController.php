<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentSalon;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StylistRequest;
use App\Models\Stylist;
use App\Modules\Stylists\Actions\SaveStylist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StylistController extends Controller
{
    use ResolvesCurrentSalon;

    public function index(Request $request): View
    {
        $salon = $this->currentSalon($request);

        $stylists = $salon->stylists()->withCount('images')->orderBy('sort_order')->get();

        return view('admin.stylists.index', compact('salon', 'stylists'));
    }

    public function create(Request $request): View
    {
        $salon = $this->currentSalon($request);

        return view('admin.stylists.form', [
            'salon' => $salon,
            'stylist' => new Stylist(['specialty' => Stylist::specialties()[0], 'years_experience' => 1, 'is_active' => true]),
        ]);
    }

    public function store(StylistRequest $request, SaveStylist $save): RedirectResponse
    {
        $stylist = $save->handle(
            $this->currentSalon($request),
            $request->stylistAttributes(),
            $request->imageIdsToDelete(),
            $request->newImages(),
        );

        return redirect()->route('admin.stylists.index')->with('status', "Stylist \"{$stylist->name}\" added.");
    }

    public function edit(Request $request, Stylist $stylist): View
    {
        $salon = $this->currentSalon($request);
        Gate::authorize('update', $stylist);

        $stylist->load('images');

        return view('admin.stylists.form', compact('salon', 'stylist'));
    }

    public function update(StylistRequest $request, Stylist $stylist, SaveStylist $save): RedirectResponse
    {
        $save->handle($this->currentSalon($request), $request->stylistAttributes(), $request->imageIdsToDelete(), $request->newImages(), $stylist);

        return redirect()->route('admin.stylists.index')->with('status', "Stylist \"{$stylist->name}\" updated.");
    }

    public function destroy(Request $request, Stylist $stylist): RedirectResponse
    {
        $this->currentSalon($request);
        Gate::authorize('delete', $stylist);

        $stylist->delete();

        return redirect()->route('admin.stylists.index')->with('status', 'Stylist removed.');
    }
}

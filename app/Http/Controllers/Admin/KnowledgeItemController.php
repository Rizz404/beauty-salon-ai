<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentSalon;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KnowledgeItemRequest;
use App\Models\SalonKnowledgeItem;
use App\Modules\Knowledge\Enums\KnowledgeCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class KnowledgeItemController extends Controller
{
    use ResolvesCurrentSalon;

    public function index(Request $request): View
    {
        $salon = $this->currentSalon($request);

        $items = $salon->knowledgeItems()->orderBy('category')->orderBy('sort_order')->get();

        return view('admin.knowledge-items.index', compact('salon', 'items'));
    }

    public function create(Request $request): View
    {
        $salon = $this->currentSalon($request);

        return view('admin.knowledge-items.form', [
            'salon' => $salon,
            'item' => new SalonKnowledgeItem(['category' => SalonKnowledgeItem::CATEGORY_GENERAL, 'is_active' => true]),
            'categories' => KnowledgeCategory::values(),
        ]);
    }

    public function store(KnowledgeItemRequest $request): RedirectResponse
    {
        $item = $this->currentSalon($request)->knowledgeItems()->create($request->knowledgeItemAttributes());

        return redirect()->route('admin.knowledge-items.index')->with('status', "Knowledge item \"{$item->title}\" created.");
    }

    public function edit(Request $request, SalonKnowledgeItem $knowledgeItem): View
    {
        $salon = $this->currentSalon($request);
        Gate::authorize('update', $knowledgeItem);

        return view('admin.knowledge-items.form', [
            'salon' => $salon,
            'item' => $knowledgeItem,
            'categories' => KnowledgeCategory::values(),
        ]);
    }

    public function update(KnowledgeItemRequest $request, SalonKnowledgeItem $knowledgeItem): RedirectResponse
    {
        $this->currentSalon($request);

        $knowledgeItem->update($request->knowledgeItemAttributes());

        return redirect()->route('admin.knowledge-items.index')->with('status', "Knowledge item \"{$knowledgeItem->title}\" updated.");
    }

    public function destroy(Request $request, SalonKnowledgeItem $knowledgeItem): RedirectResponse
    {
        $this->currentSalon($request);
        Gate::authorize('delete', $knowledgeItem);

        $knowledgeItem->delete();

        return redirect()->route('admin.knowledge-items.index')->with('status', 'Knowledge item deleted.');
    }
}

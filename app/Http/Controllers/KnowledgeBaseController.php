<?php

namespace App\Http\Controllers;

use App\Models\BusinessRule;
use App\Models\Faq;
use App\Models\Product;
use App\Models\SavedReply;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $activeSection = $request->query('section', 'faqs');
        $sections = ['faqs', 'products', 'rules', 'saved-replies'];

        if (! in_array($activeSection, $sections, true)) {
            $activeSection = 'faqs';
        }

        return view('dashboard.knowledge-base', [
            'faqs' => Faq::where('business_id', $business->id)->latest()->get(),
            'products' => Product::where('business_id', $business->id)->latest()->get(),
            'rules' => BusinessRule::where('business_id', $business->id)->latest()->get(),
            'savedReplies' => SavedReply::where('business_id', $business->id)->latest()->get(),
            'activeSection' => $activeSection,
        ]);
    }

    public function storeFaq(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:3000'],
            'category' => ['nullable', 'string', 'max:80'],
        ]);

        Faq::create($validated + ['business_id' => $business->id]);

        return redirect()->route('dashboard.knowledge-base', ['section' => 'faqs'])->with('status', 'FAQ added.');
    }

    public function updateFaq(Request $request, Faq $faq)
    {
        $this->authorizeBusinessResource($request, $faq->business_id);
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:3000'],
            'category' => ['nullable', 'string', 'max:80'],
        ]);

        $faq->update($validated);

        return redirect()->route('dashboard.knowledge-base', ['section' => 'faqs'])->with('status', 'FAQ updated.');
    }

    public function destroyFaq(Request $request, Faq $faq)
    {
        $this->authorizeBusinessResource($request, $faq->business_id);
        $faq->delete();

        return redirect()->route('dashboard.knowledge-base', ['section' => 'faqs'])->with('status', 'FAQ deleted.');
    }

    public function storeProduct(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['nullable', 'string', 'max:120'],
            'availability' => ['nullable', 'string', 'max:160'],
            'ai_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Product::create($validated + ['business_id' => $business->id]);

        return redirect()->route('dashboard.knowledge-base', ['section' => 'products'])->with('status', 'Product or service added.');
    }

    public function updateProduct(Request $request, Product $product)
    {
        $this->authorizeBusinessResource($request, $product->business_id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['nullable', 'string', 'max:120'],
            'availability' => ['nullable', 'string', 'max:160'],
            'ai_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $product->update($validated);

        return redirect()->route('dashboard.knowledge-base', ['section' => 'products'])->with('status', 'Product or service updated.');
    }

    public function destroyProduct(Request $request, Product $product)
    {
        $this->authorizeBusinessResource($request, $product->business_id);
        $product->delete();

        return redirect()->route('dashboard.knowledge-base', ['section' => 'products'])->with('status', 'Product or service deleted.');
    }

    public function storeRule(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $validated = $request->validate([
            'rule_type' => ['required', 'string', 'max:80', Rule::in(['pricing', 'availability', 'policy', 'other'])],
            'title' => ['required', 'string', 'max:160'],
            'content' => ['required', 'string', 'max:3000'],
        ]);

        BusinessRule::create($validated + ['business_id' => $business->id]);

        return redirect()->route('dashboard.knowledge-base', ['section' => 'rules'])->with('status', 'Business rule added.');
    }

    public function updateRule(Request $request, BusinessRule $rule)
    {
        $this->authorizeBusinessResource($request, $rule->business_id);
        $validated = $request->validate([
            'rule_type' => ['required', 'string', 'max:80', Rule::in(['pricing', 'availability', 'policy', 'other'])],
            'title' => ['required', 'string', 'max:160'],
            'content' => ['required', 'string', 'max:3000'],
        ]);

        $rule->update($validated);

        return redirect()->route('dashboard.knowledge-base', ['section' => 'rules'])->with('status', 'Business rule updated.');
    }

    public function destroyRule(Request $request, BusinessRule $rule)
    {
        $this->authorizeBusinessResource($request, $rule->business_id);
        $rule->delete();

        return redirect()->route('dashboard.knowledge-base', ['section' => 'rules'])->with('status', 'Business rule deleted.');
    }

    public function storeSavedReply(Request $request)
    {
        $business = $request->attributes->get('currentBusiness');
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:3000'],
            'shortcut' => ['nullable', 'string', 'max:80'],
        ]);

        SavedReply::create($validated + ['business_id' => $business->id]);

        return redirect()->route('dashboard.knowledge-base', ['section' => 'saved-replies'])->with('status', 'Saved reply added.');
    }

    public function updateSavedReply(Request $request, SavedReply $savedReply)
    {
        $this->authorizeBusinessResource($request, $savedReply->business_id);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:3000'],
            'shortcut' => ['nullable', 'string', 'max:80'],
        ]);

        $savedReply->update($validated);

        return redirect()->route('dashboard.knowledge-base', ['section' => 'saved-replies'])->with('status', 'Saved reply updated.');
    }

    public function destroySavedReply(Request $request, SavedReply $savedReply)
    {
        $this->authorizeBusinessResource($request, $savedReply->business_id);
        $savedReply->delete();

        return redirect()->route('dashboard.knowledge-base', ['section' => 'saved-replies'])->with('status', 'Saved reply deleted.');
    }

    private function authorizeBusinessResource(Request $request, int $resourceBusinessId): void
    {
        $business = $request->attributes->get('currentBusiness');

        abort_unless((int) $resourceBusinessId === (int) $business->id, 403);
    }
}

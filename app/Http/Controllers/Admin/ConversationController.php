<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Website;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $websiteIds = Website::where('tenant_id', $tenantId)
            ->pluck('id');

        $query = Conversation::with(['website', 'lead'])
            ->whereIn('website_id', $websiteIds);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('lead_stage')) {
            $query->where('lead_stage', $request->lead_stage);
        }

        $conversations = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.conversations.index', compact('conversations'));
    }

    public function show(Conversation $conversation)
    {
        $this->authorizeTenantConversation($conversation);

        $conversation->load([
            'website',
            'lead',
            'messages' => function ($query) {
                $query->orderBy('created_at');
            },
        ]);

        return view('admin.conversations.show', compact('conversation'));
    }

    private function authorizeTenantConversation(Conversation $conversation): void
    {
        $tenantId = auth()->user()->tenant_id;

        $allowedWebsiteIds = Website::where('tenant_id', $tenantId)
            ->pluck('id')
            ->toArray();

        if (!in_array($conversation->website_id, $allowedWebsiteIds)) {
            abort(403, 'Unauthorized conversation access.');
        }
    }
}
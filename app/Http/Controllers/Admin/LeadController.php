<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = Lead::with(['website', 'conversation'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('product_interest', 'like', "%{$search}%");
            });
        }

        $leads = $query
            ->orderByDesc('lead_score')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.leads.index', compact('leads'));
    }

    public function show(Lead $lead)
    {
        $this->authorizeTenantLead($lead);

        $lead->load([
            'website',
            'conversation.messages',
        ]);

        return view('admin.leads.show', compact('lead'));
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorizeTenantLead($lead);

        $request->validate([
            'status' => 'required|in:new,qualified,contacted,converted,closed',
        ]);

        $lead->status = $request->status;

        if ($request->status === 'contacted' && !$lead->contacted_at) {
            $lead->contacted_at = now();
        }

        $lead->save();

        return redirect()
            ->back()
            ->with('success', 'Lead status updated successfully.');
    }

    private function authorizeTenantLead(Lead $lead): void
    {
        if ($lead->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Unauthorized lead access.');
        }
    }
}
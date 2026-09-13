<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelConnection;
use App\Models\Tenant;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ChannelConnection::query()
            ->with([
                'tenant',
                'aiAgent',
                'website',
            ])
            ->withCount('conversations');

        if (!$user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        } elseif ($request->filled('tenant_id')) {
            $query->where('tenant_id', (int) $request->input('tenant_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $channels = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summaryQuery = ChannelConnection::query();

        if (!$user->isSuperAdmin()) {
            $summaryQuery->where('tenant_id', $user->tenant_id);
        } elseif ($request->filled('tenant_id')) {
            $summaryQuery->where('tenant_id', (int) $request->input('tenant_id'));
        }

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'active' => (clone $summaryQuery)->where('status', 'active')->count(),
            'website' => (clone $summaryQuery)->where('type', 'website')->count(),
            'whatsapp' => (clone $summaryQuery)->where('type', 'whatsapp')->count(),
        ];

        $tenants = $user->isSuperAdmin()
            ? Tenant::query()->orderBy('name')->get(['id', 'name', 'company_name'])
            : collect();

        return view('admin.channels.index', compact(
            'channels',
            'summary',
            'tenants'
        ));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelConnection;
use App\Models\Conversation;
use App\Models\KnowledgePage;
use App\Models\KnowledgeSource;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\Website;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $totalTenants = Tenant::count();
            $totalWebsites = Website::count();
            $totalChannels = ChannelConnection::count();
            $activeChannels = ChannelConnection::where('status', 'active')->count();
            $totalLeads = Lead::count();
            $qualifiedLeads = Lead::where('status', 'qualified')->count();
            $totalConversations = Conversation::count();
            $totalKnowledgeItems = KnowledgePage::count() + KnowledgeSource::count();

            $recentLeads = Lead::with(['website', 'tenant'])
                ->latest()
                ->take(5)
                ->get();

            $recentConversations = Conversation::with([
                'website.tenant',
                'channelConnection',
                'contact',
                'lead',
            ])
                ->latest()
                ->take(5)
                ->get();
        } else {
            $tenantId = (int) $user->tenant_id;

            $totalTenants = null;
            $totalWebsites = Website::where('tenant_id', $tenantId)->count();
            $totalChannels = ChannelConnection::where('tenant_id', $tenantId)->count();
            $activeChannels = ChannelConnection::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->count();
            $totalLeads = Lead::where('tenant_id', $tenantId)->count();
            $qualifiedLeads = Lead::where('tenant_id', $tenantId)
                ->where('status', 'qualified')
                ->count();

            /*
             * Important: no website_id filtering here. WhatsApp-only conversations
             * are first-class conversations and already carry tenant_id.
             */
            $totalConversations = Conversation::query()
                ->where(function ($query) use ($tenantId): void {
                    $query->where('tenant_id', $tenantId)
                        ->orWhere(function ($legacy) use ($tenantId): void {
                            $legacy->whereNull('tenant_id')
                                ->whereHas('website', function ($websiteQuery) use ($tenantId): void {
                                    $websiteQuery->where('tenant_id', $tenantId);
                                });
                        });
                })
                ->count();

            $totalKnowledgeItems = KnowledgePage::where('tenant_id', $tenantId)->count()
                + KnowledgeSource::where('tenant_id', $tenantId)->count();

            $recentLeads = Lead::with(['website', 'tenant'])
                ->where('tenant_id', $tenantId)
                ->latest()
                ->take(5)
                ->get();

            $recentConversations = Conversation::with([
                'website',
                'channelConnection',
                'contact',
                'lead',
            ])
                ->where(function ($query) use ($tenantId): void {
                    $query->where('tenant_id', $tenantId)
                        ->orWhere(function ($legacy) use ($tenantId): void {
                            $legacy->whereNull('tenant_id')
                                ->whereHas('website', function ($websiteQuery) use ($tenantId): void {
                                    $websiteQuery->where('tenant_id', $tenantId);
                                });
                        });
                })
                ->latest()
                ->take(5)
                ->get();
        }

        $setup = [
            'has_channel' => $activeChannels > 0,
            'has_knowledge' => $totalKnowledgeItems > 0,
            'has_conversation' => $totalConversations > 0,
        ];

        $setup['completed'] = collect($setup)->filter()->count();
        $setup['percent'] = (int) round(($setup['completed'] / 3) * 100);

        return view('admin.dashboard', compact(
            'totalTenants',
            'totalWebsites',
            'totalChannels',
            'activeChannels',
            'totalKnowledgeItems',
            'totalLeads',
            'qualifiedLeads',
            'totalConversations',
            'recentLeads',
            'recentConversations',
            'setup'
        ));
    }
}

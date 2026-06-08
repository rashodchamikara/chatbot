<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            SaaS Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">

                <div class="bg-white p-6 rounded shadow">
                    <div class="text-sm text-gray-500">Websites</div>
                    <div class="text-3xl font-bold">{{ $totalWebsites }}</div>
                </div>

                <div class="bg-white p-6 rounded shadow">
                    <div class="text-sm text-gray-500">Leads</div>
                    <div class="text-3xl font-bold">{{ $totalLeads }}</div>
                </div>

                <div class="bg-white p-6 rounded shadow">
                    <div class="text-sm text-gray-500">Qualified Leads</div>
                    <div class="text-3xl font-bold">{{ $qualifiedLeads }}</div>
                </div>

                <div class="bg-white p-6 rounded shadow">
                    <div class="text-sm text-gray-500">Conversations</div>
                    <div class="text-3xl font-bold">{{ $totalConversations }}</div>
                </div>
                @if(auth()->user()->isSuperAdmin())
                <div class="bg-white p-6 rounded shadow">
                    <div class="text-sm text-gray-500">Tenants</div>
                    <div class="text-3xl font-bold">{{ $totalTenants }}</div>
                </div>
                @endif

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="bg-white rounded shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg">Recent Leads</h3>
                        <a href="{{ route('admin.leads.index') }}" class="text-blue-600 text-sm">View all</a>
                    </div>

                    @forelse($recentLeads as $lead)
                        <div class="border-b py-3">
                            <div class="font-semibold">
                                {{ $lead->name ?? 'Unknown visitor' }}
                            </div>
                            <div class="text-sm text-gray-600">
                                {{ $lead->email ?? 'No email' }} |
                                Score: {{ $lead->lead_score }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $lead->product_interest ?? 'No product interest yet' }}
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500">No leads yet.</p>
                    @endforelse
                </div>

                <div class="bg-white rounded shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg">Recent Conversations</h3>
                        <a href="{{ route('admin.conversations.index') }}" class="text-blue-600 text-sm">View all</a>
                    </div>

                    @forelse($recentConversations as $conversation)
                        <div class="border-b py-3">
                            <div class="font-semibold">
                                Visitor: {{ $conversation->visitor_id }}
                            </div>
                            <div class="text-sm text-gray-600">
                                Website: {{ $conversation->website->name ?? '-' }}
                            </div>
                            <div class="text-sm text-gray-500">
                                Lead Stage: {{ $conversation->lead_stage }}
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500">No conversations yet.</p>
                    @endforelse
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
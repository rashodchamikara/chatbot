<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Conversation Details
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="bg-white rounded shadow p-6">
                    <h3 class="font-semibold text-lg mb-4">Conversation Info</h3>

                    <div class="space-y-3 text-sm">
                        <div>
                            <strong>Visitor ID:</strong>
                            {{ $conversation->visitor_id }}
                        </div>

                        <div>
                            <strong>Website:</strong>
                            {{ $conversation->website->name ?? '-' }}
                        </div>

                        <div>
                            <strong>Status:</strong>
                            {{ ucfirst($conversation->status) }}
                        </div>

                        <div>
                            <strong>Lead Stage:</strong>
                            {{ $conversation->lead_stage }}
                        </div>

                        <div>
                            <strong>Started:</strong>
                            {{ $conversation->created_at->format('Y-m-d H:i') }}
                        </div>
                    </div>

                    @if($conversation->lead)
                        <hr class="my-4">

                        <h3 class="font-semibold text-lg mb-4">Lead Info</h3>

                        <div class="space-y-3 text-sm">
                            <div>
                                <strong>Name:</strong>
                                {{ $conversation->lead->name ?? '-' }}
                            </div>

                            <div>
                                <strong>Email:</strong>
                                {{ $conversation->lead->email ?? '-' }}
                            </div>

                            <div>
                                <strong>Phone:</strong>
                                {{ $conversation->lead->phone ?? '-' }}
                            </div>

                            <div>
                                <strong>Interest:</strong>
                                {{ $conversation->lead->product_interest ?? '-' }}
                            </div>

                            <div>
                                <strong>Score:</strong>
                                {{ $conversation->lead->lead_score }}
                            </div>

                            <div>
                                <a
                                    href="{{ route('admin.leads.show', $conversation->lead) }}"
                                    class="text-blue-600"
                                >
                                    View Lead
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="md:col-span-2 bg-white rounded shadow p-6">
                    <h3 class="font-semibold text-lg mb-4">Messages</h3>

                    <div class="space-y-4">
                        @forelse($conversation->messages as $message)

                            <div class="p-4 rounded
                                @if($message->sender === 'visitor')
                                    bg-blue-50
                                @elseif($message->sender === 'ai')
                                    bg-gray-100
                                @else
                                    bg-green-50
                                @endif
                            ">
                                <div class="text-xs text-gray-500 mb-1">
                                    {{ strtoupper($message->sender) }}
                                    —
                                    {{ $message->created_at->format('Y-m-d H:i:s') }}
                                </div>

                                <div class="text-sm whitespace-pre-wrap">
                                    {{ $message->message }}
                                </div>
                            </div>

                        @empty
                            <p class="text-gray-500">
                                No messages found.
                            </p>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
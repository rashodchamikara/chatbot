<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Lead Details
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="md:col-span-1 bg-white rounded shadow p-6">
                    <h3 class="font-semibold text-lg mb-4">Lead Info</h3>

                    <div class="space-y-3 text-sm">
                        <div>
                            <strong>Name:</strong>
                            {{ $lead->name ?? '-' }}
                        </div>

                        <div>
                            <strong>Email:</strong>
                            {{ $lead->email ?? '-' }}
                        </div>

                        <div>
                            <strong>Phone:</strong>
                            {{ $lead->phone ?? '-' }}
                        </div>

                        <div>
                            <strong>Country:</strong>
                            {{ $lead->country ?? '-' }}
                        </div>

                        <div>
                            <strong>Preferred Contact Time:</strong>
                            {{ $lead->preferred_contact_time ?? '-' }}
                        </div>

                        <div>
                            <strong>Product Interest:</strong>
                            {{ $lead->product_interest ?? '-' }}
                        </div>

                        <div>
                            <strong>Lead Score:</strong>
                            {{ $lead->lead_score }}
                        </div>

                        <div>
                            <strong>Status:</strong>
                            {{ ucfirst($lead->status) }}
                        </div>

                        <div>
                            <strong>Website:</strong>
                            {{ $lead->website->name ?? '-' }}
                        </div>

                        <div>
                            <strong>Created:</strong>
                            {{ $lead->created_at->format('Y-m-d H:i') }}
                        </div>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.leads.updateStatus', $lead) }}"
                        class="mt-6"
                    >
                        @csrf
                        @method('PATCH')

                        <label class="block text-sm font-semibold mb-2">
                            Update Status
                        </label>

                        <select name="status" class="border rounded px-3 py-2 w-full mb-3">
                            <option value="new" @selected($lead->status === 'new')>New</option>
                            <option value="qualified" @selected($lead->status === 'qualified')>Qualified</option>
                            <option value="contacted" @selected($lead->status === 'contacted')>Contacted</option>
                            <option value="converted" @selected($lead->status === 'converted')>Converted</option>
                            <option value="closed" @selected($lead->status === 'closed')>Closed</option>
                        </select>

                        <button class="bg-blue-600 text-white px-4 py-2 rounded w-full">
                            Save Status
                        </button>
                    </form>
                </div>

                <div class="md:col-span-2 bg-white rounded shadow p-6">
                    <h3 class="font-semibold text-lg mb-4">Conversation</h3>

                    @if($lead->conversation && $lead->conversation->messages->count())
                        <div class="space-y-4">
                            @foreach($lead->conversation->messages as $message)

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

                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">
                            No conversation found for this lead.
                        </p>
                    @endif
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
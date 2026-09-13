<?php

namespace App\Http\Controllers\Admin;

use App\Data\Omnichannel\WhatsAppConnectionData;
use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\ChannelConnection;
use App\Models\Tenant;
use App\Services\Omnichannel\WhatsApp\WhatsAppConnectionConfigService;
use App\Services\Omnichannel\WhatsApp\WhatsAppConnectionHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class WhatsAppConnectionController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        $tenants = $user->isSuperAdmin()
            ? Tenant::query()->orderBy('name')->get()
            : collect();

        return view('admin.channels.whatsapp.create', compact('tenants'));
    }

    public function store(
        Request $request,
        WhatsAppConnectionConfigService $configService,
        WhatsAppConnectionHealthService $healthService,
    ): RedirectResponse {
        $user = $request->user();

        $rules = [
            'name' => ['required', 'string', 'max:191'],
            'business_account_id' => ['required', 'string', 'regex:/^\d+$/', 'max:191'],
            'phone_number_id' => [
                'required',
                'string',
                'regex:/^\d+$/',
                'max:191',
                Rule::unique('channel_connections', 'external_sender_id')
                    ->where(fn ($query) => $query
                        ->where('type', 'whatsapp')
                        ->where('provider', 'meta')),
            ],
            'access_token' => ['required', 'string', 'min:20'],
        ];

        if ($user->isSuperAdmin()) {
            $rules['tenant_id'] = ['required', 'integer', 'exists:tenants,id'];
        }

        $validated = $request->validate($rules, [
            'business_account_id.regex' => 'The WABA ID must contain numbers only.',
            'phone_number_id.regex' => 'The Phone Number ID must contain numbers only.',
            'phone_number_id.unique' => 'This WhatsApp Phone Number ID is already connected.',
        ]);

        $tenantId = $user->isSuperAdmin()
            ? (int) $validated['tenant_id']
            : (int) $user->tenant_id;

        if ($tenantId <= 0) {
            throw ValidationException::withMessages([
                'tenant_id' => 'This account is not assigned to a tenant.',
            ]);
        }

        /*
         * WhatsApp-only tenants do not need a Website record.
         * Reuse the tenant's first active AI agent or create one automatically.
         * This deliberately does not depend on a website or an is_default column.
         */
        $agent = $this->resolveAgent($tenantId);

        $connection = ChannelConnection::query()->create([
            'tenant_id' => $tenantId,
            'ai_agent_id' => $agent->id,
            'website_id' => null,
            'type' => 'whatsapp',
            'provider' => 'meta',
            'name' => trim($validated['name']),
            'status' => 'pending',
            'webhook_key' => (string) Str::ulid(),
            'settings' => [
                'setup_method' => 'manual_api_credentials',
            ],
        ]);

        try {
            $connection = $configService->configure(
                connection: $connection,
                data: new WhatsAppConnectionData(
                    accessToken: trim($validated['access_token']),
                    businessAccountId: trim($validated['business_account_id']),
                    phoneNumberId: trim($validated['phone_number_id']),
                ),
            );

            /*
             * This also fills safe provider metadata such as:
             * - display_phone_number
             * - verified_name
             * - quality_rating
             *
             * So users do not need to type those fields manually.
             */
            $health = $healthService->check($connection);
        } catch (Throwable $exception) {
            report($exception);

            $connection->forceFill([
                'status' => 'error',
                'last_error' => mb_substr($exception->getMessage(), 0, 1000),
            ])->save();

            return redirect()
                ->route('admin.channels.whatsapp.edit', $connection)
                ->with(
                    'warning',
                    'The WhatsApp connection was saved, but Meta verification failed. Review the IDs/token and try again.'
                );
        }

        if (!$health->healthy) {
            return redirect()
                ->route('admin.channels.whatsapp.edit', $connection)
                ->with(
                    'warning',
                    'The WhatsApp connection was saved, but Meta verification failed: ' . $health->message
                );
        }

        return redirect()
            ->route('admin.channels.index')
            ->with(
                'success',
                'WhatsApp connected and verified successfully. No website is required.'
            );
    }

    public function edit(Request $request, ChannelConnection $channelConnection)
    {
        $this->authorizeConnectionAccess($request, $channelConnection);
        $this->assertWhatsApp($channelConnection);

        $channelConnection->load(['tenant', 'aiAgent']);

        return view('admin.channels.whatsapp.edit', compact('channelConnection'));
    }

    public function update(
        Request $request,
        ChannelConnection $channelConnection,
        WhatsAppConnectionConfigService $configService,
        WhatsAppConnectionHealthService $healthService,
    ): RedirectResponse {
        $this->authorizeConnectionAccess($request, $channelConnection);
        $this->assertWhatsApp($channelConnection);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'business_account_id' => ['required', 'string', 'regex:/^\d+$/', 'max:191'],
            'phone_number_id' => [
                'required',
                'string',
                'regex:/^\d+$/',
                'max:191',
                Rule::unique('channel_connections', 'external_sender_id')
                    ->ignore($channelConnection->id)
                    ->where(fn ($query) => $query
                        ->where('type', 'whatsapp')
                        ->where('provider', 'meta')),
            ],
            'access_token' => ['nullable', 'string', 'min:20'],
        ], [
            'business_account_id.regex' => 'The WABA ID must contain numbers only.',
            'phone_number_id.regex' => 'The Phone Number ID must contain numbers only.',
            'phone_number_id.unique' => 'This WhatsApp Phone Number ID is already connected.',
        ]);

        $existingCredentials = is_array($channelConnection->credentials)
            ? $channelConnection->credentials
            : [];

        $accessToken = trim((string) ($validated['access_token'] ?? ''));

        if ($accessToken === '') {
            $accessToken = trim((string) ($existingCredentials['access_token'] ?? ''));
        }

        if ($accessToken === '') {
            throw ValidationException::withMessages([
                'access_token' => 'Enter a Meta access token because no existing token is available.',
            ]);
        }

        $channelConnection->forceFill([
            'name' => trim($validated['name']),
        ])->save();

        try {
            $channelConnection = $configService->configure(
                connection: $channelConnection,
                data: new WhatsAppConnectionData(
                    accessToken: $accessToken,
                    businessAccountId: trim($validated['business_account_id']),
                    phoneNumberId: trim($validated['phone_number_id']),
                ),
            );

            $health = $healthService->check($channelConnection);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->withInput($request->except('access_token'))
                ->with(
                    'warning',
                    'Settings were saved, but Meta verification failed. Review the credentials and try again.'
                );
        }

        return redirect()
            ->back()
            ->with(
                $health->healthy ? 'success' : 'warning',
                $health->message
            );
    }

    public function health(
        Request $request,
        ChannelConnection $channelConnection,
        WhatsAppConnectionHealthService $healthService,
    ): RedirectResponse {
        $this->authorizeConnectionAccess($request, $channelConnection);
        $this->assertWhatsApp($channelConnection);

        $health = $healthService->check($channelConnection);

        return redirect()
            ->back()
            ->with(
                $health->healthy ? 'success' : 'warning',
                $health->message
            );
    }

    public function destroy(
        Request $request,
        ChannelConnection $channelConnection,
    ): RedirectResponse {
        $this->authorizeConnectionAccess($request, $channelConnection);
        $this->assertWhatsApp($channelConnection);

        /*
         * Keep the row so historic conversations/messages retain their channel.
         * Remove the provider secret and stop new traffic.
         */
        $channelConnection->forceFill([
            'status' => 'disconnected',
            'credentials' => [],
            'connected_at' => null,
            'last_error' => null,
        ])->save();

        return redirect()
            ->route('admin.channels.index')
            ->with(
                'success',
                'WhatsApp disconnected. Conversation history was preserved and the stored token was removed.'
            );
    }


    private function resolveAgent(int $tenantId): AiAgent
    {
        $agent = AiAgent::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if ($agent) {
            return $agent;
        }

        $tenant = Tenant::query()->findOrFail($tenantId);
        $businessName = trim((string) ($tenant->company_name ?: $tenant->name));

        return AiAgent::query()->create([
            'tenant_id' => $tenantId,
            'name' => ($businessName !== '' ? $businessName : 'Business') . ' AI Assistant',
            'status' => 'active',
            'instructions' => null,
            'default_language' => 'en',
            'model_settings' => [],
            'handover_settings' => [
                'enabled' => true,
            ],
            'business_hours' => [],
        ]);
    }

    private function authorizeConnectionAccess(
        Request $request,
        ChannelConnection $connection,
    ): void {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ((int) $connection->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Unauthorized channel access.');
        }
    }

    private function assertWhatsApp(ChannelConnection $connection): void
    {
        if ($connection->type !== 'whatsapp') {
            abort(404);
        }
    }
}

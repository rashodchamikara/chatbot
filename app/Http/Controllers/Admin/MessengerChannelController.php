<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{AiAgent, ChannelConnection, MessengerOAuthGrant, Tenant};
use App\Services\Omnichannel\Messenger\GraphClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MessengerChannelController extends Controller
{
    private function tenant(Request $request): int
    {
        $user = $request->user();
        abort_unless($user && ($user->isTenantAdmin() || $user->isSuperAdmin()), 403);
        $id = $user->isSuperAdmin() ? (int) $request->input('tenant_id') : (int) $user->tenant_id;
        abort_unless($id && Tenant::whereKey($id)->exists(), 422, 'Select a tenant first.');
        return $id;
    }
    private function authorizeConnection(Request $request, ChannelConnection $channel): void
    {
        $user = $request->user();
        abort_unless($channel->type === 'facebook' && $user && ($user->isSuperAdmin()
            || ($user->isTenantAdmin() && $user->tenant_id && (int) $user->tenant_id === (int) $channel->tenant_id)), 403);
    }
    private function grant(Request $request, string $id): MessengerOAuthGrant
    {
        $grant = MessengerOAuthGrant::findOrFail($id);
        abort_unless((int) $grant->user_id === (int) $request->user()->id
            && hash_equals($grant->session_hash, hash('sha256', $request->session()->getId()))
            && $grant->expires_at->isFuture() && $grant->status === 'ready', 403, 'Connection session expired. Start again.');
        abort_unless($request->user()->isSuperAdmin() || ($request->user()->isTenantAdmin()
            && (int) $request->user()->tenant_id === (int) $grant->tenant_id), 403);
        return $grant;
    }
    public function create(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->isTenantAdmin(), 403);
        $tenants = $request->user()->isSuperAdmin() ? Tenant::orderBy('name')->get() : collect();
        $tenantId = $request->user()->isSuperAdmin() ? (int) $request->input('tenant_id') : (int) $request->user()->tenant_id;
        $agents = $tenantId ? AiAgent::where('tenant_id', $tenantId)->orderBy('name')->get() : collect();
        return view('admin.channels.facebook.connect', compact('tenants', 'tenantId', 'agents'));
    }
    public function start(Request $request)
    {
        $tenant = $this->tenant($request);
        abort_unless(config('messenger.enabled') && config('messenger.app_id') && config('messenger.app_secret'), 503, 'Messenger is not configured yet.');
        $data = $request->validate(['ai_agent_id' => ['required', 'integer']]);
        $agent = AiAgent::where('tenant_id', $tenant)->findOrFail($data['ai_agent_id']);
        MessengerOAuthGrant::where('expires_at', '<', now())->delete();
        $state = Str::random(64);
        MessengerOAuthGrant::create(['id' => (string) Str::uuid(), 'user_id' => $request->user()->id,
            'tenant_id' => $tenant, 'ai_agent_id' => $agent->id, 'state_hash' => hash('sha256', $state),
            'session_hash' => hash('sha256', $request->session()->getId()), 'expires_at' => now()->addMinutes(15)]);
        $params = ['client_id' => config('messenger.app_id'), 'redirect_uri' => route('admin.channels.facebook.callback'),
            'state' => $state, 'response_type' => 'code'];
        if (config('messenger.login_config_id')) $params['config_id'] = config('messenger.login_config_id');
        else $params['scope'] = implode(',', config('messenger.scopes'));
        return redirect()->away('https://www.facebook.com/'.config('messenger.graph_version').'/dialog/oauth?'.http_build_query($params));
    }
    public function callback(Request $request, GraphClient $graph)
    {
        $state = $request->input('state');
        abort_unless(is_string($state) && strlen($state) === 64, 403);
        $grant = DB::transaction(function () use ($request, $state) {
            $grant = MessengerOAuthGrant::where('state_hash', hash('sha256', $state))->lockForUpdate()->firstOrFail();
            abort_unless((int) $grant->user_id === (int) $request->user()->id && $grant->status === 'pending'
                && $grant->expires_at->isFuture() && hash_equals($grant->session_hash, hash('sha256', $request->session()->getId())), 403);
            abort_unless($request->user()->isSuperAdmin() || ($request->user()->isTenantAdmin()
                && (int) $request->user()->tenant_id === (int) $grant->tenant_id), 403);
            $grant->update(['status' => 'exchanging']); // Single-use even if exchange fails.
            return $grant;
        });
        try {
            if ($request->has('error') || !$request->filled('code')) throw new RuntimeException('Facebook connection was cancelled.');
            $result = $graph->request('GET', 'oauth/access_token', null, ['client_id' => config('messenger.app_id'),
                'client_secret' => config('messenger.app_secret'), 'redirect_uri' => route('admin.channels.facebook.callback'), 'code' => $request->string('code')->toString()]);
            $token = $result['access_token'] ?? '';
            if (!$token) throw new RuntimeException('Meta did not return an access token.');
            // Exchange for a long-lived user token before deriving Page tokens.
            $long = $graph->request('GET', 'oauth/access_token', null, ['grant_type' => 'fb_exchange_token',
                'client_id' => config('messenger.app_id'), 'client_secret' => config('messenger.app_secret'), 'fb_exchange_token' => $token]);
            $token = $long['access_token'] ?? '';
            if (!$token) throw new RuntimeException('Meta did not return a long-lived token.');
            $debug = $graph->request('GET', 'debug_token', config('messenger.app_id').'|'.config('messenger.app_secret'), ['input_token' => $token]);
            if (!data_get($debug, 'data.is_valid') || (string) data_get($debug, 'data.app_id') !== (string) config('messenger.app_id'))
                throw new RuntimeException('Meta returned a token for a different app or an expired grant.');
            if (array_diff(config('messenger.scopes'), data_get($debug, 'data.scopes', [])))
                throw new RuntimeException('Required Page permissions were not granted. Reconnect and approve Page listing, subscriptions, and messaging.');
            $pages = $graph->pages($token);
            if (!$pages) throw new RuntimeException('No eligible Pages were granted. Reconnect and allow access to a Page you can message on.');
            $grant->update(['pages' => $pages, 'status' => 'ready']);
            return redirect()->route('admin.channels.facebook.pages', $grant->id);
        } catch (RuntimeException $e) {
            $grant->update(['pages' => null, 'status' => 'failed']);
            return redirect()->route('admin.channels.facebook.create', ['tenant_id' => $grant->tenant_id])->withErrors(['facebook' => get_class($e) === RuntimeException::class ? $e->getMessage() : 'The operation could not be completed. Please try again.']);
        }
    }
    public function pages(Request $request, string $grant)
    {
        $grant = $this->grant($request, $grant);
        // Explicit projection: access tokens never enter HTML or JavaScript.
        $pages = collect($grant->pages)->map(fn ($p) => ['id' => $p['id'], 'name' => $p['name']])->values();
        return response()->view('admin.channels.facebook.pages', compact('grant', 'pages'))->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }
    public function connect(Request $request, string $grant, GraphClient $graph)
    {
        $grant = $this->grant($request, $grant);
        $data = $request->validate(['page_id' => ['required', 'regex:/^[0-9]+$/']]);
        try {
            $channel = DB::transaction(function () use ($grant, $data, $graph) {
                $grant = MessengerOAuthGrant::whereKey($grant->id)->lockForUpdate()->firstOrFail();
                abort_unless($grant->status === 'ready' && $grant->expires_at->isFuture(), 409);
                $page = $grant->pages[$data['page_id']] ?? null;
                abort_unless($page, 403);
                AiAgent::where('tenant_id', $grant->tenant_id)->findOrFail($grant->ai_agent_id);
                // Serialize all connection operations for this tenant; Page PK enforces cross-tenant ownership.
                Tenant::whereKey($grant->tenant_id)->lockForUpdate()->firstOrFail();
                $claim = DB::table('messenger_page_claims')->where('page_id', $page['id'])->lockForUpdate()->first();
                if ($claim && (int) $claim->tenant_id !== (int) $grant->tenant_id)
                    throw ValidationException::withMessages(['facebook' => 'This Page is already connected to another workspace.']);
                $channel = $claim ? ChannelConnection::whereKey($claim->channel_connection_id)->lockForUpdate()->firstOrFail() : new ChannelConnection();
                if (!$claim && ChannelConnection::where('type', 'facebook')->where('external_sender_id', $page['id'])->exists())
                    throw new RuntimeException('Unregistered Page connection found. Contact the system administrator.');
                $channel->fill(['tenant_id' => $grant->tenant_id, 'ai_agent_id' => $grant->ai_agent_id, 'website_id' => null,
                    'type' => 'facebook', 'provider' => 'meta', 'name' => $page['name'], 'status' => 'pending',
                    'external_account_id' => $page['id'], 'external_sender_id' => $page['id']]);
                $channel->save();
                if (!$claim) DB::table('messenger_page_claims')->insert(['page_id' => $page['id'], 'tenant_id' => $grant->tenant_id, 'channel_connection_id' => $channel->id]);
                // Subscription has no customer-visible messages; DB rollback keeps failed connections inactive.
                $graph->subscribe((string) $page['id'], $page['access_token']);
                $channel->fill(['credentials' => ['page_access_token' => $page['access_token']],
                    'settings' => array_merge($channel->settings ?? [], ['verified_name' => $page['name']]),
                    'status' => 'active', 'connected_at' => now(), 'last_health_check_at' => now(), 'last_error' => null])->save();
                $grant->update(['pages' => null, 'status' => 'used']);
                return $channel;
            });
            return redirect()->route('admin.channels.facebook.edit', $channel)->with('success', 'Facebook Page connected. Its messages now use your selected AI assistant.');
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->withErrors(['facebook' => 'Page ownership changed or the connection could not be saved. Refresh and try again.']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['facebook' => get_class($e) === RuntimeException::class ? $e->getMessage() : 'The connection could not be saved. Please try again.']);
        }
    }
    public function edit(Request $request, ChannelConnection $channel)
    {
        $this->authorizeConnection($request, $channel);
        $agents = AiAgent::where('tenant_id', $channel->tenant_id)->orderBy('name')->get();
        return view('admin.channels.facebook.edit', compact('channel', 'agents'));
    }
    public function update(Request $request, ChannelConnection $channel)
    {
        $this->authorizeConnection($request, $channel);
        $data = $request->validate(['name' => ['required', 'string', 'max:191'], 'ai_agent_id' => ['required', 'integer']]);
        AiAgent::where('tenant_id', $channel->tenant_id)->findOrFail($data['ai_agent_id']);
        $channel->update($data);
        return back()->with('success', 'Page settings saved. New messages use this assistant’s shared knowledge.');
    }
    public function disconnect(Request $request, ChannelConnection $channel)
    {
        $this->authorizeConnection($request, $channel);
        DB::transaction(function () use ($channel) {
            $locked = ChannelConnection::whereKey($channel->id)->lockForUpdate()->firstOrFail();
            $locked->update(['status' => 'disconnected', 'credentials' => null, 'last_error' => null]);
        });
        // Retain ownership/history. Ignore future events locally. Do not remove other products' Page subscriptions.
        return back()->with('success', 'Disconnected locally. Messages will no longer be processed or sent. You can also revoke this app in Facebook Business Integrations.');
    }
    public function health(Request $request, ChannelConnection $channel, GraphClient $graph)
    {
        $this->authorizeConnection($request, $channel);
        try {
            if (!$channel->credentials || !data_get($channel->credentials, 'page_access_token')) throw new RuntimeException('Reconnect this Page first.');
            $result = $graph->request('GET', $channel->external_sender_id.'/subscribed_apps', data_get($channel->credentials, 'page_access_token'));
            $subscription = collect($result['data'] ?? [])->first(fn ($a) => (string) ($a['id'] ?? '') === (string) config('messenger.app_id'));
            if (!$subscription || array_diff(['messages', 'messaging_postbacks'], $subscription['subscribed_fields'] ?? [])) throw new RuntimeException('Page subscription missing. Reconnect this Page.');
            $channel->update(['last_health_check_at' => now(), 'last_error' => null]);
            return back()->with('success', 'Meta accepted the token and confirmed the Page subscription.');
        } catch (RuntimeException $e) {
            $channel->update(['last_health_check_at' => now(), 'last_error' => $e->getMessage()]);
            return back()->withErrors(['facebook' => get_class($e) === RuntimeException::class ? $e->getMessage() : 'The operation could not be completed. Please try again.']);
        }
    }
}

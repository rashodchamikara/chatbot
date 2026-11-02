<?php
namespace App\Services\Omnichannel\Messenger;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GraphClient
{
    public function request(string $method, string $path, ?string $token = null, array $data = []): array
    {
        $version = config('messenger.graph_version');
        if (!preg_match('/^v[0-9]+\.[0-9]+$/', (string) $version)) throw new RuntimeException('Invalid Messenger API version.');
        $http = Http::acceptJson()->connectTimeout(10)->timeout(30)->withOptions(['allow_redirects' => false]);
        if ($token) {
            $http = $http->withToken($token);
            $data['appsecret_proof'] = hash_hmac('sha256', $token, config('messenger.app_secret'));
        }
        try {
            // Never retry POST sends: a timeout may occur after Meta accepted the message.
            $response = $http->send($method, 'https://graph.facebook.com/'.$version.'/'.ltrim($path, '/'),
                [strtoupper($method) === 'GET' ? 'query' : 'form_params' => $data]);
        } catch (Throwable $e) {
            // HTTP exception text can include tokens or OAuth codes. Do not propagate it.
            throw new RuntimeException('Meta could not be reached. Check connection health before retrying.');
        }
        $body = $response->json();
        if (!$response->successful() || !is_array($body) || isset($body['error'])) {
            $code = (int) data_get($body, 'error.code', 0);
            throw new RuntimeException('Meta rejected the request (code '.$code.'). Reconnect or check app permissions.', $code);
        }
        return $body;
    }

    public function pages(string $token): array
    {
        $pages = []; $after = null; $seen = [];
        do {
            $args = ['fields' => 'id,name,access_token,tasks', 'limit' => 100];
            if ($after) $args['after'] = $after;
            $result = $this->request('GET', 'me/accounts', $token, $args);
            foreach ($result['data'] ?? [] as $page) {
                if (preg_match('/^[0-9]+$/', (string) ($page['id'] ?? '')) && !empty($page['access_token'])
                    && array_intersect(['MESSAGING', 'MANAGE', 'MODERATE'], $page['tasks'] ?? [])) {
                    $pages[(string) $page['id']] = $page;
                }
            }
            $after = !empty($result['paging']['next']) ? data_get($result, 'paging.cursors.after') : null;
            if ($after && isset($seen[$after])) throw new RuntimeException('Meta Page pagination did not complete. Please reconnect.');
            if ($after) $seen[$after] = true;
            if (count($seen) > 100) throw new RuntimeException('Too many Pages. Grant access to fewer Pages and reconnect.');
        } while ($after);
        return $pages;
    }

    public function subscribe(string $pageId, string $token): void
    {
        // Preserve any other Page webhook fields used by this same Meta app.
        $existing = $this->request('GET', $pageId.'/subscribed_apps', $token);
        $fields = ['messages', 'messaging_postbacks'];
        foreach ($existing['data'] ?? [] as $app) {
            if ((string) ($app['id'] ?? '') === (string) config('messenger.app_id')) {
                $fields = array_merge($fields, $app['subscribed_fields'] ?? []);
            }
        }
        $result = $this->request('POST', $pageId.'/subscribed_apps', $token, ['subscribed_fields' => implode(',', array_unique($fields))]);
        if (empty($result['success'])) throw new RuntimeException('Meta did not confirm the Page subscription.');
    }
}

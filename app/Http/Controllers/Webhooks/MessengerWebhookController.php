<?php
namespace App\Http\Controllers\Webhooks;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessMessengerWebhookJob;
use App\Models\InboundWebhookEvent;
use App\Services\Omnichannel\Messenger\Payload;
use Illuminate\Http\Request;

class MessengerWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $token = (string) config('messenger.verify_token');
        abort_unless($token !== '' && $request->query('hub_mode') === 'subscribe'
            && hash_equals($token, (string) $request->query('hub_verify_token')), 403);
        return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
    }
    public function receive(Request $request)
    {
        $raw = $request->getContent();
        abort_if(strlen($raw) > 2 * 1024 * 1024, 413);
        abort_unless(Payload::signatureValid($raw, $request->header('X-Hub-Signature-256'), (string) config('messenger.app_secret')), 403);
        $payload = json_decode($raw, true);
        abort_unless(is_array($payload), 400);
        if (($payload['object'] ?? '') !== 'page') return response('EVENT_RECEIVED');
        // Persistent receipt first. Concurrent retries collapse via provider/payload_hash unique index.
        $event = InboundWebhookEvent::firstOrCreate(['provider' => 'meta_messenger', 'payload_hash' => hash('sha256', $raw)],
            ['event_type' => 'page', 'payload' => $raw, 'status' => 'pending', 'attempts' => 0, 'received_at' => now()]);
        if ($event->status !== 'processed') ProcessMessengerWebhookJob::dispatch($event->id);
        return response('EVENT_RECEIVED');
    }
}

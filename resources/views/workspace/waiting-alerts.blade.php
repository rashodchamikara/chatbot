<div id="cn-waiting-alerts" data-user="{{ auth()->id() }}" data-feed="{{ route('admin.conversations.index', ['mode' => 'live_waiting', 'status' => 'active', '_waiting_alert_feed' => 1]) }}" data-inbox="{{ route('admin.conversations.index', ['mode' => 'live_waiting', 'status' => 'active']) }}">
    <details class="cn-alert-settings">
        <summary>Chat alerts <span id="cn-alert-count" aria-label="Waiting conversations">—</span></summary>
        <div class="cn-alert-controls">
            <p id="cn-alert-status" role="status">Checking waiting conversations…</p>
            <button type="button" id="cn-alert-sound">Enable sound</button>
            <button type="button" id="cn-alert-test">Test sound</button>
            <button type="button" id="cn-alert-desktop">Enable desktop notifications</button>
            <label><input type="checkbox" id="cn-alert-paused"> Pause alerts in this browser</label>
            <a href="{{ route('admin.conversations.index', ['mode' => 'live_waiting', 'status' => 'active']) }}">Open waiting inbox</a>
            <small>Keep ChatNivo open. Sound may need enabling again after navigation. Desktop alerts require browser permission.</small>
        </div>
    </details>
    <section id="cn-alert-toast" class="cn-alert-toast" aria-label="Waiting chat notification" hidden>
        <div class="cn-alert-toast-heading"><strong>Customer waiting for an agent</strong><button type="button" id="cn-alert-dismiss" aria-label="Dismiss notification">×</button></div>
        <p id="cn-alert-message" role="status" aria-live="polite" aria-atomic="true"></p>
        <a id="cn-alert-open" class="cn-button" href="{{ route('admin.conversations.index', ['mode' => 'live_waiting', 'status' => 'active']) }}">Open conversation</a>
    </section>
</div>
<style>
.cn-alert-settings{position:fixed;bottom:16px;right:20px;z-index:70;width:240px;max-width:calc(100vw - 32px);font-size:12px;border:1px solid #c9d7e5;background:white;color:#17243a;border-radius:12px;box-shadow:0 8px 28px #10243a24}
.cn-alert-settings summary{padding:12px 16px;cursor:pointer;font-weight:700}.cn-alert-settings summary span{float:right;background:#f8c76b;color:#39250a;padding:1px 8px;border-radius:20px}
.cn-alert-controls{padding:0 16px 16px;display:grid;gap:12px;max-height:60vh;overflow:auto}.cn-alert-controls button,.cn-alert-controls a{padding:8px;border:1px solid #c9d7e5;border-radius:6px;text-align:left}.cn-alert-controls small{color:#52657a;line-height:1.5}
.cn-alert-toast{position:fixed;bottom:78px;right:20px;width:360px;max-width:calc(100vw - 32px);z-index:71;border:1px solid #0e9f91;border-top:4px solid #0e9f91;border-radius:14px;background:white;color:#17243a;padding:18px;box-shadow:0 12px 40px #10243a33}
.cn-alert-toast[hidden]{display:none}.cn-alert-toast-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:14px}.cn-alert-toast-heading button{font-size:24px;padding:0 5px}.cn-alert-toast p{font-size:13px;line-height:1.6;margin:12px 0;overflow-wrap:anywhere}.cn-alert-toast a{margin-top:4px}
.cn-alert-settings[open] ~ .cn-alert-toast{bottom:auto;top:24px}
@media(max-width:560px){.cn-alert-settings,.cn-alert-toast{right:16px}}
</style>
<script>
@include('workspace.waiting-alerts-script')
</script>

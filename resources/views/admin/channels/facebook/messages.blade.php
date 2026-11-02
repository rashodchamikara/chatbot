@if(session('success'))<div class="cn-panel" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="cn-panel" role="alert"><strong>We could not complete that step.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

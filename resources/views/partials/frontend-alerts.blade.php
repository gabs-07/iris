@if(session('success'))
  <div class="backend-alert success">{{ session('success') }}</div>
@endif
@if(session('warning'))
  <div class="backend-alert warning">{{ session('warning') }}</div>
@endif
@if(session('message'))
  <div class="backend-alert success">{{ session('message') }}</div>
@endif
@if($errors->any())
  <div class="backend-alert danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

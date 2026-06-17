@if(auth()->check() && auth()->user()->isPaciente())
<a class="floating-urgent-btn" href="{{ route('paciente.auxilio') }}" title="Solicitar ayuda urgente" aria-label="Solicitar ayuda urgente">
  <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"></circle><path d="M12 8v4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"></path><path d="M12 16h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"></path></svg></span>
  Auxilio
</a>
@elseif(!auth()->check())
<a class="floating-urgent-btn" href="{{ route('guest.auxilio') }}" title="Solicitar ayuda urgente sin cuenta" aria-label="Solicitar ayuda urgente sin cuenta">
  <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"></circle><path d="M12 8v4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"></path><path d="M12 16h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"></path></svg></span>
  Auxilio
</a>
@endif

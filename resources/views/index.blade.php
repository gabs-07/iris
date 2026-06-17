<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRIS | Tu bienestar mental, nuestra prioridad</title>
    <meta name="description" content="Conecta con psicólogos validados, gestiona tu progreso terapéutico y encuentra apoyo Auxilio. Plataforma SaaS integral de salud mental.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body class="landing-body">

    <!-- ═══════════════════════════════════
         HEADER
    ═══════════════════════════════════ -->
    <header class="main-header" id="main-header">
        <a href="{{ url('/') }}" class="logo iris-brand-logo"><img src="{{ asset('img/iris-logo.png') }}" alt="IRIS" class="brand-logo-image"></a>

        <nav class="desktop-nav">
            <a href="#buscar">Encontrar Psicólogo</a>
            <a href="#como-funciona">Cómo funciona</a>
            <a href="#profesionales">Para Profesionales</a>
        </nav>

        <div class="header-actions">
            <a href="{{ url('/login') }}" class="btn-nav-login">Iniciar Sesión</a>
            <a href="{{ url('/registro') }}" class="btn-nav-register">Crear Cuenta</a>
            <button class="hamburger" id="hamburger" aria-label="Menú">
                <span></span><span></span><span></span>
            </button>
        </div>
    </header>

    <!-- Menú móvil -->
    <div class="mobile-menu" id="mobile-menu">
        <nav>
            <a href="#buscar">Encontrar Psicólogo</a>
            <a href="#como-funciona">Cómo funciona</a>
            <a href="#profesionales">Para Profesionales</a>
        </nav>
        <div class="mobile-actions">
            <a href="{{ url('/login') }}" class="btn-outline">Iniciar Sesión</a>
            <a href="{{ url('/registro') }}" class="btn-primary">Crear Cuenta</a>
        </div>
    </div>

    <main>

        <!-- ═══════════════════════════════════
             HERO
        ═══════════════════════════════════ -->
        <section class="hero">
            <div class="hero-orb orb-green"></div>
            <div class="hero-orb orb-warm"></div>

            <div class="hero-content">
                <div class="hero-badge">
                    <span class="dot"></span>
                    Atención segura y confidencial
                </div>

                <h1>Tu espacio seguro<br>para <em>sanar</em> y crecer.</h1>

                <p>Conecta con especialistas validados, gestiona tu progreso terapéutico y encuentra apoyo Auxilio en nuestra comunidad. No estás solo.</p>

                <div class="hero-actions">
                    <a href="#buscar" class="btn-primary btn-lg">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        Encontrar mi especialista
                    </a>
                    <a href="#urgencia" class="btn-outline btn-lg">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m13 2-2 6.9H3l6 5.1-2.3 7L13 16.2l6.3 4.8-2.3-7 6-5.1h-8Z"/></svg>
                        Necesito ayuda urgente
                    </a>
                </div>

                <!--
                <div class="hero-trust">
                    <div class="trust-chip">
                        <span class="number">+1,200</span>
                        <span class="label">Psicólogos validados</span>
                    </div>
                    <div class="trust-divider"></div>
                    <div class="trust-chip">
                        <span class="number">98%</span>
                        <span class="label">Satisfacción de pacientes</span>
                    </div>
                    <div class="trust-divider"></div>
                    <div class="trust-chip">
                        <span class="number">Auxilio</span>
                        <span class="label">Soporte disponible</span>
                    </div>
                </div> -->
            </div>

            <!-- Visual ilustrativo -->
            <div class="hero-visual">
                <!-- Tarjeta principal: Próxima cita -->
                <div class="hero-card card-main">
                    <div class="card-header-row">
                        <span class="card-title">Citas de Hoy</span>
                        <span class="card-date">Agenda IRIS</span>
                    </div>

                    <div class="appointment-row">
                        <div class="appt-avatar">IR</div>
                        <div class="appt-info">
                            <strong>Especialista aprobado</strong>
                            <span>Psicología Clínica</span>
                        </div>
                        <span class="appt-time">Horario</span>
                    </div>

                    <div class="appointment-row">
                        <div class="appt-avatar" style="background: linear-gradient(135deg, #7a9488, #a8d5be);">IR</div>
                        <div class="appt-info">
                            <strong>Especialista aprobado</strong>
                            <span>Cognitivo-Conductual</span>
                        </div>
                        <span class="appt-time">Horario</span>
                    </div>

                </div>

                <!-- Tarjeta mood tracker -->
                <div class="hero-card card-mood">
                    <div class="mood-title">Tu estado esta semana</div>
                    <div class="mood-row">
                        <div class="mood-bar-wrap">
                            <div class="bar"><div class="fill" style="height:60%"></div></div>
                            <span class="day">L</span>
                        </div>
                        <div class="mood-bar-wrap">
                            <div class="bar"><div class="fill" style="height:45%"></div></div>
                            <span class="day">M</span>
                        </div>
                        <div class="mood-bar-wrap">
                            <div class="bar"><div class="fill" style="height:75%"></div></div>
                            <span class="day">X</span>
                        </div>
                        <div class="mood-bar-wrap">
                            <div class="bar"><div class="fill" style="height:85%"></div></div>
                            <span class="day">J</span>
                        </div>
                        <div class="mood-bar-wrap">
                            <div class="bar"><div class="fill" style="height:90%"></div></div>
                            <span class="day">V</span>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta alerta -->
                <div class="hero-card card-alert">
                    <div class="alert-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 8v4M12 16h.01"/></svg>
                    </div>
                    <strong>Modo Escucha activo</strong>
                    <span>3 psicólogos disponibles ahora</span>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════
             BUSCADOR INTELIGENTE
        ═══════════════════════════════════ -->
        <section id="buscar" class="search-section">
            <div class="search-panel">
                <div class="search-field">
                    <label>Especialidad o Tema</label>
                    <input type="text" placeholder="Ej. Ansiedad, Terapia de pareja, Depresión...">
                </div>
                <div class="search-field">
                    <label>Modalidad</label>
                    <select>
                        <option value="" disabled selected>Seleccionar...</option>
                        <option>Videollamada</option>
                        <option>Presencial</option>
                        <option>Ambas modalidades</option>
                    </select>
                </div>
                <button class="btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    Buscar
                </button>
            </div>
        </section>

        <!-- ═══════════════════════════════════
             STATS
        ═══════════════════════════════════
        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">+1,200<span>+</span></div>
                    <div class="stat-label">Psicólogos validados y certificados</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">15,000<span>+</span></div>
                    <div class="stat-label">Pacientes activos en la plataforma</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">98<span>%</span></div>
                    <div class="stat-label">Tasa de satisfacción general</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">24<span>/7</span></div>
                    <div class="stat-label">Soporte de emergencia disponible</div>
                </div>
            </div>
        </section> -->


        <!-- ═══════════════════════════════════
             FEATURES — CÓMO FUIRIONA
        ═══════════════════════════════════ -->
        <section id="como-funciona" class="features-section">
            <div class="section-header">
                <span class="section-label">Cómo funciona</span>
                <h2 class="section-title">Mucho más que una cita en línea</h2>
                <p class="section-subtitle">Un ecosistema completo diseñado para tu continuidad terapéutica.</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <h3>Agenda Inteligente</h3>
                    <p>Reserva citas en segundos con disponibilidad en tiempo real. Recibe recordatorios automáticos para no perder ninguna sesión.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <h3>Seguimiento Terapéutico</h3>
                    <p>Visualiza tareas, ejercicios y lecturas asignadas por tu profesional. Registra tu progreso diario y emocional en tiempo real.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h3>Comunidad de Apoyo</h3>
                    <p>Accede a foros moderados por profesionales para soporte emocional entre sesiones. Un espacio seguro para compartir y crecer.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4a2 2 0 0 1 1.99-2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6.13 6.13l.93-.93a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <h3>Videollamada Integrada</h3>
                    <p>Sesiones seguras directamente en la plataforma, sin instalar nada. Cifrado de extremo a extremo para tu total privacidad.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622C17.176 19.29 21 14.591 21 9c0-1.073-.15-2.11-.418-3.07"/></svg>
                    </div>
                    <h3>Expediente Protegido</h3>
                    <p>Tu historial clínico almacenado con los más altos estándares de seguridad. Solo tú y tu especialista tienen acceso.</p>
                </div>

                <!--
                <div class="feature-card">
                    <div class="feature-icon-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <h3>Métricas de Bienestar</h3>
                    <p>Visualiza tu evolución emocional con gráficas de progreso. Identifica patrones y celebra cada pequeño avance en tu camino.</p>
                </div> -->
            </div>
        </section>

        <!-- ═══════════════════════════════════
             URGEIRIA
        ═══════════════════════════════════ -->
        <section id="urgencia" class="urgent-section">
            <div class="urgent-inner">
                <span class="urgent-tag">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    Disponible Auxilio
                </span>

                <h2>¿Estás pasando por una crisis ahora mismo?</h2>

                <p>Nuestro Botón de Auxilio te conecta de manera voluntaria y confidencial con profesionales en "Modo Escucha" para contención inmediata.</p>

                <a class="btn-urgent" href="{{ route('guest.auxilio') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    Activar Botón de Auxilio
                </a>

                <p class="urgent-disclaimer">
                    * Este servicio no suple a las emergencias médicas gubernamentales (911).<br>
                    Es un apoyo emocional voluntario, no un servicio de crisis clínica.
                </p>
            </div>
        </section>

        <!-- ═══════════════════════════════════
             BENEFICIOS — PACIENTES Y PSICÓLOGOS
        ═══════════════════════════════════ -->
        <section id="profesionales" class="benefits-section">
            <div class="benefits-grid">
                <div class="benefit-col patient-col">
                    <h3>Para Pacientes</h3>
                    <div class="benefit-list">
                        <div class="benefit-item">
                            <div class="benefit-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <span>Acceso a especialistas 100% validados y certificados.</span>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <span>Privacidad absoluta de tu expediente y tus sesiones.</span>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <span>Herramientas para seguir tu progreso diario y emocional.</span>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <span>Comunidad segura y moderada para compartir experiencias.</span>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <span>Botón de Auxilio con respuesta inmediata Auxilio.</span>
                        </div>
                    </div>
                    <a href="{{ url('/registro') }}" class="btn-primary">Comenzar como Paciente</a>
                </div>

                <div class="benefit-col psych-col">
                    <h3>Para Psicólogos</h3>
                    <div class="benefit-list">
                        <div class="benefit-item">
                            <div class="benefit-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <span>Herramienta integral de gestión clínica y agenda.</span>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <span>Prescripción digital de tareas, ejercicios y lecturas.</span>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <span>Captación automatizada de pacientes calificados.</span>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <span>Control total de tu disponibilidad, agenda y tarifas.</span>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-check">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <span>Perfil público validado para aumentar tu visibilidad.</span>
                        </div>
                    </div>
                    <a href="{{ url('/registro') }}" class="btn-outline dark">Unirme como Profesional</a>
                </div>
            </div>
        </section>

    </main>

    <!-- ═══════════════════════════════════
         FOOTER
    ═══════════════════════════════════ -->
    <footer class="main-footer">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="{{ url('/') }}" class="logo iris-brand-logo"><img src="{{ asset('img/iris-logo.png') }}" alt="IRIS" class="brand-logo-image"></a>
                <p>Plataforma de salud mental y seguimiento clínico. Conectamos pacientes con especialistas validados.</p>
            </div>

            <div class="footer-col">
                <h4>Plataforma</h4>
                <nav>
                    <a href="#buscar">Buscar Especialista</a>
                    <a href="#comunidad">Comunidad</a>
                    <a href="#urgencia">Ayuda Urgente</a>
                    <a href="{{ url('/registro') }}">Crear Cuenta</a>
                </nav>
            </div>

            <div class="footer-col">
                <h4>Profesionales</h4>
                <nav>
                    <a href="{{ url('/registro') }}">Unirse como Psicólogo</a>
                    <a href="#">Cómo funciona</a>
                    <a href="#">Tarifas y Planes</a>
                </nav>
            </div>

            <div class="footer-col">
                <h4>Legal</h4>
                <nav>
                    <a href="{{ url('/privacidad') }}">Aviso de Privacidad</a>
                    <a href="{{ url('/terminos') }}">Términos de Servicio</a>
                    <a href="{{ url('/legal/view/consentimiento_datos_sensibles') }}">Protección de Datos</a>
                    <a href="{{ url('/registro') }}">Contacto</a>
                </nav>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 IRIS. Todos los derechos reservados.</p>
            <p>Diseñado con cuidado para el bienestar mental.</p>
        </div>
    </footer>

    <script>
        // ─── Header scroll effect ───
        const header = document.getElementById('main-header');
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 20);
        }, { passive: true });

        // ─── Hamburger menu ───
        const hamburger = document.getElementById('hamburger');
        const mobileMenu = document.getElementById('mobile-menu');

        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('open');
            mobileMenu.classList.toggle('open');
        });

        // Cerrar al click en links
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.remove('open');
                mobileMenu.classList.remove('open');
            });
        });

        // ─── Animate on scroll (simple Intersection Observer) ───
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.feature-card, .stat-item, .benefit-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(el);
        });
    </script>

</body>
</html>
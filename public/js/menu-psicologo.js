document.addEventListener("DOMContentLoaded", () => {
  const base = "";
  const userRole = document.body?.dataset?.role || document.querySelector('meta[name="user-role"]')?.getAttribute("content") || "psicologo";

  const icons = {
    panel: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19h16"/><path d="M4 15h7"/><path d="M4 11h5"/><path d="M4 7h3"/></svg>',
    agenda: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    pacientes: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    prescripciones: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2z"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>',
    diarios: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>',
    chat: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>',
    comunidad: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    perfil: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>',
    salir: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>'
  };

  const navItems = [
    ["/psicologo/dashboard-psicologo", "Panel clínico", icons.panel],
    ["/psicologo/agenda-psicologo", "Agenda", icons.agenda],
    ["/psicologo/pacientes-psicologo", "Mis pacientes", icons.pacientes],
    ["/psicologo/diarios-autorizados", "Diarios autorizados", icons.diarios],
    ["/psicologo/chat-profesional", "Chat profesional", icons.chat],
    ...(userRole === "psiquiatra" ? [["/psicologo/prescripciones", "Prescripciones", icons.prescripciones]] : []),
    ["/comunidad/comunidad", "Comunidad", icons.comunidad],
  ];

  const footerItems = [
    ["/psicologo/perfil-psicologo", "Mi Perfil", icons.perfil],
    ["/logout", "Cerrar sesión", icons.salir]
  ];

  const renderLink = ([href, label, icon]) => {
    const url = `${base}${href}`;
    const isActive = window.location.pathname.endsWith(href);
    if (href === "/logout") {
      return `<a href="${url}" class="js-logout"><span class="nav-icon">${icon}</span>${label}</a>`;
    }
    return `<a href="${url}" class="${isActive ? "active" : ""}"><span class="nav-icon">${icon}</span>${label}</a>`;
  };

  const sidebarHTML = `
    <aside class="sidebar">
      <div class="sidebar-mobile-header">
        <a href="${base}/psicologo/dashboard-psicologo" class="logo sidebar-logo iris-sidebar-logo" aria-label="IRIS">
          <img src="${base}/img/iris-logo.png" class="sidebar-logo-full" alt="IRIS">
        </a>
        <button class="hamburger-btn" id="mobile-menu-toggle" aria-label="Abrir menú"><span></span><span></span><span></span></button>
      </div>

      <a href="${base}/psicologo/dashboard-psicologo" class="logo sidebar-logo iris-sidebar-logo desktop-only" aria-label="IRIS">
        <img src="${base}/img/iris-logo.png" class="sidebar-logo-full" alt="IRIS">
      </a>
<nav class="sidebar-nav">${navItems.map(renderLink).join("")}</nav>
      <div class="sidebar-footer">${footerItems.map(renderLink).join("")}</div>
    </aside>`;

  const menuContainer = document.getElementById("menu-dinamico");
  if (!menuContainer) return;

  menuContainer.innerHTML = sidebarHTML;

  menuContainer.querySelector(".js-logout")?.addEventListener("click", async (event) => {
    event.preventDefault();
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
    await fetch("/logout", {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": token,
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
      },
      credentials: "same-origin"
    });
    window.location.href = "/login";
  });

  document.getElementById("mobile-menu-toggle")?.addEventListener("click", () => {
    document.body.classList.toggle("menu-open");
    document.body.style.overflow = document.body.classList.contains("menu-open") ? "hidden" : "";
  });
});

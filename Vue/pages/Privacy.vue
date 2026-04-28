<template>
  <div class="privacy-page">

    <!-- ═══════════════════════════════════════════════════
         HERO
    ═══════════════════════════════════════════════════ -->
    <section class="privacy-hero">
      <div class="privacy-hero-inner">
        <div class="privacy-hero-icon">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h1 class="privacy-hero-title">Política de Privacidad</h1>
        <p class="privacy-hero-sub">
          Tu privacidad es importante para nosotros. Aquí explicamos con total transparencia
          qué datos recopilamos, cómo los usamos y qué derechos tienes sobre ellos.
        </p>
        <div class="privacy-hero-meta">
          <span class="meta-badge">
            <i class="fa-solid fa-calendar-check"></i>
            Última actualización: 14 de abril de 2026
          </span>
          <span class="meta-badge">
            <i class="fa-solid fa-globe"></i>
            Aplicable en la UE y el resto del mundo
          </span>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════
         LAYOUT PRINCIPAL
    ═══════════════════════════════════════════════════ -->
    <div class="privacy-layout">

      <!-- ÍNDICE LATERAL -->
      <aside class="privacy-sidebar">
        <div class="toc-card">
          <p class="toc-eyebrow">
            <i class="fa-solid fa-list-ul"></i>
            Índice
          </p>
          <nav class="toc-nav">
            <a
              v-for="section in sections"
              :key="section.id"
              :href="`#${section.id}`"
              :class="['toc-item', { active: activeSection === section.id }]"
              @click.prevent="scrollTo(section.id)"
            >
              <span class="toc-num">{{ section.num }}</span>
              <span>{{ section.title }}</span>
            </a>
          </nav>
        </div>

        <div class="privacy-contact-card">
          <i class="fa-solid fa-envelope-open-text privacy-contact-icon"></i>
          <p class="privacy-contact-title">¿Tienes dudas sobre tus datos?</p>
          <p class="privacy-contact-sub">Puedes ejercer tus derechos o consultar cualquier cuestión escribiéndonos.</p>
          <a href="mailto:privacidad@testmaster.app" class="privacy-contact-btn">
            privacidad@testmaster.app
          </a>
        </div>

        <div class="rights-summary-card">
          <p class="toc-eyebrow">
            <i class="fa-solid fa-user-shield"></i>
            Tus derechos (RGPD)
          </p>
          <div class="rights-list">
            <div v-for="right in gdprRights" :key="right.key" class="right-item">
              <div :class="['right-dot', `right-dot--${right.color}`]"></div>
              <span>{{ right.label }}</span>
            </div>
          </div>
        </div>
      </aside>

      <!-- CONTENIDO -->
      <article class="privacy-content">

        <!-- Resumen ejecutivo -->
        <div class="summary-banner">
          <div class="summary-banner-icon">
            <i class="fa-solid fa-circle-info"></i>
          </div>
          <div>
            <p class="summary-banner-title">Resumen en lenguaje sencillo</p>
            <p class="summary-banner-text">
              Solo recopilamos los datos estrictamente necesarios para que el servicio funcione.
              No vendemos ni alquilamos tu información a terceros, jamás.
              Puedes pedirnos que eliminemos tu cuenta y todos tus datos en cualquier momento.
            </p>
          </div>
        </div>

        <!-- Secciones -->
        <section
          v-for="section in sections"
          :key="section.id"
          :id="section.id"
          class="privacy-section"
        >
          <div class="section-heading">
            <div :class="['section-num-badge', `badge--${section.color}`]">
              {{ section.num }}
            </div>
            <h2 class="section-title-text">{{ section.title }}</h2>
          </div>

          <!-- Párrafos de texto -->
          <template v-if="section.content">
            <p
              v-for="(para, i) in section.content"
              :key="i"
              class="section-para"
              v-html="para"
            ></p>
          </template>

          <!-- Tabla de datos -->
          <div v-if="section.table" class="section-table-wrap">
            <table class="section-table">
              <thead>
                <tr>
                  <th v-for="col in section.table.headers" :key="col">{{ col }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, i) in section.table.rows" :key="i">
                  <td v-for="(cell, j) in row" :key="j">{{ cell }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Lista de items -->
          <ul v-if="section.list" class="section-list">
            <li v-for="(item, i) in section.list" :key="i" class="section-list-item">
              <i class="fa-solid fa-circle-check list-check"></i>
              <span v-html="item"></span>
            </li>
          </ul>

          <!-- Bloque destacado -->
          <div v-if="section.highlight" :class="['section-highlight', `highlight--${section.highlight.type}`]">
            <i :class="['highlight-icon', section.highlight.icon]"></i>
            <p v-html="section.highlight.text"></p>
          </div>

        </section>

        <!-- Pie de documento -->
        <div class="privacy-footer-block">
          <div class="privacy-footer-icon">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <p class="privacy-footer-title">TestMaster se compromete con tu privacidad</p>
          <p class="privacy-footer-sub">
            Esta política puede actualizarse. Te notificaremos por correo ante cambios significativos.
            La versión vigente siempre estará disponible en esta página.
          </p>
          <div class="privacy-footer-actions">
            <a href="mailto:privacidad@testmaster.app" class="privacy-footer-btn privacy-footer-btn--primary">
              <i class="fa-solid fa-envelope"></i>
              Contactar DPO
            </a>
            <router-link to="/help" class="privacy-footer-btn privacy-footer-btn--ghost">
              <i class="fa-solid fa-circle-question"></i>
              Centro de ayuda
            </router-link>
          </div>
        </div>

      </article>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const activeSection = ref('');

// ─── Secciones ────────────────────────────────────────────────────────────────
const sections = [
  {
    id: 'responsable',
    num: '01',
    color: 'blue',
    title: 'Responsable del tratamiento',
    content: [
      'El responsable del tratamiento de los datos personales recogidos a través de TestMaster es <strong>TestMaster S.L.</strong> (en adelante, "TestMaster", "nosotros" o "la empresa").',
      'Puedes contactar con nuestro Delegado de Protección de Datos (DPO) en cualquier momento escribiendo a <strong>privacidad@testmaster.app</strong>.',
    ],
    highlight: {
      type: 'info',
      icon: 'fa-solid fa-building',
      text: '<strong>TestMaster S.L.</strong> — CIF: B-00000000 — Calle Ejemplo 1, 28001 Madrid, España',
    },
  },
  {
    id: 'datos-recogidos',
    num: '02',
    color: 'purple',
    title: 'Datos que recopilamos',
    content: [
      'Recopilamos únicamente los datos necesarios para prestar el servicio. A continuación detallamos qué información tratamos y con qué finalidad.',
    ],
    table: {
      headers: ['Categoría', 'Datos concretos', 'Cuándo se recogen'],
      rows: [
        ['Identidad', 'Nombre, correo electrónico', 'Al registrarse'],
        ['Credenciales', 'Contraseña cifrada (hash bcrypt)', 'Al registrarse'],
        ['Perfil', 'Foto de avatar, rol de usuario', 'Opcionales, al configurar el perfil'],
        ['Actividad', 'Tests creados, intentos realizados, puntuaciones', 'Al usar la plataforma'],
        ['Técnicos', 'Dirección IP, timestamps de acceso', 'Automáticamente al conectarse'],
        ['Comunicación', 'Mensajes enviados a soporte', 'Solo si contactas con nosotros'],
      ],
    },
    highlight: {
      type: 'success',
      icon: 'fa-solid fa-circle-check',
      text: 'No recopilamos datos sensibles (salud, ideología, origen étnico, etc.) ni datos de menores de 16 años sin consentimiento parental verificable.',
    },
  },
  {
    id: 'finalidad',
    num: '03',
    color: 'green',
    title: 'Finalidad y base jurídica',
    content: [
      'Tratamos tus datos con las siguientes finalidades, cada una amparada en una base jurídica del RGPD:',
    ],
    table: {
      headers: ['Finalidad', 'Base jurídica (RGPD)'],
      rows: [
        ['Gestionar tu cuenta y autenticación', 'Art. 6.1.b — Ejecución del contrato'],
        ['Personalizar la experiencia de usuario', 'Art. 6.1.b — Ejecución del contrato'],
        ['Mostrar estadísticas de tests y perfil', 'Art. 6.1.b — Ejecución del contrato'],
        ['Enviarte notificaciones transaccionales', 'Art. 6.1.b — Ejecución del contrato'],
        ['Análisis de uso para mejorar el servicio', 'Art. 6.1.f — Interés legítimo'],
        ['Cumplimiento de obligaciones legales', 'Art. 6.1.c — Obligación legal'],
        ['Enviarte comunicaciones de marketing', 'Art. 6.1.a — Consentimiento'],
      ],
    },
  },
  {
    id: 'conservacion',
    num: '04',
    color: 'orange',
    title: 'Conservación de los datos',
    content: [
      'Los datos se conservan durante el tiempo estrictamente necesario para la finalidad para la que fueron recogidos, y en todo caso según lo exijan las obligaciones legales aplicables.',
    ],
    list: [
      '<strong>Datos de cuenta activa:</strong> mientras mantengas tu cuenta abierta.',
      '<strong>Datos tras dar de baja la cuenta:</strong> 30 días en copia de seguridad cifrada, luego eliminación definitiva.',
      '<strong>Registros de actividad (logs):</strong> máximo 12 meses por motivos de seguridad.',
      '<strong>Facturas y datos fiscales:</strong> 5 años por obligación legal tributaria.',
      '<strong>Mensajes de soporte:</strong> 2 años desde la última interacción.',
    ],
  },
  {
    id: 'destinatarios',
    num: '05',
    color: 'red',
    title: 'Destinatarios y transferencias',
    content: [
      'No vendemos, alquilamos ni cedemos tus datos personales a terceros con fines comerciales. Únicamente los compartimos con los proveedores necesarios para operar el servicio, siempre bajo contrato de encargado del tratamiento conforme al RGPD.',
    ],
    table: {
      headers: ['Proveedor', 'Función', 'País'],
      rows: [
        ['Hetzner / AWS', 'Infraestructura y alojamiento', 'UE / EE. UU. (cláusulas estándar)'],
        ['Mailgun', 'Envío de correos transaccionales', 'EE. UU. (cláusulas estándar)'],
        ['Cloudflare', 'CDN y protección DDoS', 'EE. UU. (cláusulas estándar)'],
      ],
    },
    highlight: {
      type: 'warning',
      icon: 'fa-solid fa-triangle-exclamation',
      text: 'Las transferencias internacionales a EE. UU. se realizan con las garantías adecuadas del Art. 46 RGPD (cláusulas contractuales tipo aprobadas por la Comisión Europea).',
    },
  },
  {
    id: 'derechos',
    num: '06',
    color: 'teal',
    title: 'Tus derechos',
    content: [
      'Como interesado, el RGPD te reconoce los siguientes derechos sobre tus datos personales. Puedes ejercerlos en cualquier momento escribiendo a <strong>privacidad@testmaster.app</strong> con tu nombre y una copia de tu documento de identidad.',
    ],
    list: [
      '<strong>Acceso:</strong> obtener confirmación de si tratamos tus datos y una copia de los mismos.',
      '<strong>Rectificación:</strong> corregir datos inexactos o incompletos.',
      '<strong>Supresión ("derecho al olvido"):</strong> solicitar la eliminación de tus datos cuando ya no sean necesarios.',
      '<strong>Limitación del tratamiento:</strong> solicitar que pausemos el tratamiento en ciertos supuestos.',
      '<strong>Portabilidad:</strong> recibir tus datos en formato estructurado y legible por máquina.',
      '<strong>Oposición:</strong> oponerte al tratamiento basado en interés legítimo o para marketing directo.',
      '<strong>Retirada del consentimiento:</strong> retirar en cualquier momento el consentimiento otorgado sin que ello afecte a la licitud del tratamiento previo.',
    ],
    highlight: {
      type: 'info',
      icon: 'fa-solid fa-scale-balanced',
      text: 'Si consideras que tus derechos no han sido atendidos, puedes presentar una reclamación ante la <strong>Agencia Española de Protección de Datos (AEPD)</strong> en <strong>aepd.es</strong>.',
    },
  },
  {
    id: 'cookies',
    num: '07',
    color: 'indigo',
    title: 'Cookies y tecnologías similares',
    content: [
      'Utilizamos cookies y almacenamiento local del navegador para garantizar el correcto funcionamiento de la plataforma.',
    ],
    table: {
      headers: ['Nombre', 'Tipo', 'Finalidad', 'Duración'],
      rows: [
        ['token', 'Esencial', 'Autenticación de sesión (JWT)', 'Sesión / hasta logout'],
        ['user', 'Esencial', 'Datos básicos del usuario en caché', 'Sesión / hasta logout'],
        ['theme', 'Preferencias', 'Guardar preferencia de tema (oscuro/claro)', 'Persistente (indefinido)'],
      ],
    },
    content2: [
      'No utilizamos cookies de rastreo, analítica de terceros ni publicidad comportamental. Si en el futuro implementamos herramientas de análisis, te informaremos y solicitaremos tu consentimiento.',
    ],
  },
  {
    id: 'seguridad',
    num: '08',
    color: 'blue',
    title: 'Seguridad de los datos',
    content: [
      'Aplicamos medidas técnicas y organizativas adecuadas para proteger tus datos frente a accesos no autorizados, pérdida, destrucción o divulgación accidental.',
    ],
    list: [
      'Todas las comunicaciones están cifradas con <strong>HTTPS / TLS 1.3</strong>.',
      'Las contraseñas se almacenan exclusivamente como <strong>hash bcrypt</strong> con salt único.',
      'El acceso a la base de datos de producción está restringido y auditado.',
      'Realizamos <strong>copias de seguridad cifradas</strong> con periodicidad diaria.',
      'Revisamos periódicamente los permisos de acceso y realizamos pruebas de seguridad.',
    ],
    highlight: {
      type: 'warning',
      icon: 'fa-solid fa-triangle-exclamation',
      text: 'En caso de brecha de seguridad que afecte a tus datos, te notificaremos en el plazo legal (72 horas) conforme al Art. 33-34 RGPD.',
    },
  },
  {
    id: 'menores',
    num: '09',
    color: 'pink',
    title: 'Menores de edad',
    content: [
      'TestMaster no está dirigido a menores de 16 años. No recopilamos conscientemente datos personales de menores sin el consentimiento verificable de sus padres o tutores legales.',
      'Si tienes conocimiento de que un menor nos ha proporcionado datos personales sin autorización, contacta con nosotros en <strong>privacidad@testmaster.app</strong> para proceder a su eliminación inmediata.',
    ],
  },
  {
    id: 'cambios',
    num: '10',
    color: 'gray',
    title: 'Cambios en esta política',
    content: [
      'Podemos actualizar esta Política de Privacidad para reflejar cambios en nuestras prácticas, nuevas funcionalidades o requisitos legales.',
      'Cuando los cambios sean significativos, te lo comunicaremos por correo electrónico con al menos 30 días de antelación. La versión más reciente estará siempre disponible en esta página con la fecha de última actualización indicada en la cabecera.',
    ],
  },
];

// ─── Derechos RGPD (resumen sidebar) ─────────────────────────────────────────
const gdprRights = [
  { key: 'access',       label: 'Acceso',        color: 'blue' },
  { key: 'rect',         label: 'Rectificación', color: 'green' },
  { key: 'erasure',      label: 'Supresión',     color: 'red' },
  { key: 'limit',        label: 'Limitación',    color: 'orange' },
  { key: 'portability',  label: 'Portabilidad',  color: 'purple' },
  { key: 'opposition',   label: 'Oposición',     color: 'teal' },
];

// ─── Scroll spy ───────────────────────────────────────────────────────────────
const scrollTo = (id) => {
  const el = document.getElementById(id);
  if (!el) return;
  const offset = 100;
  const top = el.getBoundingClientRect().top + window.scrollY - offset;
  window.scrollTo({ top, behavior: 'smooth' });
  activeSection.value = id;
};

const handleScroll = () => {
  const ids = sections.map(s => s.id);
  for (let i = ids.length - 1; i >= 0; i--) {
    const el = document.getElementById(ids[i]);
    if (el && el.getBoundingClientRect().top <= 140) {
      activeSection.value = ids[i];
      return;
    }
  }
  activeSection.value = '';
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true });
  activeSection.value = sections[0]?.id || '';
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
/* ── Página ──────────────────────────────────────────────────────────────── */
.privacy-page {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* ── Hero ────────────────────────────────────────────────────────────────── */
.privacy-hero {
  position: relative;
  left: 50%;
  right: 50%;
  margin-left: -50vw;
  margin-right: -50vw;
  width: 100vw;
  margin-top: -2.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 3.5rem 1.5rem 3rem;
}

.privacy-hero-inner {
  max-width: 680px;
  margin: 0 auto;
  text-align: center;
}

.privacy-hero-icon {
  font-size: 2.5rem;
  color: #667eea;
  margin-bottom: 0.75rem;
}

.privacy-hero-title {
  font-size: 2rem;
  font-weight: 700;
  color: white;
  margin-bottom: 0.65rem;
}

.privacy-hero-sub {
  font-size: 0.97rem;
  color: rgba(255,255,255,0.72);
  line-height: 1.7;
  margin-bottom: 1.5rem;
}

.privacy-hero-meta {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
  flex-wrap: wrap;
}

.meta-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 5px 14px;
  border-radius: 20px;
  background: rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.75);
  font-size: 12px;
  border: 1px solid rgba(255,255,255,0.15);
}

.meta-badge i { color: #667eea; }

/* ── Layout ──────────────────────────────────────────────────────────────── */
.privacy-layout {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 1.75rem;
  align-items: start;
}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.privacy-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: sticky;
  top: 80px;
}

.toc-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.25rem;
}

.toc-eyebrow {
  font-size: 11px;
  font-weight: 600;
  color: #a0aec0;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  margin-bottom: 0.85rem;
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.toc-nav {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.toc-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem 0.6rem;
  border-radius: 7px;
  font-size: 12.5px;
  color: #718096;
  text-decoration: none;
  transition: all 0.15s;
}

.toc-item:hover {
  background: #f8f9fa;
  color: #2d3748;
}

.toc-item.active {
  background: #ebf0ff;
  color: #667eea;
  font-weight: 500;
}

.toc-num {
  font-size: 10px;
  font-weight: 700;
  color: #cbd5e0;
  font-variant-numeric: tabular-nums;
  min-width: 20px;
  flex-shrink: 0;
}

.toc-item.active .toc-num { color: #667eea; }

/* Contact card */
.privacy-contact-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.25rem;
  text-align: center;
}

.privacy-contact-icon {
  font-size: 1.5rem;
  color: #667eea;
  margin-bottom: 0.6rem;
  display: block;
}

.privacy-contact-title {
  font-size: 13px;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.35rem;
  line-height: 1.4;
}

.privacy-contact-sub {
  font-size: 12px;
  color: #a0aec0;
  margin-bottom: 0.85rem;
  line-height: 1.5;
}

.privacy-contact-btn {
  display: inline-block;
  font-size: 11.5px;
  font-weight: 600;
  color: #667eea;
  text-decoration: none;
  border: 1px solid #c3cfe2;
  border-radius: 7px;
  padding: 6px 12px;
  transition: all 0.2s;
  word-break: break-all;
}

.privacy-contact-btn:hover {
  background: #ebf0ff;
  border-color: #667eea;
}

/* Rights summary */
.rights-summary-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.25rem;
}

.rights-list {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.right-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 12.5px;
  color: #718096;
}

.right-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.right-dot--blue   { background: #667eea; }
.right-dot--green  { background: #10b981; }
.right-dot--red    { background: #ef4444; }
.right-dot--orange { background: #f59e0b; }
.right-dot--purple { background: #8b5cf6; }
.right-dot--teal   { background: #14b8a6; }

/* ── Contenido ───────────────────────────────────────────────────────────── */
.privacy-content {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* Resumen ejecutivo */
.summary-banner {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  background: #ebf0ff;
  border: 1px solid #c3cfe2;
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.5rem;
}

.summary-banner-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #667eea;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  flex-shrink: 0;
}

.summary-banner-title {
  font-size: 14px;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.35rem;
}

.summary-banner-text {
  font-size: 13.5px;
  color: #4a5568;
  line-height: 1.65;
}

/* Secciones */
.privacy-section {
  padding: 2rem 0;
  border-bottom: 1px solid #f7fafc;
  scroll-margin-top: 100px;
}

.privacy-section:last-of-type {
  border-bottom: none;
}

.section-heading {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  margin-bottom: 1.1rem;
}

.section-num-badge {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
  font-variant-numeric: tabular-nums;
}

.badge--blue   { background: #ebf0ff; color: #4c5fd5; }
.badge--purple { background: #f3e8ff; color: #7c3aed; }
.badge--green  { background: #d1fae5; color: #065f46; }
.badge--orange { background: #fff7ed; color: #c2410c; }
.badge--red    { background: #fee2e2; color: #b91c1c; }
.badge--teal   { background: #ccfbf1; color: #0f766e; }
.badge--indigo { background: #e0e7ff; color: #4338ca; }
.badge--pink   { background: #fce7f3; color: #9d174d; }
.badge--gray   { background: #f1f5f9; color: #475569; }

.section-title-text {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a202c;
}

.section-para {
  font-size: 14px;
  color: #718096;
  line-height: 1.75;
  margin-bottom: 0.75rem;
}

.section-para:last-child { margin-bottom: 0; }

/* Tabla */
.section-table-wrap {
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  margin: 1rem 0;
}

.section-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.section-table th {
  background: #f8f9fa;
  padding: 0.7rem 1rem;
  text-align: left;
  font-weight: 600;
  color: #4a5568;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.section-table td {
  padding: 0.7rem 1rem;
  color: #718096;
  border-bottom: 1px solid #f7fafc;
  vertical-align: top;
  line-height: 1.55;
}

.section-table tr:last-child td {
  border-bottom: none;
}

.section-table tbody tr:hover td {
  background: #fcfcfd;
}

/* Lista */
.section-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  margin: 0.85rem 0 0;
  padding: 0;
}

.section-list-item {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  font-size: 14px;
  color: #718096;
  line-height: 1.65;
}

.list-check {
  color: #10b981;
  font-size: 13px;
  margin-top: 3px;
  flex-shrink: 0;
}

/* Highlight */
.section-highlight {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  border-radius: 10px;
  margin-top: 1rem;
  font-size: 13.5px;
  line-height: 1.6;
}

.section-highlight p { margin: 0; }

.highlight-icon {
  font-size: 14px;
  margin-top: 2px;
  flex-shrink: 0;
}

.highlight--info {
  background: #ebf8ff;
  border: 1px solid #90cdf4;
  color: #2b6cb0;
}

.highlight--info .highlight-icon { color: #3182ce; }

.highlight--success {
  background: #f0fff4;
  border: 1px solid #9ae6b4;
  color: #276749;
}

.highlight--success .highlight-icon { color: #38a169; }

.highlight--warning {
  background: #fffbeb;
  border: 1px solid #fbd38d;
  color: #744210;
}

.highlight--warning .highlight-icon { color: #d97706; }

/* Pie de documento */
.privacy-footer-block {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 2.5rem;
  text-align: center;
  margin-top: 1.5rem;
}

.privacy-footer-icon {
  font-size: 2.5rem;
  color: #667eea;
  margin-bottom: 1rem;
}

.privacy-footer-title {
  font-size: 1.05rem;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 0.5rem;
}

.privacy-footer-sub {
  font-size: 14px;
  color: #a0aec0;
  line-height: 1.65;
  max-width: 420px;
  margin: 0 auto 1.5rem;
}

.privacy-footer-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
  flex-wrap: wrap;
}

.privacy-footer-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 10px 20px;
  border-radius: 9px;
  font-size: 13.5px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s;
  cursor: pointer;
}

.privacy-footer-btn--primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white !important;
}

.privacy-footer-btn--primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(102,126,234,0.4);
}

.privacy-footer-btn--ghost {
  background: transparent;
  border: 1px solid #e2e8f0;
  color: #4a5568;
}

.privacy-footer-btn--ghost:hover {
  background: #f8f9fa;
  border-color: #cbd5e0;
}

/* ── Dark mode ───────────────────────────────────────────────────────────── */
body.dark-mode .toc-card,
body.dark-mode .privacy-contact-card,
body.dark-mode .rights-summary-card,
body.dark-mode .privacy-footer-block {
  background: var(--tm-surface) !important;
  border-color: var(--tm-border) !important;
}

body.dark-mode .section-title-text,
body.dark-mode .summary-banner-title,
body.dark-mode .privacy-footer-title,
body.dark-mode .privacy-contact-title {
  color: var(--tm-text) !important;
}

body.dark-mode .section-para,
body.dark-mode .section-list-item,
body.dark-mode .summary-banner-text,
body.dark-mode .privacy-footer-sub,
body.dark-mode .privacy-contact-sub,
body.dark-mode .right-item,
body.dark-mode .toc-item {
  color: var(--tm-muted) !important;
}

body.dark-mode .toc-eyebrow { color: var(--tm-muted) !important; }

body.dark-mode .toc-item:hover {
  background: var(--tm-hover) !important;
  color: var(--tm-text) !important;
}

body.dark-mode .toc-item.active {
  background: rgba(102,126,234,0.15) !important;
  color: #a78bfa !important;
}

body.dark-mode .toc-item.active .toc-num { color: #a78bfa !important; }

body.dark-mode .summary-banner {
  background: rgba(102,126,234,0.12) !important;
  border-color: rgba(102,126,234,0.25) !important;
}

body.dark-mode .privacy-section {
  border-bottom-color: var(--tm-border) !important;
}

body.dark-mode .section-table-wrap {
  border-color: var(--tm-border) !important;
}

body.dark-mode .section-table th {
  background: var(--tm-bg) !important;
  color: var(--tm-muted) !important;
  border-bottom-color: var(--tm-border) !important;
}

body.dark-mode .section-table td {
  color: var(--tm-muted) !important;
  border-bottom-color: var(--tm-border) !important;
}

body.dark-mode .section-table tbody tr:hover td {
  background: var(--tm-hover) !important;
}

body.dark-mode .highlight--info {
  background: rgba(59,130,246,0.12) !important;
  border-color: rgba(59,130,246,0.3) !important;
  color: #93c5fd !important;
}

body.dark-mode .highlight--success {
  background: rgba(16,185,129,0.1) !important;
  border-color: rgba(16,185,129,0.3) !important;
  color: #6ee7b7 !important;
}

body.dark-mode .highlight--warning {
  background: rgba(245,158,11,0.1) !important;
  border-color: rgba(245,158,11,0.3) !important;
  color: #fcd34d !important;
}

body.dark-mode .privacy-contact-btn {
  border-color: var(--tm-border) !important;
  color: #a78bfa !important;
}

body.dark-mode .privacy-contact-btn:hover {
  background: rgba(102,126,234,0.12) !important;
  border-color: #667eea !important;
}

body.dark-mode .privacy-footer-btn--ghost {
  border-color: var(--tm-border) !important;
  color: var(--tm-muted) !important;
}

body.dark-mode .privacy-footer-btn--ghost:hover {
  background: var(--tm-hover) !important;
}

body.dark-mode .badge--blue   { background: rgba(76,95,213,0.18) !important; }
body.dark-mode .badge--purple { background: rgba(124,58,237,0.18) !important; }
body.dark-mode .badge--green  { background: rgba(5,150,105,0.18) !important; }
body.dark-mode .badge--orange { background: rgba(194,65,12,0.18) !important; color: #fb923c !important; }
body.dark-mode .badge--red    { background: rgba(185,28,28,0.18) !important; color: #fca5a5 !important; }
body.dark-mode .badge--teal   { background: rgba(15,118,110,0.18) !important; color: #5eead4 !important; }
body.dark-mode .badge--indigo { background: rgba(67,56,202,0.18) !important; color: #a5b4fc !important; }
body.dark-mode .badge--pink   { background: rgba(157,23,77,0.18) !important; color: #f9a8d4 !important; }
body.dark-mode .badge--gray   { background: rgba(71,85,105,0.18) !important; color: #94a3b8 !important; }

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .privacy-layout {
    grid-template-columns: 1fr;
  }

  .privacy-sidebar {
    position: static;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }

  .toc-card {
    grid-column: 1 / -1;
  }

  .toc-nav {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2px;
  }
}

@media (max-width: 640px) {
  .privacy-hero {
    padding: 2.5rem 1rem 2.25rem;
  }

  .privacy-hero-title {
    font-size: 1.6rem;
  }

  .privacy-hero-meta {
    flex-direction: column;
    align-items: center;
  }

  .privacy-sidebar {
    grid-template-columns: 1fr;
  }

  .toc-nav {
    grid-template-columns: 1fr;
  }

  .privacy-footer-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .privacy-footer-btn {
    justify-content: center;
  }

  .section-table {
    font-size: 12px;
  }

  .section-table th,
  .section-table td {
    padding: 0.55rem 0.75rem;
  }
}
</style>
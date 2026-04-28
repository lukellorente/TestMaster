<template>
  <div class="terms-page">

    <!-- ═══════════════════════════════════════════════════
         HERO
    ═══════════════════════════════════════════════════ -->
    <section class="terms-hero">
      <div class="terms-hero-inner">
        <div class="terms-hero-icon">
          <i class="fa-solid fa-file-contract"></i>
        </div>
        <h1 class="terms-hero-title">Términos y Condiciones</h1>
        <p class="terms-hero-sub">
          Al usar TestMaster aceptas estos términos. Léelos con calma; están escritos
          en lenguaje claro para que sepas exactamente cuáles son tus derechos y obligaciones.
        </p>
        <div class="terms-hero-meta">
          <span class="terms-meta-badge">
            <i class="fa-solid fa-calendar-check"></i>
            Vigente desde el 14 de abril de 2026
          </span>
          <span class="terms-meta-badge">
            <i class="fa-solid fa-scale-balanced"></i>
            Ley aplicable: España / UE
          </span>
          <span class="terms-meta-badge terms-meta-badge--highlight">
            <i class="fa-solid fa-clock-rotate-left"></i>
            Versión 1.0
          </span>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════
         LAYOUT PRINCIPAL
    ═══════════════════════════════════════════════════ -->
    <div class="terms-layout">

      <!-- SIDEBAR / ÍNDICE -->
      <aside class="terms-sidebar">
        <div class="terms-toc-card">
          <p class="terms-toc-eyebrow">
            <i class="fa-solid fa-list-ul"></i>
            Índice
          </p>
          <nav class="terms-toc-nav">
            <a
              v-for="section in sections"
              :key="section.id"
              :href="`#${section.id}`"
              :class="['terms-toc-item', { active: activeSection === section.id }]"
              @click.prevent="scrollTo(section.id)"
            >
              <span class="terms-toc-num">{{ section.num }}</span>
              <span>{{ section.title }}</span>
            </a>
          </nav>
        </div>

        <!-- Resumen clave -->
        <div class="terms-key-card">
          <p class="terms-toc-eyebrow">
            <i class="fa-solid fa-bolt"></i>
            Lo más importante
          </p>
          <div class="terms-key-list">
            <div v-for="key in keyPoints" :key="key.id" :class="['terms-key-item', `key--${key.type}`]">
              <i :class="key.icon"></i>
              <span>{{ key.text }}</span>
            </div>
          </div>
        </div>

        <div class="terms-contact-card">
          <i class="fa-solid fa-envelope terms-contact-icon"></i>
          <p class="terms-contact-title">¿Dudas legales?</p>
          <a href="mailto:legal@testmaster.app" class="terms-contact-btn">
            legal@testmaster.app
          </a>
        </div>
      </aside>

      <!-- CONTENIDO -->
      <article class="terms-content">

        <!-- Aviso de aceptación -->
        <div class="acceptance-banner">
          <i class="fa-solid fa-circle-exclamation acceptance-icon"></i>
          <div>
            <p class="acceptance-title">Aceptación de los términos</p>
            <p class="acceptance-text">
              Al acceder o usar TestMaster, confirmas que tienes al menos 16 años y que has leído,
              entendido y aceptado estos Términos y Condiciones en su totalidad. Si no estás de acuerdo,
              por favor no uses el servicio.
            </p>
          </div>
        </div>

        <!-- Secciones -->
        <section
          v-for="section in sections"
          :key="section.id"
          :id="section.id"
          class="terms-section"
        >
          <div class="terms-section-heading">
            <div :class="['terms-num-badge', `badge--${section.color}`]">
              {{ section.num }}
            </div>
            <h2 class="terms-section-title">{{ section.title }}</h2>
          </div>

          <template v-if="section.content">
            <p
              v-for="(para, i) in section.content"
              :key="i"
              class="terms-para"
              v-html="para"
            ></p>
          </template>

          <ul v-if="section.list" class="terms-list">
            <li v-for="(item, i) in section.list" :key="i" class="terms-list-item">
              <i :class="['terms-list-icon', item.icon || 'fa-solid fa-circle-dot']"></i>
              <span v-html="item.text || item"></span>
            </li>
          </ul>

          <div v-if="section.subsections" class="terms-subsections">
            <div v-for="sub in section.subsections" :key="sub.title" class="terms-subsection">
              <h3 class="terms-subsection-title">
                <i :class="sub.icon"></i>
                {{ sub.title }}
              </h3>
              <p v-for="(p, i) in sub.content" :key="i" class="terms-para" v-html="p"></p>
              <ul v-if="sub.list" class="terms-list">
                <li v-for="(item, i) in sub.list" :key="i" class="terms-list-item">
                  <i class="fa-solid fa-circle-dot terms-list-icon"></i>
                  <span v-html="item"></span>
                </li>
              </ul>
            </div>
          </div>

          <div v-if="section.highlight" :class="['terms-highlight', `highlight--${section.highlight.type}`]">
            <i :class="['terms-highlight-icon', section.highlight.icon]"></i>
            <p v-html="section.highlight.text"></p>
          </div>
        </section>

        <!-- Pie del documento -->
        <div class="terms-footer-block">
          <div class="terms-footer-icon">
            <i class="fa-solid fa-handshake"></i>
          </div>
          <p class="terms-footer-title">Gracias por confiar en TestMaster</p>
          <p class="terms-footer-sub">
            Estos términos rigen la relación entre tú y TestMaster. Si tienes cualquier pregunta,
            escríbenos antes de usar el servicio.
          </p>
          <div class="terms-footer-actions">
            <a href="mailto:legal@testmaster.app" class="terms-footer-btn terms-footer-btn--primary">
              <i class="fa-solid fa-envelope"></i>
              Contactar equipo legal
            </a>
            <router-link to="/privacidad" class="terms-footer-btn terms-footer-btn--ghost">
              <i class="fa-solid fa-shield-halved"></i>
              Política de privacidad
            </router-link>
            <router-link to="/ayuda" class="terms-footer-btn terms-footer-btn--ghost">
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

// ─── Puntos clave del sidebar ─────────────────────────────────────────────────
const keyPoints = [
  { id: 1, type: 'ok',   icon: 'fa-solid fa-circle-check', text: 'Uso gratuito para siempre' },
  { id: 2, type: 'ok',   icon: 'fa-solid fa-circle-check', text: 'Tus tests son tuyos' },
  { id: 3, type: 'ok',   icon: 'fa-solid fa-circle-check', text: 'Sin publicidad intrusiva' },
  { id: 4, type: 'warn', icon: 'fa-solid fa-triangle-exclamation', text: 'Prohibido contenido dañino' },
  { id: 5, type: 'warn', icon: 'fa-solid fa-triangle-exclamation', text: 'Sin cuentas múltiples' },
  { id: 6, type: 'info', icon: 'fa-solid fa-circle-info', text: 'Mayores de 16 años' },
];

// ─── Secciones ────────────────────────────────────────────────────────────────
const sections = [
  {
    id: 'objeto',
    num: '01',
    color: 'blue',
    title: 'Objeto y ámbito de aplicación',
    content: [
      'Los presentes Términos y Condiciones (en adelante, "Términos") regulan el acceso y uso de la plataforma TestMaster, accesible en <strong>testmaster.app</strong> y sus subdominios, así como cualquier aplicación móvil o herramienta asociada (en conjunto, el "Servicio").',
      'El Servicio es operado por <strong>TestMaster S.L.</strong> (en adelante, "TestMaster", "nosotros", "la empresa"). Al registrarte o usar el Servicio aceptas estos Términos junto con nuestra <strong>Política de Privacidad</strong>, que forma parte integrante de este acuerdo.',
    ],
    highlight: {
      type: 'info',
      icon: 'fa-solid fa-circle-info',
      text: 'Si usas TestMaster en nombre de una organización, declaras que tienes autorización para vincular a dicha organización a estos Términos.',
    },
  },
  {
    id: 'acceso',
    num: '02',
    color: 'purple',
    title: 'Acceso al servicio y registro',
    content: [
      'Parte del Servicio es accesible sin registro (consultar y realizar tests públicos). Sin embargo, para crear tests, guardar resultados y acceder a funcionalidades avanzadas es necesario crear una cuenta.',
    ],
    subsections: [
      {
        title: 'Requisitos de la cuenta',
        icon: 'fa-solid fa-user-check',
        content: [
          'Para registrarte debes cumplir los siguientes requisitos:',
        ],
        list: [
          'Tener al menos <strong>16 años</strong> de edad (o la edad mínima legal en tu país, si es superior).',
          'Proporcionar una dirección de correo electrónico <strong>válida y real</strong> que sea tuya.',
          'Crear una contraseña segura y mantenerla confidencial.',
          'Ser una persona física (no se permiten cuentas de bots automatizados sin autorización expresa).',
        ],
      },
      {
        title: 'Seguridad de la cuenta',
        icon: 'fa-solid fa-lock',
        content: [
          'Eres el único responsable de mantener la confidencialidad de tus credenciales y de todas las actividades que se realicen desde tu cuenta. Notifícanos de inmediato a <strong>seguridad@testmaster.app</strong> si sospechas un acceso no autorizado.',
        ],
      },
    ],
  },
  {
    id: 'uso-aceptable',
    num: '03',
    color: 'green',
    title: 'Uso aceptable',
    content: [
      'TestMaster es una plataforma educativa y de evaluación de conocimientos. Puedes usarla para crear, compartir y realizar tests sobre cualquier materia lícita.',
      'Al usar el Servicio te comprometes a no realizar ninguna de las siguientes acciones:',
    ],
    list: [
      { text: 'Publicar contenido <strong>ilegal, difamatorio, obsceno, violento o que incite al odio</strong> por razón de raza, género, religión, orientación sexual, etc.' },
      { text: 'Distribuir <strong>malware, virus, código malicioso</strong> o cualquier elemento que pueda dañar los sistemas de otros usuarios.' },
      { text: 'Crear tests con <strong>derechos de autor ajenos</strong> sin la debida licencia o atribución.' },
      { text: '<strong>Automatizar el acceso</strong> al Servicio mediante bots, scrapers o herramientas similares sin autorización expresa.' },
      { text: '<strong>Manipular estadísticas</strong>, clasificaciones o resultados mediante trampas, múltiples cuentas o cualquier medio fraudulento.' },
      { text: 'Intentar acceder a áreas restringidas, realizar ataques de <strong>fuerza bruta o inyección</strong> en el sistema.' },
      { text: 'Suplantar la identidad de <strong>otra persona o entidad</strong>.' },
      { text: 'Usar el Servicio para enviar <strong>spam o comunicaciones no solicitadas</strong> a otros usuarios.' },
    ],
    highlight: {
      type: 'warning',
      icon: 'fa-solid fa-triangle-exclamation',
      text: 'El incumplimiento de estas normas puede resultar en la <strong>suspensión o eliminación inmediata</strong> de tu cuenta, sin perjuicio de las acciones legales que pudieran corresponder.',
    },
  },
  {
    id: 'contenido',
    num: '04',
    color: 'orange',
    title: 'Contenido del usuario',
    content: [
      'Al subir, publicar o compartir contenido en TestMaster (preguntas, respuestas, explicaciones, imágenes de perfil, etc.), declaras y garantizas lo siguiente:',
    ],
    list: [
      { text: 'Eres el titular original del contenido o cuentas con los <strong>permisos necesarios</strong> para publicarlo.' },
      { text: 'El contenido <strong>no infringe</strong> derechos de propiedad intelectual, privacidad, honor ni ningún otro derecho de terceros.' },
      { text: 'El contenido <strong>no es falso, engañoso ni fraudulento</strong>.' },
    ],
    subsections: [
      {
        title: 'Licencia que nos otorgas',
        icon: 'fa-solid fa-key',
        content: [
          'Al publicar contenido en la plataforma, nos concedes una <strong>licencia mundial, no exclusiva, libre de regalías y sublicenciable</strong> para usar, reproducir, modificar, distribuir y mostrar dicho contenido únicamente con el fin de operar, mejorar y promocionar el Servicio.',
          'Esta licencia finaliza cuando eliminas el contenido o cierras tu cuenta, salvo que el contenido haya sido compartido con otros usuarios, en cuyo caso podría permanecer en sus historiales.',
        ],
      },
      {
        title: 'Tus derechos sobre el contenido',
        icon: 'fa-solid fa-copyright',
        content: [
          'La propiedad intelectual de los tests y preguntas que crees <strong>sigue siendo tuya</strong>. TestMaster no reivindica propiedad sobre tu contenido. Puedes eliminar tus tests en cualquier momento desde la plataforma.',
        ],
      },
    ],
  },
  {
    id: 'propiedad-intelectual',
    num: '05',
    color: 'teal',
    title: 'Propiedad intelectual de TestMaster',
    content: [
      'Todos los elementos que constituyen TestMaster — incluyendo el software, diseño, logotipos, marca, interfaz de usuario, código fuente, base de datos de categorías y cualquier otro material desarrollado por la empresa — son propiedad exclusiva de <strong>TestMaster S.L.</strong> o de sus licenciantes y están protegidos por las leyes de propiedad intelectual e industrial aplicables.',
      'Queda expresamente prohibido copiar, modificar, distribuir, vender, licenciar, realizar ingeniería inversa o crear obras derivadas de cualquier parte del Servicio sin autorización escrita previa.',
    ],
    highlight: {
      type: 'info',
      icon: 'fa-solid fa-circle-info',
      text: 'El uso del nombre "TestMaster", sus logotipos y marcas registradas sin autorización expresa constituye una infracción de derechos de marca.',
    },
  },
  {
    id: 'disponibilidad',
    num: '06',
    color: 'indigo',
    title: 'Disponibilidad y modificaciones del servicio',
    content: [
      'TestMaster se esfuerza por mantener el Servicio disponible de forma continua, pero <strong>no garantiza una disponibilidad del 100%</strong>. El Servicio puede interrumpirse temporalmente por mantenimiento programado, fallos técnicos, causas de fuerza mayor u otras circunstancias fuera de nuestro control.',
    ],
    list: [
      { text: 'Podemos <strong>modificar, añadir o eliminar funcionalidades</strong> del Servicio en cualquier momento.' },
      { text: 'Anunciaremos cambios <strong>significativos</strong> con al menos 15 días de antelación a través de la plataforma o por correo electrónico.' },
      { text: 'Las interrupciones de mantenimiento programadas se comunicarán con <strong>24 horas de antelación</strong> siempre que sea posible.' },
      { text: 'TestMaster no será responsable de los daños que puedan derivarse de la <strong>interrupción o indisponibilidad</strong> temporal del Servicio.' },
    ],
  },
  {
    id: 'suspension',
    num: '07',
    color: 'red',
    title: 'Suspensión y cancelación de cuentas',
    content: [
      'TestMaster se reserva el derecho de suspender o cancelar tu cuenta, con o sin previo aviso, si determina a su criterio razonable que has incumplido estos Términos, la legislación vigente o has causado daño a la plataforma o a otros usuarios.',
    ],
    subsections: [
      {
        title: 'Causas de suspensión o baja',
        icon: 'fa-solid fa-ban',
        list: [
          'Violación de las normas de uso aceptable (sección 03).',
          'Suplantación de identidad o uso fraudulento.',
          'Intento de comprometer la seguridad del Servicio.',
          'Decisión judicial o requerimiento de autoridad competente.',
          'Inactividad superior a 24 meses consecutivos (previa notificación).',
        ],
      },
      {
        title: 'Baja voluntaria',
        icon: 'fa-solid fa-door-open',
        content: [
          'Puedes cancelar tu cuenta en cualquier momento desde la configuración de tu perfil o contactando con <strong>soporte@testmaster.app</strong>. La cancelación conlleva la eliminación definitiva de tus datos en los plazos descritos en la Política de Privacidad.',
        ],
      },
    ],
    highlight: {
      type: 'warning',
      icon: 'fa-solid fa-triangle-exclamation',
      text: 'En caso de cancelación por incumplimiento, el contenido publicado por la cuenta puede ser eliminado de la plataforma sin posibilidad de recuperación.',
    },
  },
  {
    id: 'responsabilidad',
    num: '08',
    color: 'gray',
    title: 'Limitación de responsabilidad',
    content: [
      'El Servicio se proporciona <strong>"tal cual"</strong> y <strong>"según disponibilidad"</strong>, sin garantías de ningún tipo, expresas o implícitas, incluyendo pero no limitándose a garantías de comerciabilidad, adecuación para un fin particular o no infracción.',
      'En la máxima medida permitida por la ley aplicable, TestMaster no será responsable de:',
    ],
    list: [
      { text: 'Daños indirectos, incidentales, especiales, punitivos o consecuentes derivados del uso o imposibilidad de uso del Servicio.' },
      { text: 'Pérdida de datos, ingresos, beneficios u oportunidades de negocio.' },
      { text: 'Conductas de terceros, incluyendo el contenido publicado por otros usuarios.' },
      { text: 'Daños derivados de accesos no autorizados a tu cuenta si no tomaste las precauciones de seguridad razonables.' },
    ],
    highlight: {
      type: 'info',
      icon: 'fa-solid fa-gavel',
      text: 'Algunas jurisdicciones no permiten la exclusión de ciertas garantías o limitaciones de responsabilidad, por lo que las limitaciones anteriores pueden no aplicarse en su totalidad en tu caso.',
    },
  },
  {
    id: 'privacidad',
    num: '09',
    color: 'pink',
    title: 'Privacidad y protección de datos',
    content: [
      'El tratamiento de tus datos personales se rige por nuestra <strong>Política de Privacidad</strong>, disponible en <strong>/privacidad</strong>, que forma parte integrante de estos Términos y que te recomendamos leer detenidamente.',
      'Al aceptar estos Términos, reconoces haber leído y comprendido nuestra Política de Privacidad y consientes el tratamiento de tus datos tal y como se describe en ella.',
    ],
  },
  {
    id: 'modificaciones',
    num: '10',
    color: 'amber',
    title: 'Modificación de los términos',
    content: [
      'TestMaster puede actualizar estos Términos periódicamente para reflejar cambios en el Servicio, en la legislación o en nuestras prácticas. La versión vigente siempre estará disponible en esta página.',
      'Cuando los cambios sean <strong>sustanciales</strong>, te notificaremos por correo electrónico con al menos <strong>30 días de antelación</strong>. El uso continuado del Servicio tras la entrada en vigor de los nuevos Términos constituirá tu aceptación de los mismos.',
      'Si no estás de acuerdo con los cambios, puedes cancelar tu cuenta antes de la fecha de entrada en vigor sin ningún coste adicional.',
    ],
  },
  {
    id: 'ley-jurisdiccion',
    num: '11',
    color: 'blue',
    title: 'Ley aplicable y jurisdicción',
    content: [
      'Estos Términos se rigen e interpretan de acuerdo con la legislación española y, en lo que resulte aplicable, la normativa de la Unión Europea.',
      'Para cualquier controversia derivada de estos Términos, las partes se someten a la jurisdicción de los <strong>Juzgados y Tribunales de Madrid</strong>, con renuncia expresa a cualquier otro fuero que pudiera corresponderles.',
      'Si eres un consumidor residente en la UE, también puedes acudir a la <strong>plataforma de resolución de litigios en línea (ODR)</strong> de la Comisión Europea en <strong>ec.europa.eu/consumers/odr</strong>.',
    ],
    highlight: {
      type: 'success',
      icon: 'fa-solid fa-circle-check',
      text: 'Como consumidor de la UE, disfrutas de las protecciones adicionales que ofrecen las directivas europeas de protección al consumidor, independientemente de lo indicado en estos Términos.',
    },
  },
];

// ─── Scroll spy ───────────────────────────────────────────────────────────────
const scrollTo = (id) => {
  const el = document.getElementById(id);
  if (!el) return;
  const top = el.getBoundingClientRect().top + window.scrollY - 100;
  window.scrollTo({ top, behavior: 'smooth' });
  activeSection.value = id;
};

const handleScroll = () => {
  for (let i = sections.length - 1; i >= 0; i--) {
    const el = document.getElementById(sections[i].id);
    if (el && el.getBoundingClientRect().top <= 140) {
      activeSection.value = sections[i].id;
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
.terms-page {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* ── Hero ────────────────────────────────────────────────────────────────── */
.terms-hero {
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

.terms-hero-inner {
  max-width: 680px;
  margin: 0 auto;
  text-align: center;
}

.terms-hero-icon {
  font-size: 2.5rem;
  color: rgba(255,255,255,0.8);
  margin-bottom: 0.75rem;
}

.terms-hero-title {
  font-size: 2rem;
  font-weight: 700;
  color: white;
  margin-bottom: 0.65rem;
}

.terms-hero-sub {
  font-size: 0.97rem;
  color: rgba(255,255,255,0.7);
  line-height: 1.7;
  margin-bottom: 1.5rem;
}

.terms-hero-meta {
  display: flex;
  gap: 0.6rem;
  justify-content: center;
  flex-wrap: wrap;
}

.terms-meta-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 5px 13px;
  border-radius: 20px;
  background: rgba(255,255,255,0.08);
  color: rgba(255,255,255,0.7);
  font-size: 12px;
  border: 1px solid rgba(255,255,255,0.12);
}

.terms-meta-badge i { color: #a78bfa; }

.terms-meta-badge--highlight {
  background: rgba(167,139,250,0.18);
  border-color: rgba(167,139,250,0.35);
  color: #c4b5fd;
}

/* ── Layout ──────────────────────────────────────────────────────────────── */
.terms-layout {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 1.75rem;
  align-items: start;
}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.terms-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: sticky;
  top: 80px;
}

.terms-toc-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.25rem;
}

.terms-toc-eyebrow {
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

.terms-toc-nav {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.terms-toc-item {
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

.terms-toc-item:hover {
  background: #f8f9fa;
  color: #2d3748;
}

.terms-toc-item.active {
  background: #f3e8ff;
  color: #7c3aed;
  font-weight: 500;
}

.terms-toc-num {
  font-size: 10px;
  font-weight: 700;
  color: #cbd5e0;
  font-variant-numeric: tabular-nums;
  min-width: 20px;
  flex-shrink: 0;
}

.terms-toc-item.active .terms-toc-num { color: #8b5cf6; }

/* Key card */
.terms-key-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.25rem;
}

.terms-key-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.terms-key-item {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  font-size: 12.5px;
  line-height: 1.45;
  padding: 0.45rem 0.6rem;
  border-radius: 7px;
}

.terms-key-item i {
  font-size: 11px;
  margin-top: 2px;
  flex-shrink: 0;
}

.key--ok   { color: #065f46; background: #f0fdf4; }
.key--warn { color: #92400e; background: #fffbeb; }
.key--info { color: #1e40af; background: #eff6ff; }

.key--ok i   { color: #10b981; }
.key--warn i { color: #f59e0b; }
.key--info i { color: #3b82f6; }

/* Contact card */
.terms-contact-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.25rem;
  text-align: center;
}

.terms-contact-icon {
  font-size: 1.4rem;
  color: #8b5cf6;
  margin-bottom: 0.5rem;
  display: block;
}

.terms-contact-title {
  font-size: 13px;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.6rem;
}

.terms-contact-btn {
  display: inline-block;
  font-size: 11.5px;
  font-weight: 600;
  color: #7c3aed;
  text-decoration: none;
  border: 1px solid #ddd6fe;
  border-radius: 7px;
  padding: 6px 12px;
  transition: all 0.2s;
  word-break: break-all;
}

.terms-contact-btn:hover {
  background: #f3e8ff;
  border-color: #8b5cf6;
}

/* ── Contenido ───────────────────────────────────────────────────────────── */
.terms-content {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* Banner aceptación */
.acceptance-banner {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  background: #faf5ff;
  border: 1px solid #ddd6fe;
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.5rem;
}

.acceptance-icon {
  font-size: 1.4rem;
  color: #7c3aed;
  flex-shrink: 0;
  margin-top: 2px;
}

.acceptance-title {
  font-size: 14px;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.35rem;
}

.acceptance-text {
  font-size: 13.5px;
  color: #6b7280;
  line-height: 1.65;
}

/* Secciones */
.terms-section {
  padding: 2rem 0;
  border-bottom: 1px solid #f7fafc;
  scroll-margin-top: 100px;
}

.terms-section:last-of-type {
  border-bottom: none;
}

.terms-section-heading {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  margin-bottom: 1.1rem;
}

.terms-num-badge {
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
.badge--teal   { background: #ccfbf1; color: #0f766e; }
.badge--indigo { background: #e0e7ff; color: #4338ca; }
.badge--red    { background: #fee2e2; color: #b91c1c; }
.badge--gray   { background: #f1f5f9; color: #475569; }
.badge--pink   { background: #fce7f3; color: #9d174d; }
.badge--amber  { background: #fef3c7; color: #92400e; }

.terms-section-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a202c;
}

.terms-para {
  font-size: 14px;
  color: #718096;
  line-height: 1.75;
  margin-bottom: 0.75rem;
}

.terms-para:last-child { margin-bottom: 0; }

/* Lista */
.terms-list {
  list-style: none;
  padding: 0;
  margin: 0.75rem 0 0;
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}

.terms-list-item {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  font-size: 14px;
  color: #718096;
  line-height: 1.65;
}

.terms-list-icon {
  font-size: 9px;
  color: #8b5cf6;
  margin-top: 5px;
  flex-shrink: 0;
}

/* Subsecciones */
.terms-subsections {
  margin-top: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.terms-subsection {
  background: #fcfcfd;
  border: 1px solid #f0f0f7;
  border-radius: 10px;
  padding: 1.1rem 1.25rem;
}

.terms-subsection-title {
  font-size: 13px;
  font-weight: 600;
  color: #4a5568;
  margin-bottom: 0.65rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.terms-subsection-title i {
  color: #8b5cf6;
  font-size: 12px;
}

/* Highlight */
.terms-highlight {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  border-radius: 10px;
  margin-top: 1rem;
  font-size: 13.5px;
  line-height: 1.6;
}

.terms-highlight p { margin: 0; }

.terms-highlight-icon {
  font-size: 14px;
  margin-top: 2px;
  flex-shrink: 0;
}

.highlight--info {
  background: #ebf8ff;
  border: 1px solid #90cdf4;
  color: #2b6cb0;
}
.highlight--info .terms-highlight-icon { color: #3182ce; }

.highlight--success {
  background: #f0fff4;
  border: 1px solid #9ae6b4;
  color: #276749;
}
.highlight--success .terms-highlight-icon { color: #38a169; }

.highlight--warning {
  background: #fffbeb;
  border: 1px solid #fbd38d;
  color: #744210;
}
.highlight--warning .terms-highlight-icon { color: #d97706; }

/* Pie del documento */
.terms-footer-block {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 2.5rem;
  text-align: center;
  margin-top: 1.5rem;
}

.terms-footer-icon {
  font-size: 2.5rem;
  color: #8b5cf6;
  margin-bottom: 1rem;
}

.terms-footer-title {
  font-size: 1.05rem;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 0.5rem;
}

.terms-footer-sub {
  font-size: 14px;
  color: #a0aec0;
  line-height: 1.65;
  max-width: 420px;
  margin: 0 auto 1.5rem;
}

.terms-footer-actions {
  display: flex;
  gap: 0.65rem;
  justify-content: center;
  flex-wrap: wrap;
}

.terms-footer-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 10px 18px;
  border-radius: 9px;
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s;
  cursor: pointer;
  border: none;
}

.terms-footer-btn--primary {
  background: linear-gradient(135deg, #7c3aed 0%, #4c5fd5 100%);
  color: white !important;
}

.terms-footer-btn--primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(124,58,237,0.4);
}

.terms-footer-btn--ghost {
  background: transparent;
  border: 1px solid #e2e8f0;
  color: #4a5568;
}

.terms-footer-btn--ghost:hover {
  background: #f8f9fa;
  border-color: #cbd5e0;
}

/* ── Dark mode ───────────────────────────────────────────────────────────── */
body.dark-mode .terms-toc-card,
body.dark-mode .terms-key-card,
body.dark-mode .terms-contact-card,
body.dark-mode .terms-footer-block {
  background: var(--tm-surface) !important;
  border-color: var(--tm-border) !important;
}

body.dark-mode .terms-section-title,
body.dark-mode .acceptance-title,
body.dark-mode .terms-footer-title,
body.dark-mode .terms-contact-title,
body.dark-mode .terms-subsection-title {
  color: var(--tm-text) !important;
}

body.dark-mode .terms-para,
body.dark-mode .terms-list-item,
body.dark-mode .acceptance-text,
body.dark-mode .terms-footer-sub,
body.dark-mode .terms-toc-item,
body.dark-mode .terms-toc-eyebrow {
  color: var(--tm-muted) !important;
}

body.dark-mode .terms-toc-item:hover {
  background: var(--tm-hover) !important;
  color: var(--tm-text) !important;
}

body.dark-mode .terms-toc-item.active {
  background: rgba(139,92,246,0.15) !important;
  color: #c4b5fd !important;
}

body.dark-mode .terms-toc-item.active .terms-toc-num {
  color: #a78bfa !important;
}

body.dark-mode .acceptance-banner {
  background: rgba(124,58,237,0.1) !important;
  border-color: rgba(124,58,237,0.25) !important;
}

body.dark-mode .terms-section {
  border-bottom-color: var(--tm-border) !important;
}

body.dark-mode .terms-subsection {
  background: var(--tm-bg) !important;
  border-color: var(--tm-border) !important;
}

body.dark-mode .highlight--info {
  background: rgba(59,130,246,0.1) !important;
  border-color: rgba(59,130,246,0.25) !important;
  color: #93c5fd !important;
}

body.dark-mode .highlight--success {
  background: rgba(16,185,129,0.1) !important;
  border-color: rgba(16,185,129,0.25) !important;
  color: #6ee7b7 !important;
}

body.dark-mode .highlight--warning {
  background: rgba(245,158,11,0.1) !important;
  border-color: rgba(245,158,11,0.25) !important;
  color: #fcd34d !important;
}

body.dark-mode .terms-contact-btn {
  border-color: var(--tm-border) !important;
  color: #c4b5fd !important;
}

body.dark-mode .terms-contact-btn:hover {
  background: rgba(139,92,246,0.12) !important;
  border-color: #8b5cf6 !important;
}

body.dark-mode .terms-footer-btn--ghost {
  border-color: var(--tm-border) !important;
  color: var(--tm-muted) !important;
}

body.dark-mode .terms-footer-btn--ghost:hover {
  background: var(--tm-hover) !important;
}

body.dark-mode .key--ok   { background: rgba(5,150,105,0.1) !important; color: #6ee7b7 !important; }
body.dark-mode .key--warn { background: rgba(245,158,11,0.1) !important; color: #fcd34d !important; }
body.dark-mode .key--info { background: rgba(59,130,246,0.1) !important; color: #93c5fd !important; }

body.dark-mode .badge--blue   { background: rgba(76,95,213,0.18) !important; }
body.dark-mode .badge--purple { background: rgba(124,58,237,0.18) !important; }
body.dark-mode .badge--green  { background: rgba(5,150,105,0.18) !important; }
body.dark-mode .badge--orange { background: rgba(194,65,12,0.18) !important; color: #fb923c !important; }
body.dark-mode .badge--teal   { background: rgba(15,118,110,0.18) !important; color: #5eead4 !important; }
body.dark-mode .badge--indigo { background: rgba(67,56,202,0.18) !important; color: #a5b4fc !important; }
body.dark-mode .badge--red    { background: rgba(185,28,28,0.18) !important; color: #fca5a5 !important; }
body.dark-mode .badge--gray   { background: rgba(71,85,105,0.18) !important; color: #94a3b8 !important; }
body.dark-mode .badge--pink   { background: rgba(157,23,77,0.18) !important; color: #f9a8d4 !important; }
body.dark-mode .badge--amber  { background: rgba(146,64,14,0.18) !important; color: #fcd34d !important; }

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .terms-layout {
    grid-template-columns: 1fr;
  }

  .terms-sidebar {
    position: static;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }

  .terms-toc-card {
    grid-column: 1 / -1;
  }

  .terms-toc-nav {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2px;
  }
}

@media (max-width: 640px) {
  .terms-hero {
    padding: 2.5rem 1rem 2.25rem;
  }

  .terms-hero-title {
    font-size: 1.6rem;
  }

  .terms-hero-meta {
    flex-direction: column;
    align-items: center;
  }

  .terms-sidebar {
    grid-template-columns: 1fr;
  }

  .terms-toc-nav {
    grid-template-columns: 1fr;
  }

  .terms-footer-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .terms-footer-btn {
    justify-content: center;
  }
}
</style>
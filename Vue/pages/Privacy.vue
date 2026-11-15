<template>
  <LegalDocument
    title="Política de privacidad"
    intro="Qué datos recopilamos, para qué los usamos y qué derechos tienes sobre ellos."
    :meta="['Última actualización: 15 de noviembre de 2026', 'Versión 2026-11-15']"
    :summary="summary"
    :sections="sections"
    :footer="footer"
  />
</template>

<script setup>
import LegalDocument from '../components/legal/LegalDocument.vue';

/*
 * ⚠️ RELLENA ANTES DE PUBLICAR: busca los marcadores entre corchetes
 *    [TU NOMBRE], [TU EMAIL], [PROVEEDOR DE HOSTING], [PAÍS DEL SERVIDOR].
 * Si cambias el contenido de forma relevante, actualiza también la fecha de arriba y
 * AuthController::PRIVACY_POLICY_VERSION para que quede constancia de qué versión aceptó cada usuario.
 */
const CONTACT_EMAIL = '[TU EMAIL]';

const summary = {
  title: 'En pocas palabras',
  text: 'TestMaster es un proyecto personal sin ánimo de lucro. Solo guardamos lo necesario para que funcione tu cuenta: nombre, email, contraseña cifrada y tu actividad en los tests. No hay publicidad, ni analítica, ni cookies de rastreo, y nunca vendemos tus datos. Puedes descargarlos o borrar tu cuenta tú mismo desde tu perfil.',
};

const footer = {
  title: '¿Tienes dudas sobre tus datos?',
  text: 'Escríbeme para ejercer tus derechos o consultar cualquier cuestión. Si cambio esta política de forma importante, lo verás al iniciar sesión.',
  actions: [
    { label: `Escribir a ${CONTACT_EMAIL}`, href: `mailto:${CONTACT_EMAIL}`, primary: true },
    { label: 'Centro de ayuda', to: '/help' },
  ],
};

const sections = [
  {
    id: 'responsable',
    num: '01',
    color: 'blue',
    title: 'Responsable del tratamiento',
    content: [
      'TestMaster es un proyecto personal/educativo. El responsable del tratamiento de los datos es <strong>[TU NOMBRE]</strong>, persona física.',
      `Para cualquier cuestión sobre privacidad puedes escribir a <strong>${CONTACT_EMAIL}</strong>.`,
    ],
    highlight: {
      type: 'info',
      icon: 'fa-solid fa-user',
      text: 'Al tratarse de un proyecto de pequeña escala sin tratamiento de datos sensibles a gran escala, no es obligatorio designar un Delegado de Protección de Datos (art. 37 RGPD).',
    },
  },
  {
    id: 'datos-recogidos',
    num: '02',
    color: 'purple',
    title: 'Datos que recopilamos',
    content: [
      'Solo tratamos los datos necesarios para prestar el servicio:',
    ],
    table: {
      headers: ['Categoría', 'Datos concretos', 'Cuándo se recogen'],
      rows: [
        ['Identidad', 'Nombre y correo electrónico', 'Al registrarte'],
        ['Credenciales', 'Contraseña cifrada (hash bcrypt, nunca en claro)', 'Al registrarte'],
        ['Consentimiento', 'Fecha y versión de la política aceptada', 'Al registrarte'],
        ['Perfil (opcional)', 'Foto de avatar y biografía', 'Si decides añadirlos'],
        ['Actividad', 'Tests creados, intentos, respuestas, puntuaciones, fecha del último acceso', 'Al usar la plataforma'],
        ['Técnicos', 'Dirección IP y navegador (user-agent) asociados a la sesión', 'Automáticamente al iniciar sesión'],
      ],
    },
    highlight: {
      type: 'success',
      icon: 'fa-solid fa-circle-check',
      text: 'No recopilamos datos sensibles (salud, ideología, origen étnico, etc.).',
    },
  },
  {
    id: 'publico',
    num: '03',
    color: 'teal',
    title: 'Qué es visible para otros usuarios',
    content: [
      'Tu perfil público muestra únicamente tu <strong>nombre, foto, biografía, fecha de alta, estadísticas agregadas</strong> (tests creados, completados, nota media) y los <strong>tests públicos que hayas publicado</strong>.',
      'Tu email, tu última conexión y tu historial de intentos y respuestas <strong>solo los ves tú</strong>.',
    ],
  },
  {
    id: 'finalidad',
    num: '04',
    color: 'green',
    title: 'Finalidad y base jurídica',
    table: {
      headers: ['Finalidad', 'Base jurídica (RGPD)'],
      rows: [
        ['Crear y gestionar tu cuenta, iniciar sesión', 'Art. 6.1.b — Ejecución del contrato (Términos de uso)'],
        ['Guardar tus tests, intentos y estadísticas', 'Art. 6.1.b — Ejecución del contrato'],
        ['Mostrar tu perfil público y tus tests publicados', 'Art. 6.1.b — Ejecución del contrato'],
        ['Seguridad: limitar intentos de acceso, registros técnicos', 'Art. 6.1.f — Interés legítimo en proteger el servicio'],
      ],
    },
    content2: [
      'No usamos tus datos para publicidad, perfiles comerciales ni decisiones automatizadas.',
    ],
  },
  {
    id: 'conservacion',
    num: '05',
    color: 'orange',
    title: 'Conservación de los datos',
    list: [
      '<strong>Datos de cuenta y actividad:</strong> mientras tengas la cuenta abierta. Al eliminarla se borran de inmediato.',
      '<strong>Sesiones (IP y navegador):</strong> se eliminan automáticamente al caducar la sesión (2 horas de inactividad).',
      '<strong>Registros técnicos del servidor (logs):</strong> rotación diaria, se borran a los 30 días.',
      '<strong>Tests publicados tras borrar la cuenta:</strong> si eliges conservarlos, quedan anónimos («Usuario eliminado») y sin ningún dato tuyo asociado.',
    ],
  },
  {
    id: 'destinatarios',
    num: '06',
    color: 'red',
    title: 'Destinatarios y transferencias',
    content: [
      'No vendemos, alquilamos ni cedemos tus datos a terceros. El único proveedor que accede a ellos es el de alojamiento, como encargado del tratamiento (art. 28 RGPD).',
      'Las fuentes tipográficas e iconos se sirven desde nuestro propio servidor: tu navegador no contacta con Google ni con otras CDN al visitar la web.',
    ],
    table: {
      headers: ['Proveedor', 'Función', 'Ubicación'],
      rows: [
        ['[PROVEEDOR DE HOSTING]', 'Servidor y base de datos', '[PAÍS DEL SERVIDOR]'],
      ],
    },
  },
  {
    id: 'derechos',
    num: '07',
    color: 'teal',
    title: 'Tus derechos',
    content: [
      'Puedes ejercer tus derechos directamente desde tu perfil, en la sección <strong>«Tus datos»</strong>, o escribiendo a <strong>' + CONTACT_EMAIL + '</strong>. Respondemos en un máximo de un mes.',
    ],
    list: [
      '<strong>Acceso y portabilidad:</strong> botón «Descargar mis datos» (archivo JSON con todo lo que guardamos sobre ti).',
      '<strong>Supresión:</strong> botón «Eliminar mi cuenta». Puedes elegir si tus tests publicados se borran o se conservan anónimos.',
      '<strong>Rectificación:</strong> puedes cambiar tu foto desde el perfil; para otros datos, escríbenos.',
      '<strong>Limitación y oposición:</strong> escríbenos indicando tu caso.',
    ],
    highlight: {
      type: 'info',
      icon: 'fa-solid fa-scale-balanced',
      text: 'Si consideras que tus derechos no han sido atendidos, puedes reclamar ante la <strong>Agencia Española de Protección de Datos (AEPD)</strong> en <strong>aepd.es</strong>.',
    },
  },
  {
    id: 'cookies',
    num: '08',
    color: 'indigo',
    title: 'Cookies y almacenamiento local',
    content: [
      'Solo usamos lo estrictamente necesario para que la web funcione, por lo que no requiere consentimiento (art. 22.2 LSSI).',
    ],
    table: {
      headers: ['Nombre', 'Tipo', 'Finalidad', 'Duración'],
      rows: [
        ['testmaster-session', 'Cookie técnica (httpOnly)', 'Mantener tu sesión iniciada', '2 horas de inactividad'],
        ['XSRF-TOKEN', 'Cookie técnica', 'Protección contra ataques CSRF', '2 horas'],
        ['theme', 'Almacenamiento local', 'Recordar el tema claro/oscuro que elegiste', 'Hasta que lo borres'],
      ],
    },
    content2: [
      'No utilizamos cookies de analítica, publicidad ni de terceros.',
    ],
  },
  {
    id: 'seguridad',
    num: '09',
    color: 'blue',
    title: 'Seguridad de los datos',
    list: [
      'Comunicaciones cifradas con <strong>HTTPS</strong>.',
      'Contraseñas guardadas solo como <strong>hash bcrypt</strong>.',
      'La sesión se guarda en una cookie <strong>httpOnly</strong> inaccesible para scripts, con protección CSRF.',
      'Límite de intentos de inicio de sesión para frenar ataques de fuerza bruta.',
    ],
    highlight: {
      type: 'warning',
      icon: 'fa-solid fa-triangle-exclamation',
      text: 'Si se produjera una brecha de seguridad que afecte a tus datos, lo notificaremos a la AEPD en 72 horas y a ti cuando suponga un riesgo alto (arts. 33-34 RGPD).',
    },
  },
  {
    id: 'menores',
    num: '10',
    color: 'pink',
    title: 'Menores de edad',
    content: [
      'Para registrarte debes tener al menos <strong>14 años</strong> (art. 7 LOPDGDD). Los menores de 14 años necesitan el consentimiento de sus padres o tutores y no deben registrarse por su cuenta.',
      `Si sabes que un menor de 14 años se ha registrado, escríbenos a <strong>${CONTACT_EMAIL}</strong> y eliminaremos su cuenta.`,
    ],
  },
  {
    id: 'cambios',
    num: '11',
    color: 'gray',
    title: 'Cambios en esta política',
    content: [
      'Si actualizamos esta política, cambiaremos la fecha y la versión indicadas arriba. Guardamos qué versión aceptó cada usuario al registrarse.',
    ],
  },
];
</script>

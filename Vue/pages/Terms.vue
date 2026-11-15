<template>
  <LegalDocument
    title="Términos y condiciones"
    intro="Las reglas para usar TestMaster, escritas en lenguaje claro para que sepas cuáles son tus derechos y obligaciones."
    :meta="['Vigentes desde el 14 de abril de 2026', 'Versión 1.0', 'Ley aplicable: España / UE']"
    :summary="summary"
    :sections="sections"
    :footer="footer"
  />
</template>

<script setup>
import LegalDocument from '../components/legal/LegalDocument.vue';

const summary = {
  title: 'Lo más importante',
  text: 'Al usar TestMaster confirmas que tienes al menos 14 años y que aceptas estos términos. Si no estás de acuerdo, no uses el servicio.',
  points: [
    { type: 'ok', text: 'Uso gratuito' },
    { type: 'ok', text: 'Tus tests son tuyos' },
    { type: 'ok', text: 'Sin publicidad intrusiva' },
    { type: 'warn', text: 'Prohibido el contenido dañino' },
    { type: 'warn', text: 'Una cuenta por persona' },
    { type: 'info', text: 'Solo mayores de 14 años' },
  ],
};

const footer = {
  title: '¿Tienes dudas legales?',
  text: 'Escríbenos antes de usar el servicio si algo no te queda claro.',
  actions: [
    { label: 'Escribir a lukemartenllorente@gmail.com', href: 'mailto:lukemartenllorente@gmail.com', primary: true },
    { label: 'Política de privacidad', to: '/privacy' },
    { label: 'Centro de ayuda', to: '/help' },
  ],
};

const sections = [
  {
    id: 'objeto',
    num: '01',
    color: 'blue',
    title: 'Objeto y ámbito de aplicación',
    content: [
      'Los presentes Términos y Condiciones (en adelante, "Términos") regulan el acceso y uso de la plataforma TestMaster, accesible en <strong>testmaster.app</strong> y sus subdominios, así como cualquier aplicación móvil o herramienta asociada (en conjunto, el "Servicio").',
      'El Servicio es operado por <strong>Luke Llorente</strong> (en adelante, "TestMaster", "nosotros", "el responsable"). Al registrarte o usar el Servicio aceptas estos Términos junto con nuestra <strong>Política de Privacidad</strong>, que forma parte integrante de este acuerdo.',
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
          'Tener al menos <strong>14 años</strong> de edad (o la edad mínima legal en tu país, si es superior).',
          'Proporcionar una dirección de correo electrónico <strong>válida y real</strong> que sea tuya.',
          'Crear una contraseña segura y mantenerla confidencial.',
          'Ser una persona física (no se permiten cuentas de bots automatizados sin autorización expresa).',
        ],
      },
      {
        title: 'Seguridad de la cuenta',
        icon: 'fa-solid fa-lock',
        content: [
          'Eres el único responsable de mantener la confidencialidad de tus credenciales y de todas las actividades que se realicen desde tu cuenta. Notifícanos de inmediato a <strong>lukemartenllorente@gmail.com</strong> si sospechas un acceso no autorizado.',
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
      'Todos los elementos que constituyen TestMaster — incluyendo el software, diseño, logotipos, marca, interfaz de usuario, código fuente, base de datos de categorías y cualquier otro material desarrollado por el responsable — son propiedad exclusiva de <strong>Luke Llorente</strong> o de sus licenciantes y están protegidos por las leyes de propiedad intelectual e industrial aplicables.',
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
          'Puedes cancelar tu cuenta en cualquier momento desde la configuración de tu perfil o contactando con <strong>lukemartenllorente@gmail.com</strong>. La cancelación conlleva la eliminación definitiva de tus datos en los plazos descritos en la Política de Privacidad.',
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
      'El tratamiento de tus datos personales se rige por nuestra <strong>Política de Privacidad</strong>, disponible en <a href="/privacy">/privacy</a>, que forma parte integrante de estos Términos y que te recomendamos leer detenidamente.',
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
</script>

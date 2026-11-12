# Equipo7-Proyecto

El Changarro Inteligente
Herramienta web para que pequeños negocios de Veracruz administren productos, ventas, inventario, agenda y atención por WhatsApp desde un flujo sencillo.

Problema
Fondas, tiendas, talleres, consultorios y puestos suelen atender por WhatsApp, anotar ventas en libreta y revisar existencias manualmente. Esto puede retrasar la atención, dificultar el control de existencias y dejar a la persona dueña sin un resumen accionable.

Persona usuaria
La persona principal es la dueña o el dueño de un pequeño negocio que tiene poco tiempo y poca experiencia digital. Necesita registrar una venta o actualizar el inventario sin navegar procesos largos, consultar una recomendación comprensible y conservar el control de las decisiones.

El sistema contempla giros como abarrotes y estética. El código y los datos de demostración incluyen esos dos giros; no se presenta como una solución ya validada para todos los tipos de negocio.

Qué hace
Registra propietarios y uno o más negocios, con catálogo de productos y servicios.
Registra ventas, pedidos, citas, clientes, proveedores y existencias.
Permite capturar inventario mediante voz o escaneo OCR de una fotografía de ticket, revisar los datos propuestos y confirmar el lote.
Muestra reportes y análisis de ventas; exporta información en CSV y ofrece salida PDF para reportes/tickets.
Incluye código para atención por WhatsApp, recepción de pedidos y citas, notificaciones y envío de comprobantes.
Genera un resumen de la semana anterior con métricas y una recomendación concreta.

El giro del reto: resumen de cada lunes
En el código existe un generador de reporte semanal que calcula el periodo lunes-domingo anterior, compara ventas con la semana previa, identifica productos y alertas, redacta una recomendación y produce un PDF. El endpoint también puede devolver un objeto JSON con el texto de resumen para WhatsApp.

Pendiente importante: 
La funcionalidad permite generar el reporte de manera manual para que posteriormente sea enviado al dueño del emprendimiento. Por lo tanto, el envío del resumen semanal se realiza manualmente y no requiere una programación recurrente ni una integración automática con WhatsApp.

Para que aparezca en la demo final, abre Reportes → Resumen semanal PDF y muestra el mensaje JSON generado por ayudas/reporte_semanal_pdf.php?formato=json. Para mostrarlo dentro de WhatsApp, el equipo puede enviar manualmente el campo mensaje durante la demo, dejando claro que esa entrega es manual. El guion está en docs/demo_resumen_lunes.md.

Cómo se usa
Inicia sesión y crea o selecciona el negocio.
Configura el giro, horario y datos básicos.
Registra el catálogo y existencias; para una captura por voz o foto, revisa y corrige la propuesta antes de guardar.
Registra ventas desde Caja o recibe pedidos/citas por el canal de WhatsApp configurado.
Consulta Agenda, Finanzas, Análisis y Reportes para dar seguimiento a la operación.
Cada lunes, genera el resumen de la semana anterior. En esta versión la generación es manual; el envío recurrente requiere implementar la tarea programada descrita en la documentación técnica.

Tecnologías
PHP 8 con organización MVC, HTML, CSS y JavaScript de navegador.
MySQL/MariaDB mediante PDO.
Node.js y Express para el puente local de WhatsApp (bot.js), con whatsapp-web.js y Puppeteer.
También existe una ruta separada para WhatsApp Cloud API de Meta y un agente conversacional que usa Anthropic Claude cuando se configura.
Tesseract.js v5 para OCR en el navegador; Web Speech API en español para dictado; Dompdf para PDF.
El ZIP de proyecto contiene dos implementaciones de WhatsApp distintas. La ruta local bot.js/webhook.php y la ruta de Cloud API webhook_whatsapp.php no son una sola integración; el despliegue debe escoger y configurar la ruta que se usará. Las bitácoras también mencionan Gemini en una iteración anterior de OCR; la vista incluida en el código actual carga Tesseract.js.

Cómo instalar en desarrollo
La copia revisada fue preparada para XAMPP/Windows y requiere ajustar las rutas para otro entorno.

Requisitos
Apache con PHP 8.2 o compatible y extensiones PDO MySQL, cURL y mbstring.
MySQL o MariaDB; el dump adjunto se generó con MariaDB 10.4.32.
Node.js y npm para el puente local de WhatsApp; la bitácora registra Node.js 26.13.1.
Google Chrome para Puppeteer en la ruta local de bot.js, o ajustar executablePath al Chrome instalado.

Pasos
Coloca la carpeta del proyecto bajo la raíz web de Apache, por ejemplo htdocs/Changarro_Perfiles.
Crea la base changarro_inteligente e importa en phpMyAdmin el SQL que se adjuntó por separado a esta entrega. El ZIP del proyecto no incluía un archivo .sql; antes de guardar el dump en Git, anonimiza sus registros de demostración y ubícalo, por ejemplo, en database/changarro_inteligente.sql.
Ajusta host, base, usuario y contraseña en conexion.php para tu entorno. El archivo revisado trae valores locales de XAMPP; no los uses como configuración de producción ni publiques credenciales.
Desde la raíz del proyecto instala las dependencias Node: npm ci.
Inicia Apache y MySQL. Para la integración local, abre otra terminal en la raíz del proyecto y ejecuta node bot.js; escanea el QR con una cuenta de WhatsApp de prueba. La URL del webhook de bot.js apunta a http://127.0.0.1/Changarro_Perfiles/webhook.php; ajústala si cambia el directorio.
Abre http://localhost/Changarro_Perfiles/index.php en un navegador moderno.
Para WhatsApp Cloud API, el webhook debe ser accesible por HTTPS y configurarse en Meta. Copia config_agente.local.example.php a config_agente.local.php y configura los valores localmente o mediante variables de entorno; no pongas tokens ni secretos en Git o en la documentación.

Datos usados
El SQL adjunto incluye datos de demostración: 4 propietarios, 4 negocios, 20 productos o servicios, 4 clientes, 12 pedidos y 22 renglones de detalle. El esquema contiene tablas para propietarios, negocios, clientes, productos, pedidos, detalles, proveedores, conversaciones y mensajes. Los datos cubren abarrotes y estética y sirven para poblar pantallas/reportes; no son evidencia de resultados de negocios reales.

El dump contiene nombres, teléfonos, correos, ubicaciones y hashes de contraseña de demostración. Antes de publicar o compartir el repositorio, anonimiza esos registros y revisa cualquier dato personal. No agregues sesiones de WhatsApp ni archivos de estado de conversaciones.

Bitácora de IA
Las bitácoras adjuntas describen el uso de IA como apoyo de pair-programming, diseño MVC, depuración, consultas SQL, integración de voz/OCR, chatbot, tickets PDF y documentación. Reportan el uso de Google Gemini en arquitectura y una iteración OCR, y Anthropic Claude en la ruta conversacional actual. Las propuestas y el código generado fueron adaptados por el equipo.

La documentación técnica distingue lo que aparece en el código actual de lo que las bitácoras relatan como decisión o prueba manual. No se inventan resultados de pruebas que no hayan sido repetidos en un entorno reproducible.

Documentación
docs/analisis_y_diseno.md: análisis funcional, requerimientos, diseño UX, diagramas de flujo/arquitectura y casos de uso.
docs/pruebas_funcionales_usabilidad.md: casos funcionales y protocolo de pruebas de usabilidad; identifica qué no se ejecutó.
docs/demo_resumen_lunes.md: preparación y guion para presentar el giro de los lunes.

Seguridad y límites conocidos
El ZIP original incluía dependencias instaladas y directorios locales de WhatsApp/sesiones. La copia documental limpia excluye node_modules, .wwebjs_cache, sesion_whatsapp, sesiones y reportes locales. Reinstala dependencias con npm ci y autentica WhatsApp de nuevo en tu propio equipo.
La base almacena el stock actual, pero no se observa un historial completo de movimientos de inventario ni una fecha de caducidad en la tabla de productos.
El control de gastos, los precios automáticos de la central de abasto, el envío recurrente del reporte y el modo offline requieren validación o trabajo adicional.
Antes de producción: usa secretos gestionados, HTTPS, respaldos, acceso por negocio, consentimiento para mensajes, pruebas de restauración y revisión de privacidad/seguridad.

Fuentes de esta documentación
Se revisaron el ZIP del proyecto, el dump SQL y los archivos adjuntos de requisitos funcionales/no funcionales y bitácoras de desarrollo asistido por IA. La matriz y el plan de pruebas describen el estado observado; la instalación definitiva y el comportamiento en WhatsApp deben validarse en el entorno del equipo.

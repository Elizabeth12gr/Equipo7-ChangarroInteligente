const express = require('express');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');

const app = express();
app.use(express.json());

// URL local de tu webhook en XAMPP
const WEBHOOK_URL = 'http://127.0.0.1/Changarro_Perfiles/webhook.php';

// Guarda el ID exacto del último chat activo para evitar errores de LID al mandar PDF
const chatsActivos = new Map();

const client = new Client({
    authStrategy: new LocalAuth({ dataPath: './sesion_whatsapp' }),
    authTimeoutMs: 60000,
    webVersionCache: {
        type: 'none'
    },
    puppeteer: {
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: false,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
        ]
    }
});

client.on('qr', (qr) => {
    console.log('\n📱 Escanea este código QR con tu WhatsApp:\n');
    qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
    console.log('✅ Bot de WhatsApp conectado y listo.');
});

// Escucha mensajes de otros contactos Y también mensajes que te envíes a tu propio chat
client.on('message_create', async (msg) => {
    // Si el mensaje lo enviaste tú hacia otra persona, ignorarlo para no crear bucles
    if (msg.fromMe && msg.to !== msg.from) return;
    if (msg.from === 'status@broadcast' || msg.from.endsWith('@g.us')) return;

    // Ignorar las propias respuestas del bot para evitar bucle infinito
    const marcadoresBot = ['👋', '🏪', '📦', '💇‍♀️', '🛒', '✅', '📋', '🛍️', '🚚', '🛵', '🎉', '⚠️', '🚫', '🕒', '⏳', '🗑️', 'Por favor responde', 'Opción no válida', 'Aún no has', 'Vuelve a elegir', 'Este negocio'];
    if (msg.fromMe && marcadoresBot.some(m => (msg.body || '').startsWith(m))) return;

    try {
        const contacto = await msg.getContact();
        const chatIdReal = msg.from;
        const numeroLimpio = chatIdReal.replace(/@c\.us|@lid/g, '');

        // Guardar relación número -> chatIdReal (con @lid o @c.us)
        chatsActivos.set(numeroLimpio, chatIdReal);
        chatsActivos.set(chatIdReal, chatIdReal);

        const params = new URLSearchParams();
        params.append('From', `whatsapp:${numeroLimpio}`);
        params.append('Body', msg.body || '');
        params.append('ProfileName', contacto.pushname || contacto.name || 'Cliente');

        if (msg.location) {
            params.append('Latitude', msg.location.latitude);
            params.append('Longitude', msg.location.longitude);
        }

        const resp = await fetch(WEBHOOK_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });

        const xml = await resp.text();
        const match = xml.match(/<Message>([\s\S]*?)<\/Message>/);

        if (match && match[1]) {
            const textoRespuesta = match[1]
                .replace(/&amp;/g, '&')
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>')
                .replace(/&quot;/g, '"')
                .replace(/&apos;/g, "'");

            await client.sendMessage(chatIdReal, textoRespuesta);
        }
    } catch (err) {
        console.error('❌ Error procesando mensaje hacia webhook.php:', err.message);
    }
});

// Endpoint 1: Recordatorios y avisos programados
app.post('/notificar', (req, res) => {
    const { telefono, mensaje, delaySegundos } = req.body;
    if (!telefono || !mensaje) {
        return res.status(400).json({ ok: false, error: 'Faltan datos' });
    }

    const limpio = telefono.replace(/[^0-9]/g, '');
    const chatId = chatsActivos.get(telefono) || chatsActivos.get(limpio) || `${limpio}@c.us`;
    const ms = Math.max(1, Number(delaySegundos || 10)) * 1000;

    setTimeout(async () => {
        try {
            await client.sendMessage(chatId, mensaje);
            console.log(`🔔 Notificación enviada a ${chatId}`);
        } catch (err) {
            console.error('❌ Error enviando notificación:', err.message);
        }
    }, ms);

    res.json({ ok: true });
});

// Endpoint 2: Descargar el Ticket PDF desde PHP y enviarlo adjunto por WhatsApp
app.post('/enviar-pdf', async (req, res) => {
    const { telefono, urlPdf, nombreArchivo, caption } = req.body;
    if (!telefono || !urlPdf) {
        return res.status(400).json({ ok: false, error: 'Faltan telefono o urlPdf' });
    }

    const limpio = telefono.replace(/[^0-9]/g, '');
    // Usa el ID exacto (@lid o @c.us) del chat desde donde escribió el cliente
    const chatId = chatsActivos.get(telefono) || chatsActivos.get(limpio) || `${limpio}@c.us`;

    try {
        const respuesta = await fetch(urlPdf);
        if (!respuesta.ok) {
            throw new Error(`HTTP ${respuesta.status} al obtener PDF`);
        }

        const arrayBuffer = await respuesta.arrayBuffer();
        const base64Pdf = Buffer.from(arrayBuffer).toString('base64');

        const media = new MessageMedia(
            'application/pdf',
            base64Pdf,
            nombreArchivo || 'Ticket.pdf'
        );

        await client.sendMessage(chatId, media, {
            caption: caption || '🧾 Aquí tienes tu comprobante en PDF.'
        });

        console.log(`📄 Ticket PDF enviado a ${chatId} (${nombreArchivo})`);
        res.json({ ok: true });
    } catch (err) {
        console.error('❌ Error enviando PDF por WhatsApp:', err.message);
        res.status(500).json({ ok: false, error: err.message });
    }
});

app.listen(3001, '127.0.0.1', () => {
    console.log('🚀 Servidor puente del bot escuchando en http://127.0.0.1:3001');
});

client.initialize();
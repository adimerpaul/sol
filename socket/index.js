const express = require('express');
const { createServer } = require('http');
const { join } = require('path');
const {Server} = require('socket.io');
const cors = require('cors');

const app = express();
const server = createServer(app);
const io = new Server(server,{
    cors: {
        origin: '*',
    }
});
// env
require('dotenv').config();

app.use(cors({
    origin: '*'
}));
app.use(express.json({ limit: '1mb' }));

function emitEvent(eventName, payload) {
    const event = (eventName || 'votacion').toString().trim() || 'votacion';
    io.emit(event, payload ?? {});
    return event;
}

app.get('/', (req, res) => {
    res.sendFile(join(__dirname, 'index.html'));
});
// ruta cors mandar get
// app.get('/send', (req, res) => {
//     io.emit('reservas', 'Hola desde el servidor');
//     res.send('Mensaje enviado');
// });
app.get('/votacion', (req, res) => {
    emitEvent('votacion', {
        title: 'Nuevo dato registrado',
        message: 'Evento manual de votacion',
        kind: 'manual'
    });
    res.json({ ok: true, event: 'votacion' });
});

// app.get('/silSolicitud', (req, res) => {
//     emitEvent('votacion', {
//         title: 'Nuevo dato registrado',
//         message: 'Evento legacy silSolicitud',
//         kind: 'legacy'
//     });
//     res.json({ ok: true, event: 'votacion' });
// });

app.post('/emit', (req, res) => {
    const event = emitEvent(req.body?.event, req.body?.payload ?? {});
    res.json({ ok: true, event });
});
io.on('connection', (socket) => {
    // console.log('a user connected');
    socket.on('disconnect', () => {
        console.log('user disconnected');
    });
    // reservas
    // socket.on('reservas', (msg) => {
    //     console.log('reservas: ' + msg);
    //     io.emit('reservas', msg);
    // });
    socket.on('votacion', (msg) => {
        console.log('votacion: ' + msg);
        io.emit('votacion', msg);
    });
    socket.broadcast.emit('hi');
});

port=process.env.PORT || 3000
server.listen(port, () => {
    console.log('http://localhost:'+port);
});

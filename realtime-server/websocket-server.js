// Simple WebSocket broadcast server with HTTP POST broadcast endpoint
// Usage: npm install express ws body-parser
// Run: node websocket-server.js

const express = require('express');
const bodyParser = require('body-parser');
const { WebSocketServer } = require('ws');

const app = express();
app.use(bodyParser.json());

// Map of deliveryId -> Set of WebSocket clients
const subs = new Map();

const wss = new WebSocketServer({ port: 8080 });

wss.on('connection', (ws, req) => {
  // Expect client to send a subscription message after connect
  ws.on('message', (msg) => {
    try {
      const data = JSON.parse(msg.toString());
      if (data && data.action === 'subscribe' && data.delivery_id) {
        const id = String(data.delivery_id);
        ws.deliveryId = id;
        if (!subs.has(id)) subs.set(id, new Set());
        subs.get(id).add(ws);
      }
    } catch (err) {
      // ignore
    }
  });

  ws.on('close', () => {
    if (ws.deliveryId && subs.has(ws.deliveryId)) {
      subs.get(ws.deliveryId).delete(ws);
      if (subs.get(ws.deliveryId).size === 0) subs.delete(ws.deliveryId);
    }
  });
});

// HTTP endpoint: POST /broadcast { delivery_id, driver_latitude, driver_longitude }
app.post('/broadcast', (req, res) => {
  const { delivery_id, driver_latitude, driver_longitude } = req.body || {};
  if (!delivery_id) return res.status(400).json({ ok: false, message: 'delivery_id required' });
  const payload = JSON.stringify({ driver_latitude: driver_latitude ?? null, driver_longitude: driver_longitude ?? null, delivery_id });
  const set = subs.get(String(delivery_id));
  if (set) {
    for (const ws of set) {
      if (ws.readyState === ws.OPEN) {
        ws.send(payload);
      }
    }
  }
  return res.json({ ok: true });
});

const httpPort = 3001;
app.listen(httpPort, () => {
  console.log(`Broadcast HTTP endpoint listening on http://localhost:${httpPort}/broadcast`);
  console.log('WebSocket server listening on ws://localhost:8080');
});

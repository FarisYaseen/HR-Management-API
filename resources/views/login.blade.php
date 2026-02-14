<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HR Management</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }
        .card {
            width: 100%;
            max-width: 380px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
        }
        h1 {
            margin: 0 0 16px;
            font-size: 22px;
        }
        .field { margin-bottom: 12px; }
        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        button {
            width: 100%;
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            background: #2563eb;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        button:disabled {
            opacity: 0.75;
            cursor: not-allowed;
        }
        .error {
            margin-top: 10px;
            color: #dc2626;
            font-size: 13px;
            min-height: 18px;
        }
        .helper {
            margin-top: 12px;
            font-size: 14px;
            color: #374151;
        }
        .helper a {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }
        .helper a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Login</h1>
        <form id="loginForm">
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>
            <button id="loginBtn" type="submit">Sign in</button>
            <div class="error" id="errorBox"></div>
        </form>
        <p class="helper">Don't have an account? <a href="/register">Register now</a></p>
    </div>

    <script>
        async function hasValidToken(token) {
            const response = await fetch('/api/user', {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.ok;
        }

        (async function redirectIfLoggedIn() {
            const token = localStorage.getItem('auth_token');
            if (!token) return;

            try {
                if (await hasValidToken(token)) {
                    window.location.replace('/');
                } else {
                    localStorage.removeItem('auth_token');
                }
            } catch (_) {}
        })();

        const form = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const errorBox = document.getElementById('errorBox');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorBox.textContent = '';
            loginBtn.disabled = true;

            const payload = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            };

            try {
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok || !data.token) {
                    errorBox.textContent = data.message || 'Invalid email or password.';
                    loginBtn.disabled = false;
                    return;
                }

                localStorage.setItem('auth_token', data.token);
                window.location.replace('/');
            } catch (_) {
                errorBox.textContent = 'Network error. Please try again.';
                loginBtn.disabled = false;
            }
        });
    </script>
</body>
</html>

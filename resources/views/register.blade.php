<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HR Management</title>
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
            max-width: 420px;
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
        <h1>Register</h1>
        <form id="registerForm">
            <div class="field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required>
            </div>
            <button id="registerBtn" type="submit">Create account</button>
            <div class="error" id="errorBox"></div>
        </form>
        <p class="helper">Already have an account? <a href="/login">Login now</a></p>
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

        const form = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        const errorBox = document.getElementById('errorBox');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorBox.textContent = '';
            registerBtn.disabled = true;

            const payload = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                password_confirmation: document.getElementById('password_confirmation').value
            };

            try {
                const response = await fetch('/api/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok || !data.token) {
                    if (data.errors) {
                        const firstError = Object.values(data.errors)[0];
                        errorBox.textContent = Array.isArray(firstError) ? firstError[0] : 'Registration failed.';
                    } else {
                        errorBox.textContent = data.message || 'Registration failed.';
                    }
                    registerBtn.disabled = false;
                    return;
                }

                localStorage.setItem('auth_token', data.token);
                window.location.replace('/');
            } catch (_) {
                errorBox.textContent = 'Network error. Please try again.';
                registerBtn.disabled = false;
            }
        });
    </script>
</body>
</html>

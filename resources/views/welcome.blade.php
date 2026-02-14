<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #1f2937;
        }

        .navbar {
            height: 64px;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .brand {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .user-menu {
            position: relative;
        }

        .avatar-btn {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            background: #2563eb;
            color: #ffffff;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .dropdown {
            position: absolute;
            right: 0;
            top: 52px;
            width: 180px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            display: none;
            padding: 8px;
        }

        .dropdown.show {
            display: block;
        }

        .dropdown button {
            width: 100%;
            border: none;
            background: transparent;
            text-align: left;
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            color: #dc2626;
            font-weight: 600;
        }

        .dropdown button:hover {
            background: #fee2e2;
        }

        .content {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="brand">HR Management</div>

        <div class="user-menu">
            <button id="avatarBtn" class="avatar-btn" aria-label="User menu">U</button>
            <div id="dropdown" class="dropdown">
                <button id="logoutBtn">Logout</button>
            </div>
        </div>
    </nav>

    <main class="content">
        <div class="card">
            <h2>Welcome</h2>
            <p>You are logged in.</p>
        </div>
    </main>

    <script>
        const avatarBtn = document.getElementById('avatarBtn');
        const dropdown = document.getElementById('dropdown');
        const logoutBtn = document.getElementById('logoutBtn');

        async function hasValidToken(token) {
            const response = await fetch('/api/user', {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            });
            return response.ok;
        }

        (async function protectHomePage() {
            const token = localStorage.getItem('auth_token');

            if (!token) {
                window.location.replace('/login');
                return;
            }

            try {
                const valid = await hasValidToken(token);
                if (!valid) {
                    localStorage.removeItem('auth_token');
                    window.location.replace('/login');
                }
            } catch (_) {
                localStorage.removeItem('auth_token');
                window.location.replace('/login');
            }
        })();

        avatarBtn.addEventListener('click', () => {
            dropdown.classList.toggle('show');
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.user-menu')) {
                dropdown.classList.remove('show');
            }
        });

        logoutBtn.addEventListener('click', async () => {
            const token = localStorage.getItem('auth_token');

            if (!token) {
                window.location.replace('/login');
                return;
            }

            try {
                const response = await fetch('/api/logout', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });

                if (!response.ok) {
                    alert('Logout failed');
                    return;
                }

                localStorage.removeItem('auth_token');
                window.location.replace('/login');
            } catch (_) {
                alert('Network error while logging out');
            }
        });
    </script>
</body>
</html>

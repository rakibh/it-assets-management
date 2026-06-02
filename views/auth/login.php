<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IT Management System</title>
    <!-- Fallback Helper -->
    <script>
        function scriptFallback(src) {
            var script = document.createElement('script');
            script.src = src;
            script.defer = true;
            document.head.appendChild(script);
        }
        function styleFallback(href) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            document.head.appendChild(link);
        }
    </script>
    <!-- Tailwind CSS with Fallback -->
    <script src="https://cdn.tailwindcss.com" onerror="scriptFallback('fallback/js/tailwind.min.js')"></script>
    <script>
        window.tailwind = window.tailwind || {};
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" onerror="scriptFallback('fallback/js/alpine.min.js')"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" 
          onerror="styleFallback('fallback/css/bootstrap-icons.min.css')">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
          onerror="styleFallback('fallback/css/fonts/stylesheet.css')">
    <style>
        [x-cloak] { display: none !important; }
        .font-inter { font-family: 'Inter', sans-serif !important; }
    </style>
</head>
<body class="bg-slate-100 h-screen flex items-center justify-center font-inter">

    <div class="w-full max-w-md" x-data="loginForm()">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-slate-800 p-8 text-white text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4">
                    <i class="bi bi-laptop text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold uppercase tracking-wider">IT Manager</h1>
                <p class="text-slate-400 text-sm mt-1">System Access Portal</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitLogin" class="p-8 space-y-6">
                <?php if (isset($_GET['timeout'])): ?>
                    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 text-amber-700 text-sm mb-4">
                        <p class="font-bold">Session Expired</p>
                        <p>You have been logged out due to inactivity.</p>
                    </div>
                <?php endif; ?>

                <div x-show="error" x-transition x-cloak class="bg-red-50 border-l-4 border-red-500 p-4 text-red-700 text-sm">
                    <p x-text="error"></p>
                </div>

                <!-- Identifier -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Employee ID / Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text" x-model="formData.identifier" required
                            class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none"
                            placeholder="Enter ID or Username">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input :type="showPassword ? 'text' : 'password'" x-model="formData.password" required
                            class="w-full pl-10 pr-12 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none"
                            placeholder="••••••••">
                        <button type="button" @click="showPassword = !showPassword" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-blue-600 transition-colors">
                            <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" :disabled="loading"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-all flex items-center justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Login</span>
                    <span x-show="loading" class="animate-spin mr-2"><i class="bi bi-arrow-repeat"></i></span>
                    <i x-show="!loading" class="bi bi-box-arrow-in-right"></i>
                </button>
            </form>

            <!-- Footer -->
            <div class="bg-slate-50 p-4 text-center border-t border-slate-100">
                <p class="text-xs text-slate-500">&copy; 2026 IT Management System. v2.0</p>
            </div>
        </div>
    </div>

    <script>
        function loginForm() {
            return {
                formData: {
                    identifier: '',
                    password: '',
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                },
                showPassword: false,
                loading: false,
                error: '',
                async submitLogin() {
                    this.loading = true;
                    this.error = '';

                    try {
                        const response = await fetch('index.php?route=login_submit', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(this.formData)
                        });

                        const result = await response.json();

                        if (result.success) {
                            window.location.href = result.redirect;
                        } else {
                            this.error = result.message;
                        }
                    } catch (e) {
                        this.error = 'An unexpected error occurred. Please try again.';
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>

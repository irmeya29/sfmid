<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | SFMID Gestion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }

        .login-bg {
            background-image: url('/sfmid.jpg');
            background-size: cover;
            background-position: center;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card-anim {
            animation: fadeUp 1.1s cubic-bezier(.16,1,.3,1) both;
        }

        .form-input { transition: border-color .2s, background .2s, box-shadow .2s; }
        .form-input:focus {
            border-color: #2676B3 !important;
            background: #fff !important;
            box-shadow: 0 0 0 3px rgba(38,118,179,.10) !important;
            outline: none !important;
        }

        .custom-check input[type="checkbox"] {
            appearance: none; -webkit-appearance: none;
            width: 18px; height: 18px; border-radius: 5px;
            border: 1.5px solid #CBD5E1; background: #F8FAFC;
            cursor: pointer; flex-shrink: 0;
            transition: background .15s, border-color .15s;
        }
        .custom-check input[type="checkbox"]:checked {
            background: #2676B3; border-color: #2676B3;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 9'%3E%3Cpath d='M1 4l3.5 3.5L11 1' stroke='%23fff' stroke-width='1.8' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: center; background-size: 11px;
        }

        .btn-submit { transition: background .2s, transform .1s; }
        .btn-submit:hover  { background: #1E5F91 !important; }
        .btn-submit:active { transform: scale(.99); }

       .signature {
    position: absolute;
    bottom: 18px;
    right: 22px;
    z-index: 10;
    font-size: 12px;
    font-weight: 700;
    color: rgba(255,255,255,.75);
    letter-spacing: 1px;
    text-decoration: none;
    text-transform: uppercase;
    transition: color .2s;
}
.signature:hover { color: #FA820A; }
    </style>
</head>
<body class="min-h-screen antialiased">

    <main class="login-bg relative flex min-h-screen items-center justify-center px-5 py-10">

        {{-- Overlay sombre sur l'image --}}
        <div class="absolute inset-0 bg-[#0D2E4D]/62"></div>

        {{-- Card centré --}}
        <div class="card-anim relative z-10 w-full max-w-[420px] overflow-hidden rounded-3xl bg-white shadow-[0_32px_64px_rgba(0,0,0,.32)]">

            {{-- ── Header bleu ── --}}
            <div class="bg-[#2676B3] px-9 py-8">
                <div class="flex items-center gap-4">

                    {{-- Logo fond blanc pour que le SVG bleu soit visible --}}
                    <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-white p-2 shadow-sm">
                        <img src="{{ route('brand.logo') }}" alt="Logo SFMID" class="h-full w-full object-contain">
                    </div>

                    <div>
                        <div class="text-2xl font-black leading-none tracking-tight text-white">SFMID</div>
                        <div class="mt-1.5 text-[11px] font-semibold uppercase tracking-[2px] text-white/50">Application de gestion</div>
                    </div>
                </div>
                <div class="mt-5 h-[3px] w-9 rounded-full bg-[#FA820A]"></div>
            </div>

            {{-- ── Body formulaire ── --}}
            <div class="px-9 py-8">

                <h2 class="text-xl font-black tracking-tight text-[#0D2E4D]">Connexion</h2>
                <p class="mt-1 mb-7 text-sm text-slate-400">Entrez vos identifiants pour accéder à votre espace.</p>

                @if($errors->any())
                    <div class="mb-6 flex items-start gap-3 rounded-xl border-[1.5px] border-red-200 bg-red-50 px-4 py-3">
                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <p class="text-sm font-semibold text-red-800">Identifiants incorrects ou compte inactif.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="mb-2 block text-[11px] font-bold uppercase tracking-[1.5px] text-slate-500">
                            Adresse e-mail
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center">
                                <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <input id="email" name="email" type="email"
                                   value="{{ old('email') }}"
                                   autocomplete="email" autofocus
                                   placeholder="vous@exemple.com"
                                   class="form-input w-full rounded-xl border-[1.5px] border-slate-200 bg-slate-50 py-3.5 pl-11 pr-4 text-sm text-slate-900 placeholder-slate-300">
                        </div>
                        @error('email')
                            <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Mot de passe --}}
                    <div>
                        <label for="password" class="mb-2 block text-[11px] font-bold uppercase tracking-[1.5px] text-slate-500">
                            Mot de passe
                        </label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center">
                                <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path stroke-linecap="round" d="M7 11V7a5 5 0 0110 0v4"/>
                                </svg>
                            </span>
                            <input id="password" name="password" type="password"
                                   autocomplete="current-password"
                                   placeholder="••••••••"
                                   class="form-input w-full rounded-xl border-[1.5px] border-slate-200 bg-slate-50 py-3.5 pl-11 pr-12 text-sm text-slate-900">
                            <button type="button" data-password-toggle="password" class="absolute inset-y-0 right-3 flex items-center rounded-lg px-2 text-slate-400 hover:text-[#2676B3]" aria-label="Afficher le mot de passe">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Se souvenir --}}
                    <div class="custom-check flex items-center gap-3">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember" class="cursor-pointer select-none text-sm font-medium text-slate-500">
                            Se souvenir de moi
                        </label>
                    </div>

                    {{-- Bouton --}}
                    <button type="submit"
                            class="btn-submit inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#2676B3] px-4 py-4 text-sm font-semibold text-white tracking-wide">
                        <i data-lucide="log-in" class="h-4 w-4"></i>
                        <span>Se connecter</span>
                    </button>

                </form>

                <p class="mt-7 text-center text-xs text-slate-300">
                    SFMID &copy; {{ date('Y') }} — Accès réservé aux membres autorisés
                </p>
            </div>

        </div>

        {{-- Signature --}}
        <a href="https://irmeya-ouedraogo.com" target="_blank" rel="noopener" class="signature">
            by irdel
        </a>

    </main>

<script>
    lucide.createIcons();

    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;

            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
            button.innerHTML = `<i data-lucide="${visible ? 'eye' : 'eye-off'}" class="h-4 w-4"></i>`;
            lucide.createIcons();
        });
    });
</script>
</body>
</html>

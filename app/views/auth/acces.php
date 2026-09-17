<?php
$activacio = ($acces['token_tipus'] ?? '') !== \App\Models\UsuariToken::TIPUS_RESET;
$titol = !$acces ? 'Enllaç no vàlid' : ($activacio ? 'Crea la teva contrasenya' : 'Canvia la teva contrasenya');
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex">
    <title><?= e($titol) ?> - <?= e(\App\Config\App::name()) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{brand:{DEFAULT:'#23AAC5',dark:'#1B8FA6',light:'#E8F7FA'}}}}}</script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-brand-light min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
            <div class="text-center mb-6">
                <img src="<?= url('img/GMAO-Logo-600.png') ?>" alt="GMAO" class="h-10 w-auto mx-auto mb-4">
                <h1 class="text-xl font-bold text-gray-800"><?= e($titol) ?></h1>
            </div>

            <?php if (!$acces): ?>
                <p class="text-sm text-gray-600 text-center">
                    Aquest enllaç ja s'ha fet servir, ha caducat o no és correcte.
                    Demana'n un de nou a la persona responsable de la teva instal·lació.
                </p>
                <a href="<?= url('login') ?>" class="mt-6 block w-full text-center bg-brand text-white py-2.5 rounded-lg font-medium hover:bg-brand-dark transition">
                    Anar a l'inici de sessió
                </a>
            <?php else: ?>
                <p class="text-sm text-gray-600 text-center mb-6">
                    Hola, <strong><?= e($acces['nom']) ?></strong>.
                    <?= $activacio ? 'Tria una contrasenya per entrar al GMAO.' : 'Tria una contrasenya nova.' ?>
                </p>

                <?php if (!empty($flash)): ?>
                    <div class="mb-4 p-3 rounded-lg text-sm <?= $flash['type'] === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200' ?>">
                        <?= e($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($sessioOberta !== null): ?>
                    <div class="mb-4 p-3 rounded-lg text-sm bg-amber-50 text-amber-800 border border-amber-200">
                        Ara tens la sessió oberta com a <strong><?= e($sessioOberta) ?></strong>. Si continues, es tancarà i entraràs com a <?= e($acces['nom']) ?>.
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= url('acces/' . $token) ?>">
                    <?= csrf_field() ?>

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correu electrònic</label>
                        <input type="email" id="email" value="<?= e($acces['email']) ?>" autocomplete="username" readonly
                               class="w-full px-4 py-2.5 border border-gray-200 bg-gray-50 rounded-lg text-gray-600 outline-none">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contrasenya nova</label>
                        <input type="password" id="password" name="password" required autofocus minlength="<?= (int)$minLlargada ?>" autocomplete="new-password"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand focus:border-brand outline-none transition">
                        <p class="text-xs text-gray-400 mt-1">Almenys <?= (int)$minLlargada ?> caràcters.</p>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmacio" class="block text-sm font-medium text-gray-700 mb-1">Repeteix la contrasenya</label>
                        <input type="password" id="password_confirmacio" name="password_confirmacio" required minlength="<?= (int)$minLlargada ?>" autocomplete="new-password"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand focus:border-brand outline-none transition">
                    </div>

                    <label class="flex items-center gap-2 mb-6 cursor-pointer">
                        <input type="checkbox" id="mostrar-contrasenya" class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand">
                        <span class="text-sm text-gray-600">Mostrar la contrasenya</span>
                    </label>

                    <button type="submit" class="w-full bg-brand text-white py-2.5 rounded-lg font-medium hover:bg-brand-dark focus:ring-4 focus:ring-brand/20 transition">
                        Desar i entrar
                    </button>
                </form>

                <script>
                document.getElementById('mostrar-contrasenya').addEventListener('change', function () {
                    const tipus = this.checked ? 'text' : 'password';
                    document.getElementById('password').type = tipus;
                    document.getElementById('password_confirmacio').type = tipus;
                });
                </script>
            <?php endif; ?>
        </div>
        <p class="text-center text-gray-400 text-xs mt-6">&copy; <?= date('Y') ?> GMAO E-Bone</p>
    </div>
</body>
</html>

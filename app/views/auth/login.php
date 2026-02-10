<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sessió - <?= e(\App\Config\App::name()) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">GMAO</h1>
                <p class="text-gray-500 text-sm mt-1">Gestió de Manteniment</p>
            </div>

            <?php if (!empty($flash)): ?>
                <div class="mb-4 p-3 rounded-lg text-sm <?= $flash['type'] === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200' ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('login') ?>">
                <?= csrf_field() ?>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correu electrònic</label>
                    <input type="email" id="email" name="email" required autofocus
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                           placeholder="usuari@exemple.com">
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contrasenya</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                           placeholder="••••••••">
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-medium hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition">
                    Iniciar sessió
                </button>
            </form>
        </div>
        <p class="text-center text-gray-400 text-xs mt-6">&copy; <?= date('Y') ?> GMAO E-Bone</p>
    </div>
</body>
</html>

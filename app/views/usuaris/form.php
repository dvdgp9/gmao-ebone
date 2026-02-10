<?php
$title = $usuari ? 'Editar Usuari' : 'Nou Usuari';
$action = $usuari ? url('usuaris/update/' . $usuari['id']) : url('usuaris/store');
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('usuaris') ?>" class="text-sm text-gray-500 hover:text-blue-600 transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tornar a usuaris
    </a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2"><?= e($title) ?></h2>
</div>

<form method="POST" action="<?= $action ?>" class="space-y-6">
    <?= csrf_field() ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Dades de l'usuari</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="nom" value="<?= e($usuari['nom'] ?? '') ?>" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cognoms</label>
                <input type="text" name="cognoms" value="<?= e($usuari['cognoms'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="<?= e($usuari['email'] ?? '') ?>" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Contrasenya <?= $usuari ? '' : '<span class="text-red-500">*</span>' ?>
                </label>
                <input type="password" name="password" <?= $usuari ? '' : 'required' ?>
                       placeholder="<?= $usuari ? 'Deixar en blanc per no canviar' : '' ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
        </div>
        <div class="mt-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="actiu" value="1" <?= ($usuari['actiu'] ?? 1) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="text-sm text-gray-700">Usuari actiu</span>
            </label>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Assignació a instal·lació</h3>

        <?php if (!empty($assignacions)): ?>
        <div class="mb-4">
            <p class="text-sm text-gray-500 mb-2">Assignacions actuals:</p>
            <div class="space-y-1">
                <?php foreach ($assignacions as $a): ?>
                <div class="flex items-center gap-2 text-sm">
                    <span class="font-medium text-gray-700"><?= e($a['instalacio_nom']) ?></span>
                    <span class="text-gray-400">→</span>
                    <span class="inline-block bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded"><?= e(ucfirst(str_replace('_', ' ', $a['rol_nom']))) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <hr class="my-4 border-gray-200">
        <p class="text-sm text-gray-500 mb-3">Actualitzar o afegir assignació:</p>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Instal·lació</label>
                <select name="instalacio_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($instalacions as $inst): ?>
                        <option value="<?= $inst['id'] ?>"><?= e($inst['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                <select name="rol_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($rols as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= e(ucfirst(str_replace('_', ' ', $r['nom']))) ?> — <?= e($r['descripcio'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
            <?= $usuari ? 'Actualitzar' : 'Crear usuari' ?>
        </button>
        <a href="<?= url('usuaris') ?>" class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
            Cancel·lar
        </a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

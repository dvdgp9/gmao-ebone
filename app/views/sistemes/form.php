<?php
$title = $sistema ? 'Editar Sistema' : 'Nou Sistema';
$action = $sistema ? url('sistemes/update/' . $sistema['id']) : url('sistemes/store');
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('sistemes') ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tornar a sistemes
    </a>
    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mt-2"><?= e($title) ?></h2>
</div>

<form method="POST" action="<?= $action ?>" class="space-y-6">
    <?= csrf_field() ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sigla <span class="text-red-500">*</span></label>
                <input type="text" name="codi" value="<?= e($sistema['codi'] ?? '') ?>" maxlength="20" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono uppercase focus:ring-2 focus:ring-brand focus:border-brand outline-none"
                       placeholder="ACS">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="nom" value="<?= e($sistema['nom'] ?? '') ?>" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none"
                       placeholder="Aigua Calenta Sanitària">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripció</label>
                <textarea name="descripcio" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none" placeholder="Context o aclariments d'ús del sistema"><?= e($sistema['descripcio'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <button type="submit" class="bg-brand text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-dark transition">
            <?= $sistema ? 'Actualitzar sistema' : 'Crear sistema' ?>
        </button>
        <a href="<?= url('sistemes') ?>" class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
            Cancel·lar
        </a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

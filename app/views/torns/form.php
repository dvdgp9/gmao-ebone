<?php
$title = $torn ? 'Editar Torn' : 'Nou Torn';
$action = $torn ? url('torns/update/' . $torn['id']) : url('torns/store');
$dies = $torn ? (json_decode($torn['dies_setmana'] ?? '[]', true) ?: []) : [];
$diesOptions = ['dll' => 'Dilluns', 'dm' => 'Dimarts', 'dx' => 'Dimecres', 'dj' => 'Dijous', 'dv' => 'Divendres', 'ds' => 'Dissabte', 'dg' => 'Diumenge'];
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('torns') ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tornar a torns
    </a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2"><?= e($title) ?></h2>
</div>

<form method="POST" action="<?= $action ?>" class="space-y-6">
    <?= csrf_field() ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="nom" value="<?= e($torn['nom'] ?? '') ?>" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hora inici</label>
                <input type="time" name="hora_inici" value="<?= e($torn['hora_inici'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hora fi</label>
                <input type="time" name="hora_fi" value="<?= e($torn['hora_fi'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 pb-2">
                    <input type="checkbox" name="actiu" value="1" <?= ($torn['actiu'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand">
                    <span class="text-sm text-gray-700">Torn actiu</span>
                </label>
            </div>
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Dies de la setmana</label>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($diesOptions as $key => $label): ?>
                <label class="flex items-center gap-2 bg-gray-50 rounded-lg px-4 py-2 cursor-pointer hover:bg-gray-100 transition">
                    <input type="checkbox" name="dies[]" value="<?= $key ?>" <?= in_array($key, $dies) ? 'checked' : '' ?>
                           class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand">
                    <span class="text-sm text-gray-700"><?= $label ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-brand text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-dark transition">
            <?= $torn ? 'Actualitzar' : 'Crear torn' ?>
        </button>
        <a href="<?= url('torns') ?>" class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
            Cancel·lar
        </a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

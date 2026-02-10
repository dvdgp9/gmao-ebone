<?php
$title = $tasca ? 'Editar Tasca' : 'Nova Tasca';
$action = $tasca ? url('tasques-cataleg/update/' . $tasca['id']) : url('tasques-cataleg/store');
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('tasques-cataleg') ?>" class="text-sm text-gray-500 hover:text-blue-600 transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tornar al catàleg
    </a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2"><?= e($title) ?></h2>
</div>

<form method="POST" action="<?= $action ?>" class="space-y-6">
    <?= csrf_field() ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Informació de la tasca</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Codi</label>
                <input type="text" name="codi" value="<?= e($tasca['codi'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sistema</label>
                <select name="sistema_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($sistemes as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($tasca['sistema_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['codi']) ?> — <?= e($s['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipus equip</label>
                <select name="tipus_equip_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($tipusEquip as $te): ?>
                        <option value="<?= $te['id'] ?>" <?= ($tasca['tipus_equip_id'] ?? '') == $te['id'] ? 'selected' : '' ?>>
                            <?= e($te['codi']) ?> — <?= e($te['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la tasca <span class="text-red-500">*</span></label>
            <input type="text" name="nom" value="<?= e($tasca['nom'] ?? '') ?>" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Descripció</label>
            <textarea name="descripcio" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"><?= e($tasca['descripcio'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Normativa i periodicitat</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Periodicitat normativa</label>
                <select name="periodicitat_normativa_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($periodicitats as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($tasca['periodicitat_normativa_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Normativa</label>
                <select name="normativa_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($normatives as $n): ?>
                        <option value="<?= $n['id'] ?>" <?= ($tasca['normativa_id'] ?? '') == $n['id'] ? 'selected' : '' ?>>
                            <?= e($n['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Empresa / Responsable</label>
                <input type="text" name="empresa_responsable" value="<?= e($tasca['empresa_responsable'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
            <?= $tasca ? 'Actualitzar' : 'Crear tasca' ?>
        </button>
        <a href="<?= url('tasques-cataleg') ?>" class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
            Cancel·lar
        </a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

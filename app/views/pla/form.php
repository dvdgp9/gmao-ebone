<?php
$title = $tasca ? 'Editar Tasca del Pla' : 'Afegir Tasca al Pla';
$action = $tasca ? url('pla/update/' . $tasca['id']) : url('pla/store');
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('pla') ?>" class="text-sm text-gray-500 hover:text-blue-600 transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tornar al pla
    </a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2"><?= e($title) ?></h2>
</div>

<form method="POST" action="<?= $action ?>" class="space-y-6">
    <?= csrf_field() ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Tasca i assignació</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tasca del catàleg <span class="text-red-500">*</span></label>
                <select name="tasca_cataleg_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">— Selecciona una tasca —</option>
                    <?php foreach ($cataleg as $tc): ?>
                        <option value="<?= $tc['id'] ?>" <?= ($tasca['tasca_cataleg_id'] ?? '') == $tc['id'] ? 'selected' : '' ?>>
                            <?= e(($tc['codi'] ? $tc['codi'] . ' — ' : '') . $tc['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Equip</label>
                <select name="equip_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">— Cap equip —</option>
                    <?php foreach ($equips as $eq): ?>
                        <option value="<?= $eq['id'] ?>" <?= ($tasca['equip_id'] ?? '') == $eq['id'] ? 'selected' : '' ?>>
                            <?= e($eq['nom_mn'] . ' — ' . $eq['nom_equip']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Espai</label>
                <select name="espai_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($espais as $esp): ?>
                        <option value="<?= $esp['id'] ?>" <?= ($tasca['espai_id'] ?? '') == $esp['id'] ? 'selected' : '' ?>>
                            <?= e($esp['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Torn</label>
                <select name="torn_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($torns as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= ($tasca['torn_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                            <?= e($t['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Periodicitat i dates</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Periodicitat</label>
                <select name="periodicitat_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($periodicitats as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($tasca['periodicitat_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['nom']) ?> (<?= $p['dies_interval'] ?> dies)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
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
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data darrera realització</label>
                <input type="date" name="data_darrera_realitzacio" value="<?= e($tasca['data_darrera_realitzacio'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data propera realització</label>
                <input type="date" name="data_propera_realitzacio" value="<?= e($tasca['data_propera_realitzacio'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                <p class="text-xs text-gray-400 mt-1">Es recalcula automàticament si hi ha periodicitat i data darrera.</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Observacions</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Observacions</label>
                <textarea name="observacions" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"><?= e($tasca['observacions'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Comentaris de manteniment</label>
                <textarea name="comentaris" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"><?= e($tasca['comentaris'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="en_curs" value="1" <?= ($tasca['en_curs'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Tasca en curs (activa)</span>
                </label>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
            <?= $tasca ? 'Actualitzar' : 'Afegir al pla' ?>
        </button>
        <a href="<?= url('pla') ?>" class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
            Cancel·lar
        </a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

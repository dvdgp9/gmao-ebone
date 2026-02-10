<?php
$title = $equip ? 'Editar Equip' : 'Nou Equip';
$action = $equip ? url('equips/update/' . $equip['id']) : url('equips/store');
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('equips') ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tornar a equips
    </a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2"><?= e($title) ?></h2>
</div>

<form method="POST" action="<?= $action ?>" class="space-y-6">
    <?= csrf_field() ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Identificació</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sistema</label>
                <select name="sistema_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($sistemes as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($equip['sistema_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['codi']) ?> — <?= e($s['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipus equip</label>
                <select name="tipus_equip_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($tipusEquip as $te): ?>
                        <option value="<?= $te['id'] ?>" <?= ($equip['tipus_equip_id'] ?? '') == $te['id'] ? 'selected' : '' ?>>
                            <?= e($te['codi']) ?> — <?= e($te['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                <input type="number" name="numero" value="<?= e($equip['numero'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Codi MN <span class="text-red-500">*</span></label>
                <input type="text" name="nom_mn" value="<?= e($equip['nom_mn'] ?? '') ?>" placeholder="ACS-CAL-1"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom equip <span class="text-red-500">*</span></label>
                <input type="text" name="nom_equip" value="<?= e($equip['nom_equip'] ?? '') ?>" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Detalls tècnics</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                <input type="text" name="model" value="<?= e($equip['model'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dóna servei a</label>
                <input type="text" name="dona_servei_a" value="<?= e($equip['dona_servei_a'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Equipament</label>
                <input type="text" name="equipament" value="<?= e($equip['equipament'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Empresa mantenedora</label>
                <input type="text" name="empresa_mantenedora" value="<?= e($equip['empresa_mantenedora'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none"><?= e($equip['notes'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ubicació i estat</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Espai</label>
                <select name="espai_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($espais as $esp): ?>
                        <option value="<?= $esp['id'] ?>" <?= ($equip['espai_id'] ?? '') == $esp['id'] ? 'selected' : '' ?>>
                            <?= e($esp['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Planta</label>
                <input type="text" name="planta" value="<?= e($equip['planta'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estat</label>
                <select name="estat_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($estats as $est): ?>
                        <option value="<?= $est['id'] ?>" <?= ($equip['estat_id'] ?? '') == $est['id'] ? 'selected' : '' ?>>
                            <?= e($est['nom']) ?> — <?= e($est['descripcio'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data instal·lació</label>
                <input type="date" name="data_installacio" value="<?= e($equip['data_installacio'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fi garantia</label>
                <input type="date" name="fi_garantia" value="<?= e($equip['fi_garantia'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-brand text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-dark transition">
            <?= $equip ? 'Actualitzar' : 'Crear equip' ?>
        </button>
        <a href="<?= url('equips') ?>" class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
            Cancel·lar
        </a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

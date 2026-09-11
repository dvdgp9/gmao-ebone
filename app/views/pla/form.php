<?php
$isEdit = !empty($tasca);
$origen = $origen ?? 'pla';
$title = $isEdit ? 'Editar tasca' : 'Nova tasca';
$action = $isEdit ? url('pla/update/' . $tasca['id']) : url('pla/store');
$backUrl = $origen === 'cataleg' && !$isEdit ? url('tasques-cataleg') : url('pla');
$backLabel = $origen === 'cataleg' && !$isEdit ? 'Tornar al repositori' : 'Tornar al pla';
$selectedCatalog = $selectedCatalog ?? null;
$preselectedCatalogId = (int)($preselectedCatalogId ?? ($tasca['tasca_cataleg_id'] ?? 0));
$selectedTornIds = array_values(array_map('intval', $selectedTornIds ?? []));
$availableTornIds = array_values(array_map(static fn(array $t): string => (string)$t['id'], $torns));

$catalogOptions = [];
foreach ($cataleg as $item) {
    $catalogOptions[(string)$item['id']] = [
        'codi' => $item['codi'] ?? '',
        'sistema_id' => (string)($item['sistema_id'] ?? ''),
        'tipus_equip_id' => (string)($item['tipus_equip_id'] ?? ''),
        'nom' => $item['nom'] ?? '',
        'descripcio' => $item['descripcio'] ?? '',
        'periodicitat_normativa_id' => (string)($item['periodicitat_normativa_id'] ?? ''),
        'normativa_id' => (string)($item['normativa_id'] ?? ''),
        'empresa_responsable' => $item['empresa_responsable'] ?? '',
    ];
}
if ($selectedCatalog && !isset($catalogOptions[(string)$selectedCatalog['id']])) {
    $catalogOptions[(string)$selectedCatalog['id']] = [
        'codi' => $selectedCatalog['codi'] ?? '',
        'sistema_id' => (string)($selectedCatalog['sistema_id'] ?? ''),
        'tipus_equip_id' => (string)($selectedCatalog['tipus_equip_id'] ?? ''),
        'nom' => $selectedCatalog['nom'] ?? '',
        'descripcio' => $selectedCatalog['descripcio'] ?? '',
        'periodicitat_normativa_id' => (string)($selectedCatalog['periodicitat_normativa_id'] ?? ''),
        'normativa_id' => (string)($selectedCatalog['normativa_id'] ?? ''),
        'empresa_responsable' => $selectedCatalog['empresa_responsable'] ?? '',
    ];
}
$blankTask = [
    'codi' => '', 'sistema_id' => '', 'tipus_equip_id' => '', 'nom' => '',
    'descripcio' => '', 'periodicitat_normativa_id' => '', 'normativa_id' => '',
    'empresa_responsable' => '',
];
$initialTask = $selectedCatalog ? $catalogOptions[(string)$selectedCatalog['id']] : $blankTask;
if ($isEdit && !empty($tasca['codi'])) {
    $initialTask['codi'] = $tasca['codi'];
}
$formState = [
    'addToPlan' => $isEdit ? true : ($afegirAlPla ?? $origen !== 'cataleg'),
    'catalogId' => $preselectedCatalogId ? (string)$preselectedCatalogId : '',
    'catalogs' => $catalogOptions,
    'blankTask' => $blankTask,
    'task' => $initialTask,
    'tornIds' => array_map('strval', $selectedTornIds),
    'availableTornIds' => $availableTornIds,
];
ob_start();
?>

<div class="mb-6">
    <a href="<?= $backUrl ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <?= e($backLabel) ?>
    </a>
    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mt-2"><?= e($title) ?></h2>
    <p class="text-sm text-gray-500 mt-1">Una sola fitxa per al repositori i per al pla de manteniment.</p>
</div>

<form method="POST" action="<?= $action ?>" class="space-y-6"
      x-data='<?= htmlspecialchars(json_encode($formState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>'>
    <?= csrf_field() ?>
    <input type="hidden" name="origen" value="<?= e($origen) ?>">

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
        <div class="flex flex-col gap-1 mb-5">
            <h3 class="text-lg font-semibold text-gray-800">Informació de la tasca</h3>
            <p class="text-sm text-gray-500">Crea una tasca nova o recupera'n una del repositori.</p>
        </div>

        <?php if (!$isEdit): ?>
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Tasca existent del repositori</label>
            <select name="tasca_cataleg_id" x-model="catalogId"
                    @change="task = catalogId && catalogs[catalogId] ? {...catalogs[catalogId]} : {...blankTask}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                <option value="">— Crear una tasca nova —</option>
                <?php foreach ($cataleg as $item): ?>
                    <option value="<?= (int)$item['id'] ?>"><?= e(($item['codi'] ? $item['codi'] . ' — ' : '') . $item['nom']) ?></option>
                <?php endforeach; ?>
                <?php if ($selectedCatalog && !array_filter($cataleg, static fn(array $item): bool => (int)$item['id'] === (int)$selectedCatalog['id'])): ?>
                    <option value="<?= (int)$selectedCatalog['id'] ?>"><?= e(($selectedCatalog['codi'] ? $selectedCatalog['codi'] . ' — ' : '') . $selectedCatalog['nom']) ?></option>
                <?php endif; ?>
            </select>
            <p class="text-xs text-gray-400 mt-1">En seleccionar-ne una, pots revisar les dades abans de tornar-la a programar.</p>
        </div>
        <?php else: ?>
            <input type="hidden" name="tasca_cataleg_id" value="<?= $preselectedCatalogId ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Codi</label>
                <input type="text" name="codi" maxlength="50" x-model="task.codi" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sistema</label>
                <select name="sistema_id" x-model="task.sistema_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($sistemes as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e($s['codi']) ?> — <?= e($s['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipus equip</label>
                <select name="tipus_equip_id" x-model="task.tipus_equip_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($tipusEquip as $te): ?>
                        <option value="<?= (int)$te['id'] ?>"><?= e($te['codi']) ?> — <?= e($te['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la tasca <span class="text-red-500">*</span></label>
            <input type="text" name="nom" x-model="task.nom" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Descripció</label>
            <textarea name="descripcio" rows="3" x-model="task.descripcio" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none"></textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Periodicitat normativa</label>
                <select name="periodicitat_normativa_id" x-model="task.periodicitat_normativa_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($periodicitats as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['nom']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Normativa</label>
                <select name="normativa_id" x-model="task.normativa_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($normatives as $n): ?><option value="<?= (int)$n['id'] ?>"><?= e($n['nom']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Empresa / Responsable</label>
                <input type="text" name="empresa_responsable" x-model="task.empresa_responsable" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
        </div>
    </div>

    <?php if (!$isEdit): ?>
    <label class="flex items-start gap-3 bg-brand-light/60 border border-brand/20 rounded-xl px-4 py-4 cursor-pointer active:scale-[0.99] transition-transform">
        <input type="checkbox" name="afegir_al_pla" value="1" x-model="addToPlan" <?= ($afegirAlPla ?? $origen !== 'cataleg') ? 'checked' : '' ?> class="w-4 h-4 mt-0.5 text-brand border-gray-300 rounded focus:ring-brand">
        <span>
            <span class="block text-sm font-semibold text-gray-800">Afegir al pla de manteniment</span>
            <span class="block text-xs text-gray-500 mt-0.5">Activa la programació, l'espai, l'equip i els torns. Si no la marques, la tasca queda disponible al repositori.</span>
        </span>
    </label>
    <?php endif; ?>

    <div x-show="addToPlan" x-collapse class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Assignació al pla</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Equip</label>
                    <select name="equip_id" :disabled="!addToPlan" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                        <option value="">— Cap equip —</option>
                        <?php foreach ($equips as $eq): ?><option value="<?= (int)$eq['id'] ?>" <?= ($tasca['equip_id'] ?? '') == $eq['id'] ? 'selected' : '' ?>><?= e($eq['nom_mn'] . ' — ' . $eq['nom_equip']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Espai</label>
                    <select name="espai_id" :disabled="!addToPlan" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                        <option value="">— Selecciona —</option>
                        <?php foreach ($espais as $esp): ?><option value="<?= (int)$esp['id'] ?>" <?= ($tasca['espai_id'] ?? '') == $esp['id'] ? 'selected' : '' ?>><?= e($esp['nom']) ?><?= empty($esp['actiu']) ? ' (inactiu)' : '' ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <div class="flex flex-wrap items-end justify-between gap-2 mb-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Torns</label>
                            <p class="text-xs text-gray-400 mt-0.5">Una única execució compartida entre tots els torns seleccionats.</p>
                        </div>
                        <?php if (!empty($torns)): ?>
                        <label class="inline-flex items-center gap-2 rounded-lg border border-brand/25 bg-brand-light/50 px-3 py-2 text-sm font-medium text-brand-dark cursor-pointer active:scale-[0.98] transition">
                            <input type="checkbox"
                                   :checked="availableTornIds.length > 0 && availableTornIds.every(id => tornIds.includes(id))"
                                   @change="tornIds = $event.target.checked ? [...availableTornIds] : []"
                                   :disabled="!addToPlan"
                                   class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand">
                            Tots els torns
                        </label>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($torns)): ?>
                        <div class="rounded-lg border border-dashed border-gray-300 px-4 py-3 text-sm text-gray-400">No hi ha torns configurats. La tasca quedarà sense torn assignat.</div>
                    <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 rounded-xl border border-gray-200 bg-gray-50/70 p-3">
                        <?php foreach ($torns as $t): ?>
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 cursor-pointer hover:border-brand/40 active:scale-[0.98] transition">
                            <input type="checkbox" name="torn_ids[]" value="<?= (int)$t['id'] ?>" x-model="tornIds" :disabled="!addToPlan"
                                   <?= in_array((int)$t['id'], $selectedTornIds, true) ? 'checked' : '' ?>
                                   class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand">
                            <span><?= e($t['nom']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Periodicitat i dates</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Periodicitat del pla</label>
                    <select name="periodicitat_id" :disabled="!addToPlan" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                        <option value="">— Selecciona —</option>
                        <?php foreach ($periodicitats as $p): ?><option value="<?= (int)$p['id'] ?>" <?= ($tasca['periodicitat_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= e($p['nom']) ?> (<?= (int)$p['dies_interval'] ?> dies)</option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data darrera realització</label>
                    <input type="date" name="data_darrera_realitzacio" :disabled="!addToPlan" value="<?= e($tasca['data_darrera_realitzacio'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data propera realització</label>
                    <input type="date" name="data_propera_realitzacio" :disabled="!addToPlan" value="<?= e($tasca['data_propera_realitzacio'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                    <p class="text-xs text-gray-400 mt-1">Es recalcula si hi ha periodicitat i data darrera.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Notes de manteniment</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observacions</label>
                    <textarea name="observacions" rows="2" :disabled="!addToPlan" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none"><?= e($tasca['observacions'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Comentaris de manteniment</label>
                    <textarea name="comentaris" rows="2" :disabled="!addToPlan" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none"><?= e($tasca['comentaris'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <button type="submit" class="bg-brand text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-dark active:scale-[0.98] transition"><?= $isEdit ? 'Actualitzar tasca' : 'Crear tasca' ?></button>
        <a href="<?= $backUrl ?>" class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 active:scale-[0.98] transition text-center">Cancel·lar</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

<?php
$title = 'Vista prèvia importació';
$backUrl = !empty($returnTo ?? '') ? url($returnTo) : url('import');
$typeLabels = [
    'tasques_cataleg' => 'Tasques al Catàleg',
    'tasques_pla' => 'Tasques al Pla',
    'pla_rapid' => 'Pla ràpid',
    'completa_instalacio' => 'Importació completa',
];
$isQuickPlan = ($importType ?? '') === 'pla_rapid';
$summary = $importSummary ?? [];
$hasReviewRows = $isQuickPlan && ((int)($summary['review'] ?? 0) > 0);
$hasErrorRows = $isQuickPlan && ((int)($summary['error'] ?? 0) > 0);
ob_start();
?>

<div class="mb-6">
    <a href="<?= $backUrl ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <?= !empty($returnTo ?? '') ? 'Tornar a onboarding' : 'Tornar' ?>
    </a>
    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mt-2">Vista prèvia</h2>
    <p class="text-gray-500 text-sm mt-1">
        Tipus: <span class="font-medium"><?= e($typeLabels[$importType] ?? 'Importació') ?></span>
        — <?= $totalRows ?> files detectades
    </p>
</div>

<?php if ($isQuickPlan): ?>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="rounded-xl border border-green-200 bg-green-50 p-4">
        <div class="text-2xl font-bold text-green-700"><?= (int)($summary['match'] ?? 0) ?></div>
        <div class="text-xs font-medium text-green-800 mt-1">Reutilitzen catàleg</div>
    </div>
    <div class="rounded-xl border border-brand/20 bg-brand-light p-4">
        <div class="text-2xl font-bold text-brand-dark"><?= (int)($summary['new'] ?? 0) ?></div>
        <div class="text-xs font-medium text-brand-dark mt-1">Creen tasca nova</div>
    </div>
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
        <div class="text-2xl font-bold text-amber-700"><?= (int)($summary['review'] ?? 0) ?></div>
        <div class="text-xs font-medium text-amber-800 mt-1">Requereixen revisió</div>
    </div>
    <div class="rounded-xl border border-red-200 bg-red-50 p-4">
        <div class="text-2xl font-bold text-red-700"><?= (int)($summary['error'] ?? 0) ?></div>
        <div class="text-xs font-medium text-red-800 mt-1">Errors</div>
    </div>
</div>

<?php if ($hasErrorRows): ?>
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Hi ha files amb errors. Per seguretat, no es pot importar fins que el fitxer quedi corregit.
    </div>
<?php elseif ($hasReviewRows): ?>
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Hi ha coincidències dubtoses. Decideix en cada fila si vols reutilitzar la tasca proposada, crear-ne una de nova o saltar-la.
    </div>
<?php else: ?>
    <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
        No hi ha coincidències dubtoses. Si confirmes, es crearà el pla de la instal·lació activa amb les accions indicades.
    </div>
<?php endif; ?>

<div class="space-y-3 md:hidden mb-6">
    <?php foreach (($quickPreview ?? []) as $row): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="text-xs font-semibold text-gray-400">Fila <?= (int)$row['row'] ?></div>
                <div class="text-sm font-semibold text-gray-800 mt-1 break-words"><?= e($row['task_name']) ?></div>
            </div>
            <span class="import-action-pill import-action-pill--<?= e($row['status']) ?>"><?= e($row['action_label']) ?></span>
        </div>
        <div class="mt-3 grid grid-cols-1 gap-2 text-xs text-gray-600">
            <div><span class="text-gray-400">Periodicitat:</span> <?= e($row['periodicitat_label']) ?></div>
            <?php if (!empty($row['matched_task_name'])): ?>
                <div><span class="text-gray-400">Coincidència:</span> <?= e($row['matched_task_name']) ?> (<?= (int)$row['score'] ?>%)</div>
            <?php endif; ?>
            <div class="<?= $row['status'] === 'review' || $row['status'] === 'error' ? 'text-amber-700' : 'text-gray-500' ?>"><?= e($row['message']) ?></div>
            <?php if ($row['status'] === 'review'): ?>
                <label class="block">
                    <span class="block text-gray-500 mb-1">Decisió obligatòria</span>
                    <select name="quick_resolution_mobile[<?= (int)$row['row'] ?>]" form="quickImportForm" class="w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs text-gray-700 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100">
                        <option value="">Selecciona una acció</option>
                        <option value="use_match">Usar la coincidència proposada</option>
                        <option value="create_new">Crear una tasca nova</option>
                        <option value="skip">Saltar aquesta fila</option>
                    </select>
                </label>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 font-medium text-gray-400 text-xs">Fila</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase">Tasca detectada</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase">Periodicitat</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase">Acció</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase">Detall</th>
                    <?php if ($hasReviewRows): ?>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase">Decisió</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach (($quickPreview ?? []) as $row): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-xs text-gray-400"><?= (int)$row['row'] ?></td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-800 max-w-sm break-words"><?= e($row['task_name']) ?></div>
                        <?php if (!empty($row['normativa_label']) || !empty($row['sistema_label'])): ?>
                            <div class="text-xs text-gray-400 mt-1"><?= e(trim(($row['normativa_label'] ?? '') . ' ' . ($row['sistema_label'] ?? ''))) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600"><?= e($row['periodicitat_label']) ?></td>
                    <td class="px-4 py-3"><span class="import-action-pill import-action-pill--<?= e($row['status']) ?>"><?= e($row['action_label']) ?></span></td>
                    <td class="px-4 py-3 text-xs text-gray-500 max-w-md">
                        <?php if (!empty($row['matched_task_name'])): ?>
                            <div class="font-medium text-gray-700"><?= e($row['matched_task_name']) ?> · <?= (int)$row['score'] ?>%</div>
                        <?php endif; ?>
                        <div class="<?= $row['status'] === 'review' || $row['status'] === 'error' ? 'text-amber-700' : '' ?>"><?= e($row['message']) ?></div>
                    </td>
                    <?php if ($hasReviewRows): ?>
                        <td class="px-4 py-3 min-w-64">
                            <?php if ($row['status'] === 'review'): ?>
                                <label class="sr-only" for="quick-resolution-<?= (int)$row['row'] ?>">Decisió fila <?= (int)$row['row'] ?></label>
                                <select id="quick-resolution-<?= (int)$row['row'] ?>" name="quick_resolution[<?= (int)$row['row'] ?>]" form="quickImportForm" class="w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs text-gray-700 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100">
                                    <option value="">Selecciona</option>
                                    <option value="use_match">Usar coincidència proposada</option>
                                    <option value="create_new">Crear tasca nova</option>
                                    <option value="skip">Saltar fila</option>
                                </select>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">No cal decisió</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="space-y-3 md:hidden mb-6">
    <?php foreach ($preview as $i => $row): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="text-xs font-semibold text-gray-500 mb-3">Fila <?= $i + 1 ?></div>
        <div class="space-y-2 text-xs">
            <?php foreach ($headers as $col => $h): ?>
            <div>
                <div class="text-gray-400"><?= e($h ?: "Col {$col}") ?></div>
                <div class="text-gray-700 mt-0.5 break-words"><?= e($row[$col] ?? '') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 font-medium text-gray-400 text-xs">#</th>
                    <?php foreach ($headers as $col => $h): ?>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 text-xs uppercase"><?= e($h ?: "Col {$col}") ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($preview as $i => $row): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-xs text-gray-400"><?= $i + 1 ?></td>
                    <?php foreach ($headers as $col => $h): ?>
                        <td class="px-4 py-2 text-xs max-w-xs truncate"><?= e($row[$col] ?? '') ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalRows > count($preview)): ?>
    <div class="px-4 py-2 border-t border-gray-200 text-xs text-gray-400">
        Mostrant <?= count($preview) ?> de <?= $totalRows ?> files
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<form id="quickImportForm" method="POST" action="<?= url('import/process') ?>" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
    <?= csrf_field() ?>
    <?php if (!empty($returnTo ?? '')): ?>
        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
    <?php endif; ?>
    <?php if ($hasErrorRows): ?>
        <button type="button" disabled class="cursor-not-allowed bg-gray-200 text-gray-500 px-6 py-2.5 rounded-lg text-sm font-medium">
            Corregeix els errors abans d'importar
        </button>
    <?php else: ?>
        <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 active:scale-[0.98] transition"
                onclick="return confirm('Segur que vols importar <?= $totalRows ?> registres?')">
            Confirmar importació (<?= $totalRows ?> registres)
        </button>
    <?php endif; ?>
    <a href="<?= $backUrl ?>" class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
        Cancel·lar
    </a>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

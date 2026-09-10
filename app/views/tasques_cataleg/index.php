<?php
$title = 'Repositori de Tasques';
$canManagePlan = !empty($_SESSION['is_superadmin']) || in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio', 'cap_manteniment']);
$canManageCatalog = !empty($_SESSION['is_superadmin']) || in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio']);
ob_start();
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Repositori de Tasques</h2>
        <p class="text-gray-500 text-sm mt-1">Històric de tasques disponibles per recuperar o tornar a activar</p>
    </div>
    <?php if ($canManagePlan): ?>
    <a href="<?= url('pla/create?origen=cataleg') ?>" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark active:scale-[0.98] transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nova tasca
    </a>
    <?php endif; ?>
</div>

<!-- Buscador -->
<div class="mb-4">
    <form method="GET" action="<?= url('tasques-cataleg') ?>" class="flex flex-col sm:flex-row gap-2">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cercar per nom, codi o sistema..."
               class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
        <button type="submit" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Cercar</button>
        <?php if ($search): ?>
            <a href="<?= url('tasques-cataleg') ?>" class="bg-gray-100 text-gray-500 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Netejar</a>
        <?php endif; ?>
    </form>
</div>

<div class="space-y-4 md:hidden">
    <?php if (empty($tasques)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-4 py-8 text-center text-gray-400">No hi ha tasques al repositori.</div>
    <?php else: ?>
        <?php foreach ($tasques as $tasca): ?>
        <?php
            $activePlanCount = (int)$tasca['active_plan_count'];
            $inactivePlanCount = (int)$tasca['inactive_plan_count'];
            $statusLabel = $activePlanCount > 0
                ? ($activePlanCount . ' activa' . ($activePlanCount > 1 ? 's' : '') . ($inactivePlanCount > 0 ? ' · ' . $inactivePlanCount . ' desactivada' . ($inactivePlanCount > 1 ? 's' : '') : ''))
                : ($inactivePlanCount > 0 ? 'Desactivada' : (!empty($tasca['activa']) ? 'Disponible' : 'Arxivada'));
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-mono text-xs text-brand"><?= e($tasca['codi'] ?? '-') ?></div>
                    <h3 class="text-sm font-semibold text-gray-800 mt-1"><?= e($tasca['nom']) ?></h3>
                </div>
                <?php if (!empty($tasca['sistema_codi'])): ?>
                    <span class="inline-block bg-brand-light text-brand-dark text-xs px-2 py-0.5 rounded whitespace-nowrap"><?= e($tasca['sistema_codi']) ?></span>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-4 text-xs">
                <div>
                    <div class="text-gray-400">Periodicitat</div>
                    <div class="text-gray-700 mt-0.5"><?= e($tasca['periodicitat_nom'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="text-gray-400">Normativa</div>
                    <div class="text-gray-700 mt-0.5"><?= e($tasca['normativa_nom'] ?? '-') ?></div>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 mt-4 pt-3 border-t border-gray-100">
                <span class="text-xs font-medium <?= $activePlanCount > 0 ? 'text-emerald-700' : 'text-gray-500' ?>">
                    <?= e($statusLabel) ?>
                </span>
                <div class="flex items-center gap-3">
                <?php if ($canManagePlan): ?>
                    <?php if (!empty($tasca['reactivable_plan_id'])): ?>
                    <form method="POST" action="<?= url('pla/reactivate/' . (int)$tasca['reactivable_plan_id']) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="text-sm font-medium text-brand hover:text-brand-dark transition">Reactivar</button>
                    </form>
                    <?php elseif ($activePlanCount === 0): ?>
                    <a href="<?= url('pla/create?origen=cataleg&afegir=1&tasca_cataleg_id=' . (int)$tasca['id']) ?>" class="text-sm font-medium text-brand hover:text-brand-dark transition">Afegir al pla</a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($canManageCatalog): ?>
                <a href="<?= url('tasques-cataleg/edit/' . $tasca['id']) ?>" class="text-sm text-brand hover:text-brand-dark transition">Editar</a>
                <?php if (!empty($tasca['activa'])): ?>
                <?php if ($activePlanCount === 0): ?>
                <form method="POST" action="<?= url('tasques-cataleg/delete/' . $tasca['id']) ?>" onsubmit="return confirm('Segur que vols arxivar aquesta tasca?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-sm text-gray-500 hover:text-red-700 transition">Arxivar</button>
                </form>
                <?php endif; ?>
                <?php else: ?>
                <form method="POST" action="<?= url('tasques-cataleg/activate/' . $tasca['id']) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-sm text-gray-600 hover:text-brand transition">Recuperar</button>
                </form>
                <?php endif; ?>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Codi</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Tasca</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Sistema</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Periodicitat</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Normativa</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Estat</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Accions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($tasques)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No hi ha tasques al repositori.</td></tr>
                <?php else: ?>
                    <?php foreach ($tasques as $tasca): ?>
                    <?php
                        $activePlanCount = (int)$tasca['active_plan_count'];
                        $inactivePlanCount = (int)$tasca['inactive_plan_count'];
                        $statusLabel = $activePlanCount > 0
                            ? ($activePlanCount . ' activa' . ($activePlanCount > 1 ? 's' : '') . ($inactivePlanCount > 0 ? ' · ' . $inactivePlanCount . ' desactivada' . ($inactivePlanCount > 1 ? 's' : '') : ''))
                            : ($inactivePlanCount > 0 ? 'Desactivada' : (!empty($tasca['activa']) ? 'Disponible' : 'Arxivada'));
                    ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-mono text-xs text-brand"><?= e($tasca['codi'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <div class="max-w-md truncate" title="<?= e($tasca['nom']) ?>"><?= e($tasca['nom']) ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (!empty($tasca['sistema_codi'])): ?>
                                <span class="inline-block bg-brand-light text-brand-dark text-xs px-2 py-0.5 rounded"><?= e($tasca['sistema_codi']) ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs"><?= e($tasca['periodicitat_nom'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-500 text-xs max-w-xs truncate"><?= e($tasca['normativa_nom'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-block text-xs px-2 py-0.5 rounded <?= $activePlanCount > 0 ? 'bg-emerald-50 text-emerald-700' : (!empty($tasca['reactivable_plan_id']) ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600') ?>">
                                <?= e($statusLabel) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <?php if ($canManagePlan): ?>
                                    <?php if (!empty($tasca['reactivable_plan_id'])): ?>
                                    <form method="POST" action="<?= url('pla/reactivate/' . (int)$tasca['reactivable_plan_id']) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="text-xs font-medium text-brand hover:text-brand-dark transition">Reactivar</button>
                                    </form>
                                    <?php elseif ($activePlanCount === 0): ?>
                                    <a href="<?= url('pla/create?origen=cataleg&afegir=1&tasca_cataleg_id=' . (int)$tasca['id']) ?>" class="text-xs font-medium text-brand hover:text-brand-dark transition">Afegir al pla</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($canManageCatalog): ?>
                                <a href="<?= url('tasques-cataleg/edit/' . $tasca['id']) ?>" class="text-gray-400 hover:text-brand transition" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <?php if (!empty($tasca['activa'])): ?>
                                <?php if ($activePlanCount === 0): ?>
                                <form method="POST" action="<?= url('tasques-cataleg/delete/' . $tasca['id']) ?>" onsubmit="return confirm('Segur que vols arxivar aquesta tasca?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-gray-400 hover:text-red-600 transition" title="Arxivar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php else: ?>
                                <form method="POST" action="<?= url('tasques-cataleg/activate/' . $tasca['id']) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-gray-400 hover:text-brand transition" title="Recuperar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-gray-200 text-sm text-gray-500">
        <?= count($tasques) ?> tasques al repositori
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

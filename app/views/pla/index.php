<?php
$title = 'Pla de Manteniment';
ob_start();
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Pla de Manteniment</h2>
        <p class="text-gray-500 text-sm mt-1">Tasques assignades a la instal·lació</p>
    </div>
    <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio', 'cap_manteniment'])): ?>
    <a href="<?= url('pla/create') ?>" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Afegir Tasca
    </a>
    <?php endif; ?>
</div>

<div class="mb-3 max-w-full overflow-x-hidden">
    <form method="GET" action="<?= url('pla') ?>" class="flex flex-col sm:flex-row gap-2">
        <input
            type="text"
            name="q"
            value="<?= e($search ?? '') ?>"
            placeholder="Cercar per codi, tasca, equip, espai o torn..."
            class="w-full min-w-0 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none"
        >
        <button type="submit" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Filtrar</button>
        <?php if (!empty($search)): ?>
            <a href="<?= url('pla') ?>" class="bg-gray-100 text-gray-500 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition text-center">Netejar</a>
        <?php endif; ?>
    </form>
</div>

<?php $limitMobile = 15; ?>
<!-- Mobile: cards compactes amb expand + mostrar més -->
<div class="md:hidden w-full max-w-full overflow-x-hidden" x-data="{ showAll: false, expanded: {} }">
    <?php if (empty($tasques)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-4 py-8 text-center text-gray-400">No hi ha tasques al pla de manteniment.</div>
    <?php else: ?>
        <div class="space-y-2 w-full max-w-full">
        <?php foreach ($tasques as $idx => $t): ?>
        <?php
            $vencuda = $t['data_propera_realitzacio'] && $t['data_propera_realitzacio'] < date('Y-m-d');
            $avui = $t['data_propera_realitzacio'] === date('Y-m-d');
        ?>
        <div x-show="showAll || <?= $idx ?> < <?= $limitMobile ?>" 
             class="w-full max-w-full overflow-hidden bg-white rounded-lg shadow-sm border px-3 py-2.5 <?= $vencuda ? 'border-red-200 bg-red-50/60' : ($avui ? 'border-yellow-200 bg-yellow-50/70' : 'border-gray-200') ?>">
            <div class="min-w-0">
                <div class="flex items-start gap-2 min-w-0">
                    <span class="font-mono text-[11px] text-brand shrink-0 pt-0.5"><?= e($t['tasca_codi'] ?? '-') ?></span>
                    <span class="text-sm leading-5 text-gray-800 break-words min-w-0"><?= e($t['tasca_nom']) ?></span>
                </div>
                <div class="flex flex-wrap items-center gap-1 mt-2">
                    <?php if ($vencuda): ?>
                        <span class="bg-red-100 text-red-600 text-[10px] px-1.5 py-0.5 rounded font-medium">Vençuda</span>
                    <?php elseif ($avui): ?>
                        <span class="bg-yellow-100 text-yellow-700 text-[10px] px-1.5 py-0.5 rounded font-medium">Avui</span>
                    <?php endif; ?>
                    <?php if (($t['espai_id'] ?? null) && isset($t['espai_actiu']) && (int)$t['espai_actiu'] === 0): ?>
                        <span class="bg-gray-100 text-gray-500 text-[10px] px-1.5 py-0.5 rounded font-medium">Espai inactiu</span>
                    <?php endif; ?>
                    <?php if ($t['torn_nom']): ?>
                        <span class="bg-purple-50 text-purple-700 text-[10px] px-1.5 py-0.5 rounded"><?= e($t['torn_nom']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-start justify-between gap-2 mt-2 text-xs text-gray-500">
                <span class="min-w-0 break-words leading-4">
                    <?= e($t['espai_nom'] ?? '-') ?>
                    <?php if (($t['espai_id'] ?? null) && isset($t['espai_actiu']) && (int)$t['espai_actiu'] === 0): ?>
                        <span class="text-gray-400">(inactiu)</span>
                    <?php endif; ?>
                    <?= $t['equip_nom'] ? ' · ' . e($t['equip_nom']) : '' ?>
                </span>
                <button type="button" @click="expanded[<?= $t['id'] ?>] = !expanded[<?= $t['id'] ?>]" class="text-brand hover:text-brand-dark text-[11px] ml-2 shrink-0 whitespace-nowrap">
                    <span x-show="!expanded[<?= $t['id'] ?>]">+ Detalls</span>
                    <span x-show="expanded[<?= $t['id'] ?>]">- Tancar</span>
                </button>
            </div>
            <div x-show="expanded[<?= $t['id'] ?>]" x-collapse class="mt-3 pt-2 border-t border-gray-100 text-xs space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <span class="text-gray-400">Periodicitat</span>
                    <span class="text-right text-gray-700 break-words"><?= e($t['periodicitat_nom'] ?? '-') ?></span>
                </div>
                <div class="flex items-start justify-between gap-3">
                    <span class="text-gray-400">Darrera</span>
                    <span class="text-right text-gray-700"><?= $t['data_darrera_realitzacio'] ? format_date($t['data_darrera_realitzacio']) : '-' ?></span>
                </div>
                <div class="flex items-start justify-between gap-3">
                    <span class="text-gray-400">Propera</span>
                    <span class="text-right <?= $vencuda ? 'text-red-600 font-medium' : 'text-gray-700' ?>"><?= $t['data_propera_realitzacio'] ? format_date($t['data_propera_realitzacio']) : '-' ?></span>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="<?= url('pla/edit/' . $t['id']) ?>" class="text-sm text-brand hover:text-brand-dark transition">Editar</a>
                    <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
                    <form method="POST" action="<?= url('pla/delete/' . $t['id']) ?>" onsubmit="return confirm('Segur que vols eliminar aquesta tasca del pla?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="text-sm text-red-600 hover:text-red-700 transition">Eliminar</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php if (count($tasques) > $limitMobile): ?>
        <button x-show="!showAll" @click="showAll = true" type="button"
                class="w-full mt-3 bg-white border border-gray-300 text-gray-600 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
            Mostrar <?= count($tasques) - $limitMobile ?> tasques més
        </button>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Codi</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Tasca</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Espai</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Torn</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Periodicitat</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Darrera</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Propera</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Accions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($tasques)): ?>
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No hi ha tasques al pla de manteniment.</td></tr>
                <?php else: ?>
                    <?php foreach ($tasques as $t): ?>
                    <?php
                        $vencuda = $t['data_propera_realitzacio'] && $t['data_propera_realitzacio'] < date('Y-m-d');
                        $avui = $t['data_propera_realitzacio'] === date('Y-m-d');
                    ?>
                    <tr class="hover:bg-gray-50 transition <?= $vencuda ? 'bg-red-50' : ($avui ? 'bg-yellow-50' : '') ?>">
                        <td class="px-4 py-3 font-mono text-xs text-brand"><?= e($t['tasca_codi'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <div class="max-w-xs truncate" title="<?= e($t['tasca_nom']) ?>"><?= e($t['tasca_nom']) ?></div>
                            <?php if ($t['equip_nom']): ?>
                                <div class="text-xs text-gray-400 mt-0.5"><?= e($t['equip_nom']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            <?= e($t['espai_nom'] ?? '-') ?>
                            <?php if (($t['espai_id'] ?? null) && isset($t['espai_actiu']) && (int)$t['espai_actiu'] === 0): ?>
                                <div class="text-[11px] text-gray-400">Espai inactiu</div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($t['torn_nom']): ?>
                                <span class="inline-block bg-purple-50 text-purple-700 text-xs px-2 py-0.5 rounded"><?= e($t['torn_nom']) ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs"><?= e($t['periodicitat_nom'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-xs text-gray-500"><?= $t['data_darrera_realitzacio'] ? format_date($t['data_darrera_realitzacio']) : '-' ?></td>
                        <td class="px-4 py-3 text-xs">
                            <?php if ($t['data_propera_realitzacio']): ?>
                                <span class="<?= $vencuda ? 'text-red-600 font-medium' : ($avui ? 'text-yellow-600 font-medium' : 'text-gray-500') ?>">
                                    <?= format_date($t['data_propera_realitzacio']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?= url('pla/edit/' . $t['id']) ?>" class="text-gray-400 hover:text-brand transition" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
                                <form method="POST" action="<?= url('pla/delete/' . $t['id']) ?>" onsubmit="return confirm('Segur que vols eliminar aquesta tasca del pla?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-gray-400 hover:text-red-600 transition" title="Eliminar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
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
        <?= count($tasques) ?> tasques al pla
        <?php if (!empty($search)): ?>
            <span>per a "<?= e($search) ?>"</span>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

<?php
$title = 'Vista Setmanal';
$diesNom = ['dl.', 'dt.', 'dc.', 'dj.', 'dv.', 'ds.', 'dg.'];
ob_start();
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Vista Setmanal</h2>
    <p class="text-gray-500 text-sm mt-1">Tasques programades per setmana i torn</p>
</div>

<!-- Navegació de setmana + filtre torn -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div class="flex items-center gap-2">
        <a href="<?= url('setmana?setmana=' . ($setmanaOffset - 1) . ($tornActual ? '&torn=' . $tornActual : '')) ?>"
           class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm hover:bg-gray-50 transition">
            &larr; Anterior
        </a>
        <div class="bg-white border border-gray-200 rounded-lg px-4 py-2 text-center">
            <div class="text-sm font-semibold text-gray-800">Setmana <?= $setmanaNum ?></div>
            <div class="text-xs text-gray-500">
                <?= $dilluns->format('d/m/Y') ?> — <?= $diumenge->format('d/m/Y') ?>
            </div>
        </div>
        <a href="<?= url('setmana?setmana=' . ($setmanaOffset + 1) . ($tornActual ? '&torn=' . $tornActual : '')) ?>"
           class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm hover:bg-gray-50 transition">
            Següent &rarr;
        </a>
        <?php if ($setmanaOffset !== 0): ?>
        <a href="<?= url('setmana' . ($tornActual ? '?torn=' . $tornActual : '')) ?>"
           class="text-sm text-blue-600 hover:text-blue-800 transition ml-2">Avui</a>
        <?php endif; ?>
    </div>

    <div class="flex items-center gap-2">
        <span class="text-sm text-gray-500">Torn:</span>
        <a href="<?= url('setmana?setmana=' . $setmanaOffset) ?>"
           class="px-3 py-1.5 text-sm rounded-lg <?= !$tornActual ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' ?> transition">
            Tots
        </a>
        <?php foreach ($torns as $t): ?>
        <a href="<?= url('setmana?setmana=' . $setmanaOffset . '&torn=' . $t['id']) ?>"
           class="px-3 py-1.5 text-sm rounded-lg <?= $tornActual == $t['id'] ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' ?> transition">
            <?= e($t['nom']) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tabla de tasques -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 font-medium text-gray-600 w-16">Codi</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Espai</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Tasca</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 w-20">Periodicitat</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 w-24">Torn</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 w-24">Data propera</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-600 w-20">Acció</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($tasques)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No hi ha tasques programades per a aquesta setmana.</td></tr>
                <?php else: ?>
                    <?php foreach ($tasques as $t): ?>
                    <?php
                        $vencuda = $t['data_propera_realitzacio'] && $t['data_propera_realitzacio'] < date('Y-m-d');
                        $avui = $t['data_propera_realitzacio'] === date('Y-m-d');
                    ?>
                    <tr class="hover:bg-gray-50 transition <?= $vencuda ? 'bg-red-50' : ($avui ? 'bg-yellow-50' : '') ?>">
                        <td class="px-4 py-3 font-mono text-xs text-blue-600"><?= e($t['tasca_codi'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-500 text-xs"><?= e($t['espai_nom'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <div class="max-w-sm truncate" title="<?= e($t['tasca_nom']) ?>"><?= e($t['tasca_nom']) ?></div>
                            <?php if ($t['equip_nom']): ?>
                                <div class="text-xs text-gray-400 mt-0.5"><?= e($t['equip_nom']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs"><?= e($t['periodicitat_nom'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <?php if ($t['torn_nom']): ?>
                                <span class="inline-block bg-purple-50 text-purple-700 text-xs px-2 py-0.5 rounded"><?= e($t['torn_nom']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs <?= $vencuda ? 'text-red-600 font-medium' : 'text-gray-500' ?>">
                            <?= $t['data_propera_realitzacio'] ? format_date($t['data_propera_realitzacio']) : '-' ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio', 'cap_manteniment', 'tecnic'])): ?>
                            <form method="POST" action="<?= url('registre/store') ?>" class="inline-flex items-center gap-1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="tasca_pla_id" value="<?= $t['id'] ?>">
                                <input type="hidden" name="data_execucio" value="<?= date('Y-m-d') ?>">
                                <input type="hidden" name="realitzada" value="1">
                                <input type="hidden" name="redirect" value="setmana?setmana=<?= $setmanaOffset ?><?= $tornActual ? '&torn=' . $tornActual : '' ?>">
                                <button type="submit" class="bg-green-50 text-green-700 hover:bg-green-100 px-2.5 py-1 rounded text-xs font-medium transition"
                                        onclick="return confirm('Marcar com a realitzada?')">
                                    Fet
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-gray-200 text-sm text-gray-500">
        <?= count($tasques) ?> tasques programades
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

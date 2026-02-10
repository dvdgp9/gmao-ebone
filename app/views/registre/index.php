<?php
$title = 'Registre de Tasques';
ob_start();
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Registre de Tasques</h2>
    <p class="text-gray-500 text-sm mt-1">Historial d'execucions de tasques de manteniment</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Data</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Codi</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Tasca</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Espai</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Torn</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Tècnic</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-600">Estat</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Comentaris</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($registres)): ?>
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No hi ha registres d'execucions.</td></tr>
                <?php else: ?>
                    <?php foreach ($registres as $r): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap"><?= format_date($r['data_execucio']) ?></td>
                        <td class="px-4 py-3 font-mono text-xs text-blue-600"><?= e($r['tasca_codi'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <div class="max-w-xs truncate" title="<?= e($r['tasca_nom'] ?? '') ?>"><?= e($r['tasca_nom'] ?? '-') ?></div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs"><?= e($r['espai_nom'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <?php if (!empty($r['torn_nom'])): ?>
                                <span class="inline-block bg-purple-50 text-purple-700 text-xs px-2 py-0.5 rounded"><?= e($r['torn_nom']) ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs"><?= e($r['usuari_nom'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($r['realitzada']): ?>
                                <span class="inline-block bg-green-50 text-green-700 text-xs px-2 py-0.5 rounded">Fet</span>
                            <?php else: ?>
                                <span class="inline-block bg-red-50 text-red-700 text-xs px-2 py-0.5 rounded">No fet</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs max-w-xs truncate"><?= e($r['comentaris'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-500">
            Mostrant <?= count($registres) ?> de <?= $pagination['total'] ?> registres
        </p>
        <div class="flex gap-1">
            <?php if ($pagination['current_page'] > 1): ?>
                <a href="<?= url('registre?page=' . ($pagination['current_page'] - 1)) ?>"
                   class="px-3 py-1 text-sm rounded bg-gray-100 text-gray-600 hover:bg-gray-200 transition">&larr;</a>
            <?php endif; ?>
            <?php
                $start = max(1, $pagination['current_page'] - 3);
                $end = min($pagination['total_pages'], $pagination['current_page'] + 3);
            ?>
            <?php for ($i = $start; $i <= $end; $i++): ?>
                <a href="<?= url('registre?page=' . $i) ?>"
                   class="px-3 py-1 text-sm rounded <?= $i === $pagination['current_page'] ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> transition">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <a href="<?= url('registre?page=' . ($pagination['current_page'] + 1)) ?>"
                   class="px-3 py-1 text-sm rounded bg-gray-100 text-gray-600 hover:bg-gray-200 transition">&rarr;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="px-4 py-3 border-t border-gray-200 text-sm text-gray-500">
        <?= $pagination['total'] ?> registres
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

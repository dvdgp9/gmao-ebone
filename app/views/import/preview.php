<?php
$title = 'Vista prèvia importació';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('import') ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tornar
    </a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2">Vista prèvia</h2>
    <p class="text-gray-500 text-sm mt-1">
        Tipus: <span class="font-medium"><?= $importType === 'tasques_cataleg' ? 'Tasques al Catàleg' : 'Tasques al Pla' ?></span>
        — <?= $totalRows ?> files detectades
    </p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
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

<form method="POST" action="<?= url('import/process') ?>" class="flex items-center gap-3">
    <?= csrf_field() ?>
    <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 transition"
            onclick="return confirm('Segur que vols importar <?= $totalRows ?> registres?')">
        Confirmar importació (<?= $totalRows ?> registres)
    </button>
    <a href="<?= url('import') ?>" class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
        Cancel·lar
    </a>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

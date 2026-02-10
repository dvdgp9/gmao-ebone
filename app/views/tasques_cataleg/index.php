<?php
$title = 'Catàleg de Tasques';
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Catàleg de Tasques</h2>
        <p class="text-gray-500 text-sm mt-1">Base de dades global de tasques de manteniment</p>
    </div>
    <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
    <a href="<?= url('tasques-cataleg/create') ?>" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nova Tasca
    </a>
    <?php endif; ?>
</div>

<!-- Buscador -->
<div class="mb-4">
    <form method="GET" action="<?= url('tasques-cataleg') ?>" class="flex gap-2">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cercar per nom, codi o sistema..."
               class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
        <button type="submit" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Cercar</button>
        <?php if ($search): ?>
            <a href="<?= url('tasques-cataleg') ?>" class="bg-gray-100 text-gray-500 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Netejar</a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Codi</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Tasca</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Sistema</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Periodicitat</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Normativa</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Accions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($tasques)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No hi ha tasques al catàleg.</td></tr>
                <?php else: ?>
                    <?php foreach ($tasques as $tasca): ?>
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
                        <td class="px-4 py-3 text-right">
                            <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?= url('tasques-cataleg/edit/' . $tasca['id']) ?>" class="text-gray-400 hover:text-brand transition" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="<?= url('tasques-cataleg/delete/' . $tasca['id']) ?>" onsubmit="return confirm('Segur que vols desactivar aquesta tasca?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-gray-400 hover:text-red-600 transition" title="Desactivar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-gray-200 text-sm text-gray-500">
        <?= count($tasques) ?> tasques trobades
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

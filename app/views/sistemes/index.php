<?php
$title = 'Sistemes';
ob_start();
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Sistemes</h2>
        <p class="text-gray-500 text-sm mt-1">Catàleg global de sistemes i sigles</p>
    </div>
    <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
    <a href="<?= url('sistemes/create') ?>" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nou Sistema
    </a>
    <?php endif; ?>
</div>

<div class="mb-4">
    <form method="GET" action="<?= url('sistemes') ?>" class="flex flex-col sm:flex-row gap-2">
        <input type="text" name="q" value="<?= e($search ?? '') ?>" placeholder="Cercar per sigla, nom o descripció..."
               class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
        <button type="submit" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Cercar</button>
        <?php if (!empty($search)): ?>
            <a href="<?= url('sistemes') ?>" class="bg-gray-100 text-gray-500 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Netejar</a>
        <?php endif; ?>
    </form>
</div>

<div class="space-y-4 md:hidden">
    <?php if (empty($sistemes)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-4 py-8 text-center text-gray-400">No hi ha sistemes registrats.</div>
    <?php else: ?>
        <?php foreach ($sistemes as $sistema): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-mono text-xs text-brand"><?= e($sistema['codi']) ?></div>
                    <h3 class="text-sm font-semibold text-gray-800 mt-1"><?= e($sistema['nom']) ?></h3>
                </div>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600">
                    <?= (int)$sistema['equips_count'] ?> eq. · <?= (int)$sistema['tasques_count'] ?> tasq.
                </span>
            </div>
            <div class="mt-3 text-sm text-gray-500">
                <?= e($sistema['descripcio'] ?: 'Sense descripció') ?>
            </div>
            <div class="flex items-center justify-end gap-3 mt-4 pt-3 border-t border-gray-100">
                <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
                <a href="<?= url('sistemes/edit/' . $sistema['id']) ?>" class="text-sm text-brand hover:text-brand-dark transition">Editar</a>
                <form method="POST" action="<?= url('sistemes/delete/' . $sistema['id']) ?>" onsubmit="return confirm('Segur que vols eliminar aquest sistema?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-sm text-red-600 hover:text-red-700 transition" <?= ((int)$sistema['equips_count'] > 0 || (int)$sistema['tasques_count'] > 0) ? 'disabled title="Sistema en ús"' : '' ?>>Eliminar</button>
                </form>
                <?php endif; ?>
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
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Sigla</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Nom</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Descripció</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Equips</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Tasques</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Accions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($sistemes)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No hi ha sistemes registrats.</td></tr>
                <?php else: ?>
                    <?php foreach ($sistemes as $sistema): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-mono text-xs text-brand"><?= e($sistema['codi']) ?></td>
                        <td class="px-4 py-3 font-medium"><?= e($sistema['nom']) ?></td>
                        <td class="px-4 py-3 text-gray-500 text-xs max-w-md"><?= e($sistema['descripcio'] ?: '-') ?></td>
                        <td class="px-4 py-3 text-gray-500"><?= (int)$sistema['equips_count'] ?></td>
                        <td class="px-4 py-3 text-gray-500"><?= (int)$sistema['tasques_count'] ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?= url('sistemes/edit/' . $sistema['id']) ?>" class="text-gray-400 hover:text-brand transition" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="<?= url('sistemes/delete/' . $sistema['id']) ?>" onsubmit="return confirm('Segur que vols eliminar aquest sistema?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-gray-400 hover:text-red-600 transition disabled:cursor-not-allowed disabled:text-gray-300" title="<?= ((int)$sistema['equips_count'] > 0 || (int)$sistema['tasques_count'] > 0) ? 'Sistema en ús' : 'Eliminar' ?>" <?= ((int)$sistema['equips_count'] > 0 || (int)$sistema['tasques_count'] > 0) ? 'disabled' : '' ?>>
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
        <?= count($sistemes) ?> sistemes
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

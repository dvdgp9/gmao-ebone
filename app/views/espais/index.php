<?php
$title = 'Espais';
ob_start();
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Espais</h2>
        <p class="text-gray-500 text-sm mt-1">Ubicacions de la instal·lació</p>
    </div>
    <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio', 'cap_manteniment'])): ?>
    <a href="<?= url('espais/create') ?>" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nou Espai
    </a>
    <?php endif; ?>
</div>

<div class="space-y-4 md:hidden">
    <?php if (empty($espais)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-4 py-8 text-center text-gray-400">No hi ha espais registrats.</div>
    <?php else: ?>
        <?php foreach ($espais as $espai): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-mono text-xs text-brand"><?= e($espai['codi'] ?? '-') ?></div>
                    <h3 class="text-sm font-semibold text-gray-800 mt-1"><?= e($espai['nom']) ?></h3>
                </div>
                <span class="inline-block px-2 py-0.5 rounded text-xs <?= ($espai['actiu'] ?? 1) ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                    <?= ($espai['actiu'] ?? 1) ? 'Actiu' : 'Inactiu' ?>
                </span>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-4 text-xs">
                <div>
                    <div class="text-gray-400">Planta</div>
                    <div class="text-gray-700 mt-0.5"><?= e($espai['planta'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="text-gray-400">Zona</div>
                    <div class="text-gray-700 mt-0.5"><?= e($espai['zona'] ?? '-') ?></div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 mt-4 pt-3 border-t border-gray-100">
                <a href="<?= url('espais/edit/' . $espai['id']) ?>" class="text-sm text-brand hover:text-brand-dark transition">Editar</a>
                <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio', 'cap_manteniment'])): ?>
                <form method="POST" action="<?= url('espais/toggle/' . $espai['id']) ?>" onsubmit="return confirm('<?= ($espai['actiu'] ?? 1) ? 'Desactivar aquest espai? Les tasques associades deixaran de sortir com a pendents.' : 'Activar aquest espai?' ?>')">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-sm <?= ($espai['actiu'] ?? 1) ? 'text-yellow-600 hover:text-yellow-700' : 'text-green-600 hover:text-green-700' ?> transition">
                        <?= ($espai['actiu'] ?? 1) ? 'Desactivar' : 'Activar' ?>
                    </button>
                </form>
                <?php endif; ?>
                <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
                <form method="POST" action="<?= url('espais/delete/' . $espai['id']) ?>" onsubmit="return confirm('Segur que vols eliminar aquest espai?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-sm text-red-600 hover:text-red-700 transition">Eliminar</button>
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
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Codi</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Nom</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Planta</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Zona</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Estat</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Accions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($espais)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No hi ha espais registrats.</td></tr>
                <?php else: ?>
                    <?php foreach ($espais as $espai): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-mono text-xs text-brand"><?= e($espai['codi'] ?? '-') ?></td>
                        <td class="px-4 py-3 font-medium"><?= e($espai['nom']) ?></td>
                        <td class="px-4 py-3 text-gray-500"><?= e($espai['planta'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-500"><?= e($espai['zona'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded text-xs <?= ($espai['actiu'] ?? 1) ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                                <?= ($espai['actiu'] ?? 1) ? 'Actiu' : 'Inactiu' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?= url('espais/edit/' . $espai['id']) ?>" class="text-gray-400 hover:text-brand transition" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio', 'cap_manteniment'])): ?>
                                <form method="POST" action="<?= url('espais/toggle/' . $espai['id']) ?>" onsubmit="return confirm('<?= ($espai['actiu'] ?? 1) ? 'Desactivar aquest espai? Les tasques associades deixaran de sortir com a pendents.' : 'Activar aquest espai?' ?>')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-gray-400 <?= ($espai['actiu'] ?? 1) ? 'hover:text-yellow-600' : 'hover:text-green-600' ?> transition" title="<?= ($espai['actiu'] ?? 1) ? 'Desactivar' : 'Activar' ?>">
                                        <?php if ($espai['actiu'] ?? 1): ?>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 105.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        <?php else: ?>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <?php endif; ?>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
                                <form method="POST" action="<?= url('espais/delete/' . $espai['id']) ?>" onsubmit="return confirm('Segur que vols eliminar aquest espai?')">
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
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

<?php
$title = 'Equips';
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Equips</h2>
        <p class="text-gray-500 text-sm mt-1">Inventari d'equips de la instal·lació</p>
    </div>
    <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio', 'cap_manteniment'])): ?>
    <a href="<?= url('equips/create') ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nou Equip
    </a>
    <?php endif; ?>
</div>

<!-- Filtres -->
<div class="mb-4">
    <form method="GET" action="<?= url('equips') ?>" class="flex flex-col sm:flex-row gap-2">
        <input type="text" name="q" value="<?= e($search ?? '') ?>" placeholder="Cercar per codi, nom, model..."
               class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <select name="sistema" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="">Tots els sistemes</option>
            <?php foreach ($sistemes ?? [] as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ($sistemaFilter ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['codi']) ?> — <?= e($s['nom']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Filtrar</button>
        <?php if (!empty($search) || !empty($sistemaFilter)): ?>
            <a href="<?= url('equips') ?>" class="bg-gray-100 text-gray-500 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition text-center">Netejar</a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Codi</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Nom</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Sistema</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Tipus</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Espai</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Model</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Estat</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Accions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($equips)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-400">No hi ha equips registrats.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($equips as $equip): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-mono text-xs text-blue-600"><?= e($equip['nom_mn']) ?></td>
                        <td class="px-4 py-3"><?= e($equip['nom_equip']) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($equip['sistema_codi']): ?>
                                <span class="inline-block bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded"><?= e($equip['sistema_codi']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs"><?= e($equip['tipus_nom'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-500"><?= e($equip['espai_nom'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-500 text-xs"><?= e($equip['model'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <?php
                            $estatColors = [
                                'MB' => 'bg-green-50 text-green-700',
                                'B' => 'bg-blue-50 text-blue-700',
                                'R' => 'bg-yellow-50 text-yellow-700',
                                'D' => 'bg-red-50 text-red-700',
                                'BAIXA' => 'bg-gray-100 text-gray-500',
                            ];
                            $color = $estatColors[$equip['estat_nom'] ?? ''] ?? 'bg-gray-50 text-gray-500';
                            ?>
                            <span class="inline-block text-xs px-2 py-0.5 rounded <?= $color ?>"><?= e($equip['estat_nom'] ?? '-') ?></span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?= url('equips/edit/' . $equip['id']) ?>" class="text-gray-400 hover:text-blue-600 transition" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
                                <form method="POST" action="<?= url('equips/delete/' . $equip['id']) ?>" onsubmit="return confirm('Segur que vols eliminar aquest equip?')">
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

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-500">
            Mostrant <?= count($equips) ?> de <?= $pagination['total'] ?> equips
        </p>
        <div class="flex gap-1">
            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <a href="<?= url('equips?page=' . $i) ?>"
                   class="px-3 py-1 text-sm rounded <?= $i === $pagination['current_page'] ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> transition">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

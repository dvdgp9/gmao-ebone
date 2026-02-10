<?php
$title = 'Usuaris';
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Usuaris</h2>
        <p class="text-gray-500 text-sm mt-1">Gestió d'usuaris i permisos</p>
    </div>
    <a href="<?= url('usuaris/create') ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nou Usuari
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Nom</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Email</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Rol</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-600">Actiu</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Accions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($usuaris)): ?>
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No hi ha usuaris registrats.</td></tr>
                <?php else: ?>
                    <?php foreach ($usuaris as $u): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-medium"><?= e($u['nom'] . ' ' . ($u['cognoms'] ?? '')) ?></td>
                        <td class="px-4 py-3 text-gray-500"><?= e($u['email']) ?></td>
                        <td class="px-4 py-3">
                            <?php if (!empty($u['rol_nom'])): ?>
                                <?php
                                $rolColors = [
                                    'superadmin' => 'bg-red-50 text-red-700',
                                    'admin_instalacio' => 'bg-orange-50 text-orange-700',
                                    'cap_manteniment' => 'bg-blue-50 text-blue-700',
                                    'tecnic' => 'bg-green-50 text-green-700',
                                    'lectura' => 'bg-gray-100 text-gray-600',
                                ];
                                $color = $rolColors[$u['rol_nom']] ?? 'bg-gray-50 text-gray-600';
                                ?>
                                <span class="inline-block text-xs px-2 py-0.5 rounded <?= $color ?>">
                                    <?= e(ucfirst(str_replace('_', ' ', $u['rol_nom']))) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">Sense rol assignat</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($u['actiu']): ?>
                                <span class="inline-block w-2 h-2 bg-green-500 rounded-full"></span>
                            <?php else: ?>
                                <span class="inline-block w-2 h-2 bg-gray-300 rounded-full"></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= url('usuaris/edit/' . $u['id']) ?>" class="text-gray-400 hover:text-blue-600 transition" title="Editar">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
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

<?php
$title = 'Usuaris';
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Usuaris</h2>
        <p class="text-gray-500 text-sm mt-1">Gestió d'usuaris i permisos</p>
    </div>
    <a href="<?= url('usuaris/create') ?>" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nou Usuari
    </a>
</div>

<!-- Stats cards -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <?php
    $totalUsuaris = count($usuaris);
    $actius = count(array_filter($usuaris, fn($u) => $u['actiu']));
    $inactius = $totalUsuaris - $actius;
    ?>
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-brand/10 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800"><?= $totalUsuaris ?></p>
            <p class="text-xs text-gray-500">Total usuaris</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800"><?= $actius ?></p>
            <p class="text-xs text-gray-500">Actius</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800"><?= $inactius ?></p>
            <p class="text-xs text-gray-500">Inactius</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Usuari</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Rol</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Instal·lació</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-600">Estat</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Creat</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Accions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($usuaris)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No hi ha usuaris registrats.</td></tr>
                <?php else: ?>
                    <?php foreach ($usuaris as $u): ?>
                    <tr class="hover:bg-gray-50 transition <?= !$u['actiu'] ? 'opacity-50' : '' ?>">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-brand/10 rounded-full flex items-center justify-center text-brand text-sm font-bold flex-shrink-0">
                                    <?= strtoupper(substr($u['nom'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800"><?= e($u['nom'] . ' ' . ($u['cognoms'] ?? '')) ?></p>
                                    <p class="text-xs text-gray-400"><?= e($u['email']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (!empty($u['is_superadmin'])): ?>
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium bg-red-50 text-red-700">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    Superadmin
                                </span>
                            <?php elseif (!empty($u['rol_nom'])): ?>
                                <?php
                                $rolColors = [
                                    'admin_instalacio' => 'bg-orange-50 text-orange-700',
                                    'cap_manteniment' => 'bg-brand-light text-brand-dark',
                                    'tecnic' => 'bg-green-50 text-green-700',
                                    'lectura' => 'bg-gray-100 text-gray-600',
                                ];
                                $color = $rolColors[$u['rol_nom']] ?? 'bg-gray-50 text-gray-600';
                                ?>
                                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= $color ?>">
                                    <?= e(ucfirst(str_replace('_', ' ', $u['rol_nom']))) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs italic">Sense rol</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            <?= e($u['instalacio_nom'] ?? '—') ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($u['actiu']): ?>
                                <span class="inline-flex items-center gap-1 text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded-full">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Actiu
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Inactiu
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs">
                            <?= date('d/m/Y', strtotime($u['created_at'] ?? 'now')) ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?= url('usuaris/edit/' . $u['id']) ?>" class="text-gray-400 hover:text-brand transition" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <?php if (($u['id'] ?? 0) != ($_SESSION['user_id'] ?? 0)): ?>
                                <form method="POST" action="<?= url('usuaris/toggle/' . $u['id']) ?>" class="inline" onsubmit="return confirm('<?= $u['actiu'] ? 'Desactivar' : 'Activar' ?> aquest usuari?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-gray-400 hover:text-<?= $u['actiu'] ? 'red-500' : 'green-500' ?> transition" title="<?= $u['actiu'] ? 'Desactivar' : 'Activar' ?>">
                                        <?php if ($u['actiu']): ?>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        <?php else: ?>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <?php endif; ?>
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

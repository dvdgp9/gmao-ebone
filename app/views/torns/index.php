<?php
$title = 'Torns';
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Torns</h2>
        <p class="text-gray-500 text-sm mt-1">Gestió de torns de la instal·lació</p>
    </div>
    <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
    <a href="<?= url('torns/create') ?>" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nou Torn
    </a>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($torns)): ?>
        <div class="col-span-full text-center py-12 text-gray-400">No hi ha torns configurats.</div>
    <?php else: ?>
        <?php foreach ($torns as $torn): ?>
        <?php
            $dies = json_decode($torn['dies_setmana'] ?? '[]', true) ?: [];
            $diesNoms = ['dll' => 'Dl', 'dm' => 'Dm', 'dx' => 'Dx', 'dj' => 'Dj', 'dv' => 'Dv', 'ds' => 'Ds', 'dg' => 'Dg'];
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800"><?= e($torn['nom']) ?></h3>
                    <?php if ($torn['hora_inici'] || $torn['hora_fi']): ?>
                        <p class="text-sm text-gray-500 mt-0.5"><?= e($torn['hora_inici'] ?? '?') ?> — <?= e($torn['hora_fi'] ?? '?') ?></p>
                    <?php endif; ?>
                </div>
                <span class="inline-block px-2 py-0.5 rounded text-xs <?= $torn['actiu'] ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                    <?= $torn['actiu'] ? 'Actiu' : 'Inactiu' ?>
                </span>
            </div>
            <div class="flex gap-1 mb-4">
                <?php foreach ($diesNoms as $key => $label): ?>
                    <span class="w-8 h-8 flex items-center justify-center rounded text-xs font-medium <?= in_array($key, $dies) ? 'bg-brand-light text-brand-dark' : 'bg-gray-50 text-gray-300' ?>">
                        <?= $label ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
            <div class="flex gap-3 pt-3 border-t border-gray-100">
                <a href="<?= url('torns/edit/' . $torn['id']) ?>" class="text-sm text-brand hover:text-brand-dark transition">Editar</a>
                <form method="POST" action="<?= url('torns/delete/' . $torn['id']) ?>" onsubmit="return confirm('Segur que vols desactivar aquest torn?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-sm text-red-500 hover:text-red-700 transition">Desactivar</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

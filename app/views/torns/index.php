<?php
$title = 'Torns';
ob_start();
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Torns</h2>
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
            $horaInici = $torn['hora_inici'] ?? null;
            $horaFi = $torn['hora_fi'] ?? null;
            $isActiu = !empty($torn['actiu']);
        ?>
        <div class="rounded-xl border p-6 transition <?= $isActiu ? 'bg-white shadow-sm border-gray-200' : 'bg-gray-50 border-gray-300 opacity-70' ?>">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="text-lg font-semibold <?= $isActiu ? 'text-gray-800' : 'text-gray-500' ?>"><?= e($torn['nom']) ?></h3>
                    <?php if ($horaInici || $horaFi): ?>
                        <p class="text-sm text-gray-500 mt-0.5"><?= e($horaInici ?? '?') ?> — <?= e($horaFi ?? '?') ?></p>
                    <?php endif; ?>
                </div>
                <span class="inline-block px-2 py-0.5 rounded text-xs <?= $isActiu ? 'bg-green-50 text-green-700' : 'bg-gray-200 text-gray-600' ?>">
                    <?= $isActiu ? 'Actiu' : 'Inactiu' ?>
                </span>
            </div>
            <div class="flex gap-1 mb-4">
                <?php foreach ($diesNoms as $key => $label): ?>
                    <span class="w-8 h-8 flex items-center justify-center rounded text-xs font-medium <?= $isActiu && in_array($key, $dies, true) ? 'bg-brand-light text-brand-dark' : 'bg-gray-100 text-gray-300' ?>">
                        <?= $label ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php $assignats = $usuarisPerTorn[(int)$torn['id']] ?? []; ?>
            <?php if (!empty($assignats)): ?>
            <div class="mb-4 text-sm text-gray-600">
                <span class="text-xs font-medium text-gray-400 uppercase">Usuaris</span>
                <p class="mt-0.5"><?= e(implode(', ', $assignats)) ?></p>
            </div>
            <?php endif; ?>
            <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
            <div class="flex gap-3 pt-3 border-t border-gray-100">
                <a href="<?= url('torns/edit/' . $torn['id']) ?>" class="text-sm text-brand hover:text-brand-dark transition">Editar</a>
                <?php if ($isActiu): ?>
                <form method="POST" action="<?= url('torns/delete/' . $torn['id']) ?>" onsubmit="return confirm('Segur que vols desactivar aquest torn?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-sm text-red-500 hover:text-red-700 transition">Desactivar</button>
                </form>
                <?php else: ?>
                <form method="POST" action="<?= url('torns/activate/' . $torn['id']) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-sm text-green-600 hover:text-green-800 transition">Activar</button>
                </form>
                <?php endif; ?>
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

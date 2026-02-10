<?php
$title = "Instal·lacions";
ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Instal·lacions</h2>
        <p class="text-gray-500 text-sm mt-1">Gestió de centres i instal·lacions</p>
    </div>
    <a href="<?= url('instalacions/create') ?>" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nova Instal·lació
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($instalacions)): ?>
        <div class="col-span-full text-center py-12 text-gray-400">No hi ha instal·lacions registrades.</div>
    <?php else: ?>
        <?php foreach ($instalacions as $inst): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800"><?= e($inst['nom']) ?></h3>
                    <?php if ($inst['adreca']): ?>
                        <p class="text-sm text-gray-500 mt-1"><?= e($inst['adreca']) ?></p>
                    <?php endif; ?>
                </div>
                <span class="inline-block px-2 py-0.5 rounded text-xs <?= $inst['activa'] ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                    <?= $inst['activa'] ? 'Activa' : 'Inactiva' ?>
                </span>
            </div>
            <div class="mt-4 text-sm text-gray-500 space-y-1">
                <?php if ($inst['telefon']): ?>
                    <p><?= e($inst['telefon']) ?></p>
                <?php endif; ?>
                <?php if ($inst['email']): ?>
                    <p><?= e($inst['email']) ?></p>
                <?php endif; ?>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 flex gap-3">
                <a href="<?= url('instalacions/edit/' . $inst['id']) ?>" class="text-sm text-brand hover:text-brand-dark transition">Editar</a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

<?php
$title = 'Panel Global';
ob_start();
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Panel Global</h2>
    <p class="text-gray-500 text-sm mt-1">Visió general de totes les instal·lacions</p>
</div>

<!-- Totals -->
<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-brand/10 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800"><?= $totals['instalacions'] ?></p>
            <p class="text-xs text-gray-500">Instal·lacions</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-brand/10 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800"><?= $totals['equips'] ?></p>
            <p class="text-xs text-gray-500">Equips totals</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
        <div class="w-10 h-10 bg-brand/10 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-800"><?= $totals['tasques_pla'] ?></p>
            <p class="text-xs text-gray-500">Tasques al pla</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center gap-3">
        <div class="w-10 h-10 <?= $totals['tasques_vencudes'] > 0 ? 'bg-red-50' : 'bg-green-50' ?> rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 <?= $totals['tasques_vencudes'] > 0 ? 'text-red-500' : 'text-green-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold <?= $totals['tasques_vencudes'] > 0 ? 'text-red-600' : 'text-gray-800' ?>"><?= $totals['tasques_vencudes'] ?></p>
            <p class="text-xs text-gray-500">Tasques vençudes</p>
        </div>
    </div>
</div>

<!-- Installations grid -->
<h3 class="text-lg font-semibold text-gray-800 mb-4">Instal·lacions</h3>

<?php if (empty($instalacions)): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
        <p class="text-gray-400 mb-4">No hi ha instal·lacions creades.</p>
        <a href="<?= url('instalacions/create') ?>" class="inline-flex items-center gap-2 bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Crear instal·lació
        </a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php foreach ($instalacions as $inst): ?>
        <form method="POST" action="<?= url('switch-instalacio') ?>" class="bg-white rounded-xl border border-gray-200 hover:border-brand hover:shadow-md transition-all cursor-pointer group">
            <?= csrf_field() ?>
            <input type="hidden" name="instalacio_id" value="<?= $inst['id'] ?>">
            <button type="submit" class="w-full text-left p-5">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h4 class="text-lg font-bold text-gray-800 group-hover:text-brand transition"><?= e($inst['nom']) ?></h4>
                        <?php if ($inst['adreca']): ?>
                            <p class="text-xs text-gray-400 mt-0.5"><?= e($inst['adreca']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="w-8 h-8 bg-gray-100 group-hover:bg-brand/10 rounded-lg flex items-center justify-center transition">
                        <svg class="w-4 h-4 text-gray-400 group-hover:text-brand transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-2 mt-4">
                    <div class="text-center">
                        <p class="text-lg font-bold text-gray-700"><?= $inst['equips'] ?></p>
                        <p class="text-[10px] text-gray-400 uppercase">Equips</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-bold text-gray-700"><?= $inst['espais'] ?></p>
                        <p class="text-[10px] text-gray-400 uppercase">Espais</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-bold text-gray-700"><?= $inst['tasques_pla'] ?></p>
                        <p class="text-[10px] text-gray-400 uppercase">Tasques</p>
                    </div>
                    <div class="text-center">
                        <?php if ($inst['tasques_vencudes'] > 0): ?>
                            <p class="text-lg font-bold text-red-600"><?= $inst['tasques_vencudes'] ?></p>
                            <p class="text-[10px] text-red-400 uppercase">Vençudes</p>
                        <?php else: ?>
                            <p class="text-lg font-bold text-green-600">0</p>
                            <p class="text-[10px] text-green-400 uppercase">Vençudes</p>
                        <?php endif; ?>
                    </div>
                </div>
            </button>
        </form>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

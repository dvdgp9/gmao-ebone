<?php
$title = 'Configurar Instal·lació';
$items = [
    [
        'title' => 'Espais',
        'description' => 'Defineix les àrees, plantes i zones de la instal·lació.',
        'count' => (int)($stats['espais'] ?? 0),
        'url' => url('espais/create'),
        'cta' => (($stats['espais'] ?? 0) > 0) ? 'Gestionar espais' : 'Afegir primer espai',
    ],
    [
        'title' => 'Torns',
        'description' => 'Configura els torns per organitzar les tasques diàries i setmanals.',
        'count' => (int)($stats['torns'] ?? 0),
        'url' => url('torns/create'),
        'cta' => (($stats['torns'] ?? 0) > 0) ? 'Gestionar torns' : 'Configurar torns',
    ],
    [
        'title' => 'Equips',
        'description' => 'Crea els equips principals abans de carregar el pla de manteniment.',
        'count' => (int)($stats['equips'] ?? 0),
        'url' => url('equips/create'),
        'cta' => (($stats['equips'] ?? 0) > 0) ? 'Gestionar equips' : 'Afegir primer equip',
    ],
    [
        'title' => 'Tasques / Importació',
        'description' => 'Importa dades inicials o carrega tasques del pla de manteniment.',
        'count' => (int)($stats['tasques_pla'] ?? 0),
        'url' => url('import'),
        'cta' => (($stats['tasques_pla'] ?? 0) > 0) ? 'Obrir importació' : 'Importar dades',
    ],
];
$completed = count(array_filter($items, fn($item) => $item['count'] > 0));
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('instalacions') ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tornar a instal·lacions
    </a>
    <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">Instal·lació creada</h2>
            <p class="text-gray-500 text-sm mt-1">Continua amb la configuració inicial de <span class="font-medium text-gray-700"><?= e($instalacio['nom']) ?></span>.</p>
        </div>
        <div class="bg-brand-light text-brand-dark rounded-xl px-4 py-3 text-sm">
            <div class="font-semibold"><?= $completed ?> de <?= count($items) ?> passos iniciats</div>
            <div class="text-xs mt-1">Ja tens aquesta instal·lació com a context actiu.</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-4">
        <?php foreach ($items as $index => $item): ?>
            <?php $done = $item['count'] > 0; ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold <?= $done ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>"><?= $index + 1 ?></span>
                            <h3 class="text-lg font-semibold text-gray-800"><?= e($item['title']) ?></h3>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $done ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' ?>">
                                <?= $done ? 'Completat' : 'Pendent' ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-3"><?= e($item['description']) ?></p>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-2xl font-bold text-gray-800"><?= $item['count'] ?></div>
                        <div class="text-xs text-gray-400">registres</div>
                    </div>
                </div>
                <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-3">
                    <a href="<?= $item['url'] ?>" class="inline-flex items-center justify-center gap-2 bg-brand text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-dark transition">
                        <?= e($item['cta']) ?>
                    </a>
                    <?php if ($done): ?>
                        <span class="text-sm text-gray-500">Ja hi ha contingut carregat en aquest bloc.</span>
                    <?php else: ?>
                        <span class="text-sm text-gray-500">Encara no has configurat aquest apartat.</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h3 class="text-lg font-semibold text-gray-800">Resum ràpid</h3>
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-gray-500">Espais</span>
                    <span class="font-semibold text-gray-800"><?= (int)($stats['espais'] ?? 0) ?></span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-gray-500">Torns</span>
                    <span class="font-semibold text-gray-800"><?= (int)($stats['torns'] ?? 0) ?></span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-gray-500">Equips</span>
                    <span class="font-semibold text-gray-800"><?= (int)($stats['equips'] ?? 0) ?></span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-gray-500">Tasques pla</span>
                    <span class="font-semibold text-gray-800"><?= (int)($stats['tasques_pla'] ?? 0) ?></span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h3 class="text-lg font-semibold text-gray-800">Següents accions</h3>
            <div class="mt-4 flex flex-col gap-3">
                <a href="<?= url('dashboard') ?>" class="inline-flex items-center justify-center gap-2 bg-gray-100 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                    Entrar al dashboard de la instal·lació
                </a>
                <a href="<?= url('instalacions') ?>" class="inline-flex items-center justify-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                    Tornar a instal·lacions
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

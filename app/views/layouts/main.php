<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Dashboard') ?> - <?= e(\App\Config\App::name()) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{brand:{DEFAULT:'#23AAC5',dark:'#1B8FA6',light:'#E8F7FA',50:'#E8F7FA',100:'#C5EDF3',200:'#8DDBE7',300:'#55C9DB',400:'#23AAC5',500:'#1B8FA6',600:'#167487',700:'#115A68',800:'#0C3F49',900:'#07252A'}}}}}</script>
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-link.active { background-color: #23AAC5; color: white; }
        .sidebar-link:hover:not(.active) { background-color: rgb(243 244 246); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php
    $__badgeVencudes = 0;
    if (!empty($_SESSION['instalacio_id'])) {
        try {
            $__db = \App\Models\Database::getInstance();
            $__st = $__db->prepare('SELECT COUNT(*) FROM tasques_pla WHERE instalacio_id = ? AND en_curs = 1 AND data_propera_realitzacio < CURDATE()');
            $__st->execute([$_SESSION['instalacio_id']]);
            $__badgeVencudes = (int)$__st->fetchColumn();
        } catch (\Throwable $e) {}
    }
    ?>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-white border-r border-gray-200 flex flex-col fixed h-full z-30 transition-transform -translate-x-full lg:translate-x-0">
            <div class="p-5 border-b border-gray-200">
                <h1 class="text-xl font-bold text-gray-800">GMAO</h1>
                <p class="text-xs text-gray-400 mt-0.5">Gestió de Manteniment</p>
            </div>

            <?php if (!empty($_SESSION['assignacions']) && count($_SESSION['assignacions']) > 1): ?>
            <div class="px-4 py-3 border-b border-gray-200">
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Instal·lació</label>
                <form method="POST" action="<?= url('switch-instalacio') ?>" id="switchForm">
                    <?= csrf_field() ?>
                    <select name="instalacio_id" onchange="document.getElementById('switchForm').submit()"
                            class="mt-1 w-full text-sm border border-gray-300 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-brand outline-none">
                        <?php foreach ($_SESSION['assignacions'] as $a): ?>
                            <option value="<?= $a['instalacio_id'] ?>" <?= ($a['instalacio_id'] == ($_SESSION['instalacio_id'] ?? '')) ? 'selected' : '' ?>>
                                <?= e($a['instalacio_nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <?php endif; ?>

            <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                <a href="<?= url('dashboard') ?>" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 <?= is_active('dashboard') ?: (is_active('') ? 'active' : '') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>

                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider pt-4 pb-1 px-3">Maestros</p>

                <a href="<?= url('equips') ?>" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 <?= is_active('equips') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Equips
                </a>

                <a href="<?= url('espais') ?>" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 <?= is_active('espais') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Espais
                </a>

                <a href="<?= url('tasques-cataleg') ?>" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 <?= is_active('tasques-cataleg') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    Catàleg Tasques
                </a>

                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider pt-4 pb-1 px-3">Manteniment</p>

                <a href="<?= url('pla') ?>" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 <?= is_active('pla') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Pla de Manteniment
                    <?php if ($__badgeVencudes > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full leading-none"><?= $__badgeVencudes ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?= url('setmana') ?>" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 <?= is_active('setmana') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Vista Setmanal
                    <?php if ($__badgeVencudes > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full leading-none"><?= $__badgeVencudes ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?= url('registre') ?>" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 <?= is_active('registre') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Registre
                </a>

                <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio'])): ?>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider pt-4 pb-1 px-3">Administració</p>

                <a href="<?= url('usuaris') ?>" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 <?= is_active('usuaris') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Usuaris
                </a>

                <?php if (($_SESSION['current_role'] ?? '') === 'superadmin'): ?>
                <a href="<?= url('instalacions') ?>" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 <?= is_active('instalacions') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                    Instal·lacions
                </a>
                <?php endif; ?>

                <a href="<?= url('torns') ?>" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 <?= is_active('torns') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Torns
                </a>

                <a href="<?= url('import') ?>" class="sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-700 <?= is_active('import') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Importar Excel
                </a>
                <?php endif; ?>
            </nav>

            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-brand rounded-full flex items-center justify-center text-white text-sm font-medium">
                        <?= strtoupper(substr($_SESSION['user_nom'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-700 truncate"><?= e($_SESSION['user_nom'] ?? '') ?></p>
                        <p class="text-xs text-gray-400 truncate"><?= e(ucfirst(str_replace('_', ' ', $_SESSION['current_role'] ?? ''))) ?></p>
                    </div>
                    <a href="<?= url('logout') ?>" title="Tancar sessió" class="text-gray-400 hover:text-red-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 lg:ml-64">
            <!-- Topbar -->
            <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between sticky top-0 z-20">
                <button id="sidebarToggle" class="lg:hidden text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex items-center gap-2">
                    <?php if (!empty($_SESSION['instalacio_nom'])): ?>
                        <span class="text-sm text-gray-500">
                            <span class="font-medium text-gray-700"><?= e($_SESSION['instalacio_nom'] ?? '') ?></span>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="text-sm text-gray-400">
                    <?= date('d/m/Y H:i') ?>
                </div>
            </header>

            <!-- Page content -->
            <main class="p-6">
                <?php $flash = $flash ?? flash(); ?>
                <?php if ($flash): ?>
                    <div class="mb-4 p-3 rounded-lg text-sm <?= $flash['type'] === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : ($flash['type'] === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-brand-light text-brand-dark border border-brand-light') ?>">
                        <?= e($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <!-- Overlay para sidebar mobile -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden"></div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');

        if (toggle) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            });
        }
        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            });
        }
    </script>
</body>
</html>

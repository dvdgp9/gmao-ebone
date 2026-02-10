<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Accés denegat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{brand:{DEFAULT:'#23AAC5',dark:'#1B8FA6',light:'#E8F7FA'}}}}}</script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-gray-300">403</h1>
        <p class="text-xl text-gray-600 mt-4">No tens permisos per accedir a aquesta pàgina</p>
        <a href="<?= url('') ?>" class="mt-6 inline-block bg-brand text-white px-6 py-2 rounded-lg hover:bg-brand-dark transition">
            Tornar a l'inici
        </a>
    </div>
</body>
</html>

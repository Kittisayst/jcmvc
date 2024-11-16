<?php
// debug.php
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Information</title>
    <link rel="stylesheet" href="/css/tailwind.min.css">
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-6xl mx-auto">
        <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6">
            <h1 class="text-2xl font-bold text-red-700">
                ພົບຂໍ້ຜິດພາດ: <?= htmlspecialchars($exception->getMessage()) ?>
            </h1>
        </div>

        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Stack Trace</h2>
            <pre class="bg-gray-100 p-4 rounded overflow-x-auto">
<?= htmlspecialchars($exception->getTraceAsString()) ?>
            </pre>
        </div>

        <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Request Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="font-semibold">URI:</h3>
                    <p><?= htmlspecialchars($_SERVER['REQUEST_URI']) ?></p>
                </div>
                <div>
                    <h3 class="font-semibold">Method:</h3>
                    <p><?= htmlspecialchars($_SERVER['REQUEST_METHOD']) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Environment</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="font-semibold">PHP Version:</h3>
                    <p><?= phpversion() ?></p>
                </div>
                <div>
                    <h3 class="font-semibold">Server Software:</h3>
                    <p><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE']) ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
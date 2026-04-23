<?php
require_once 'components/functions.php';
$searchQuery = $_GET['q'] ?? '';
$currentLang = $_SESSION['lang'] ?? 'sk';
?>

<header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-8">
        <div class="flex justify-between items-center h-16">

            <!-- Left -->
            <div class="flex items-center space-x-10">
                <a href="diaries.php" class="text-2xl font-bold text-primary">
                    <?= t('app_name') ?>
                </a>

                <div class="flex items-center space-x-8">
                    <a href="diaries.php" class="text-gray-700 hover:text-primary font-medium <?= $searchQuery === '' ? 'text-primary font-semibold' : '' ?>">
                        <?= t('entries') ?>
                    </a>

                    <form method="GET" action="diaries.php" class="flex items-center">
                        <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>"
                               placeholder="<?= t('search_placeholder') ?>"
                               class="w-80 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                        <button type="submit" class="ml-3 px-5 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-primary hover:text-bg-indigo-700 hover:border-primary transition font-medium flex items-center gap-2 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <?= t('search_button') ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Gear icon + dropdown -->
            <div class="relative">
                <button id="settings-btn" class="p-3 rounded-full hover:bg-gray-100 transition">
                    <!-- Classic perfect gear icon -->
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>

                <!-- Dropdown -->
                <div id="settings-menu" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-50 max-h-96 overflow-y-auto max-h-96 overflow-y-auto">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="font-bold text-gray-800"><?= t('settings') ?></p>
                    </div>

                    <div class="py-2">
                        <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider"><?= t('language') ?></p>
                        <?php
                        $langNames = [
                            'sk'    => 'Slovenčina',
                            'en'    => 'English',
                            'de'    => 'Deutsch',
                            'es'    => 'Español',
                            'fr'    => 'Français',
                            'pt_BR' => 'Português (Brasil)',
                            'ru'    => 'Русский',
                            'ja'    => '日本語',
                            'ko'    => '한국어',
                            'ar'    => 'العربية',
                            'zh_CN' => '中文（简体）',
                        ];
                        foreach ($langNames as $code => $name):
                            $file = __DIR__ . "/../lang/$code.json";
                            if (!file_exists($file)) continue;
                        ?>
                        <a href="?lang=<?= $code ?>" class="block px-4 py-2 text-sm <?= $currentLang === $code ? 'font-bold text-black' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <?= $name ?>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-t border-gray-100 pt-2">
                        <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <?= t('manage_categories_templates') ?>
                        </a>

                        <a href="settings.php#password" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <?= t('change_password') ?>
                        </a>

                        <a href="backup.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <?= t('backup_data') ?>
                        </a>


                        <!-- EXPORT WITH FORMAT CHOICE -->
                        <a href="#" onclick="event.preventDefault(); document.getElementById('exportModal').classList.remove('hidden')"
                           class="block px-4 py-2 text-sm text-green-700 hover:bg-green-50 font-medium">
                            <?= t('export_entries') ?>
                        </a>

                        <!-- IMPORT ENTRIES -->
                        <div class="relative">
                            <label class="block px-4 py-2 text-sm text-blue-700 hover:bg-blue-50 font-medium cursor-pointer">
                                <?= t('import_entries') ?? 'Importovať zápisy (JSON)' ?>
                                <input type="file" id="import-file" accept=".json" class="hidden"
                                       onchange="importEntries(event)">
                            </label>
                        </div>

                        <div class="border-t border-gray-100 mt-2 pt-2">
                            <a href="logout.php"
                               class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium">
                                <?= t('logout') ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- TOTO BY TU NEMALO BYT ALE NERIESIM -->
<div id="exportModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4">
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m-4-6v6m8-6v6m-8 4h8a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800"><?= t('export_entries') ?></h2>
            <p class="text-gray-600 mt-2"><?= t('export_choose_format') ?></p>
        </div>

        <div class="space-y-4">
            <!-- JSON Export -->
            <a href="/components/export.php?format=json"
               class="w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition font-medium">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <span class="text-blue-600 font-bold text-lg">JSON</span>
                    </div>
                    <div class="text-left">
                        <p class="font-semibold">HTML (odporúčané)</p>
                        <p class="text-xs text-gray-500"><?= t('export_json_desc') ?></p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <!-- HTML Export -->
            <a href="/components/export.php?format=html"
               class="w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition font-medium">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <span class="text-orange-600 font-bold text-lg">HTML</span>
                    </div>
                    <div class="text-left">
                        <p class="font-semibold">HTML</p>
                        <p class="text-xs text-gray-500"><?= t('export_txt_desc') ?></p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <!-- TXT Export -->
            <a href="/components/export.php?format=txt"
               class="w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition font-medium">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                        <span class="text-gray-700 font-bold text-lg">TXT</span>
                    </div>
                    <div class="text-left">
                        <p class="font-semibold"><?= t('export_txt') ?></p>
                        <p class="text-xs text-gray-500"><?= t('export_txt_desc') ?></p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <button onclick="document.getElementById('exportModal').classList.add('hidden')"
                class="mt-6 w-full text-center text-gray-500 hover:text-gray-700 text-sm font-medium">
            <?= t('cancel') ?>
        </button>
    </div>
</div>

<script>
    const btn = document.getElementById('settings-btn');
    const menu = document.getElementById('settings-menu');

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('hidden');
    });

    document.addEventListener('click', () => {
        menu.classList.add('hidden');
    });
</script>

<script>
    function importEntries(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (!confirm('<?= addslashes(t('confirm_import') ?? 'Naozaj chceš naimportovať zápisy? Aktuálne zápisy budú nahradené.') ?>')) {
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = JSON.parse(e.target.result);
                if (!Array.isArray(data)) throw new Error('Invalid format');

                // Send to PHP via fetch
                fetch('components/import.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            alert('<?= addslashes(t('import_success') ?? 'Zápisy boli úspešne naimportované!') ?>');
                            location.reload();
                        } else {
                            alert('Chyba: ' + (res.error || 'neznáma'));
                        }
                    });
            } catch (err) {
                alert('<?= addslashes(t('import_error') ?? 'Neplatný JSON súbor') ?>');
            }
        };
        reader.readAsText(file);
    }
</script>

<!-- Upozornenie pre malé obrazovky -->
<div id="screen-warning" class="hidden fixed inset-0 bg-gray-900 flex flex-col items-center justify-center z-50 p-8 text-center">
    <div class="text-8xl mb-6">🖥️</div>
    <h2 class="text-2xl font-bold text-white mb-4">Takto to nebude fungovať!</h2>
    <p class="text-gray-300 max-w-sm">Táto aplikácia je optimalizovaná pre väčšie obrazovky. Skúste to znova na tablete alebo počítači s väčšou obrazovkou.</p>
</div>
<script>
function checkScreenSize() {
    const warning = document.getElementById('screen-warning');
    warning.classList.toggle('hidden', window.innerWidth >= 1024);
}
checkScreenSize();
window.addEventListener('resize', checkScreenSize);
</script>
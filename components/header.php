<?php
require_once 'components/functions.php';
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
                        <a href="#" onclick="event.preventDefault(); document.getElementById('langModal').classList.remove('hidden'); document.getElementById('settings-menu').classList.add('hidden');" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            🌐 <?= t('language') ?>
                        </a>
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
<div id="langModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">🌐 <?= t('language') ?></h2>
        <div class="space-y-2">
            <?php
            $langNames = ["sk"=>"Slovenčina","en"=>"English","de"=>"Deutsch","es"=>"Español","fr"=>"Français","pt_BR"=>"Português (Brasil)","ru"=>"Русский","ja"=>"日本語","ko"=>"한국어","ar"=>"العربية","zh_CN"=>"中文（简体）"];
            $currentLang2 = $_SESSION["lang"] ?? "sk";
            foreach ($langNames as $code => $name):
                $file = __DIR__ . "/../lang/$code.json";
                if (!file_exists($file)) continue;
            ?>
            <a href="?lang=<?= $code ?>" class="flex items-center justify-between px-4 py-3 rounded-xl <?= $currentLang2 === $code ? 'bg-indigo-50 text-indigo-700 font-bold border border-indigo-200' : 'text-gray-700 hover:bg-gray-50' ?> transition">
                <?= $name ?><?php if ($currentLang2 === $code): ?> <span>✓</span><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <button onclick="document.getElementById('langModal').classList.add('hidden')" class="mt-6 w-full text-center text-gray-500 hover:text-gray-700 text-sm">Zavrieť</button>
    </div>
</div>

<div id="exportModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800"><?= t('export_entries') ?></h2>
            <p class="text-gray-600 mt-2"><?= t('export_choose_format') ?></p>
        </div>

        <div class="space-y-4 mb-6">
            <!-- Výber denníka -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Denník</label>
                <select id="export-diary" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="">— Všetky denníky —</option>
                    <?php
                    $dennikStmt = $pdo->prepare("SELECT PK_ID_dennik, nazov FROM Dennik WHERE FK_ID_pouzivatel = ? ORDER BY nazov");
                    $dennikStmt->execute([$_SESSION['user_id'] ?? 0]);
                    foreach ($dennikStmt->fetchAll() as $d):
                    ?>
                    <option value="<?= $d['PK_ID_dennik'] ?>"><?= htmlspecialchars($d['nazov']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Výber rozsahu -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Rozsah</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="export-scope" value="text" checked class="text-indigo-600"> Iba text
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="export-scope" value="full"> Text + prílohy
                    </label>
                </div>
            </div>

            <!-- Výber formátu -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Formát</label>
                <div class="flex gap-3">
                    <button onclick="doExport('html')" class="flex-1 py-3 bg-orange-100 text-orange-700 rounded-xl font-bold hover:bg-orange-200 transition">HTML</button>
                    <button onclick="doExport('txt')" class="flex-1 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition">TXT</button>
                    <button onclick="doExport('json')" class="flex-1 py-3 bg-blue-100 text-blue-700 rounded-xl font-bold hover:bg-blue-200 transition">JSON</button>
                </div>
            </div>
        </div>

        <button onclick="document.getElementById('exportModal').classList.add('hidden')"
                class="w-full text-center text-gray-500 hover:text-gray-700 text-sm font-medium">
            <?= t('cancel') ?>
        </button>
    </div>
</div>

<script>
function doExport(format) {
    const diaryId = document.getElementById('export-diary').value;
    const scope = document.querySelector('input[name="export-scope"]:checked').value;
    let url = '/components/export.php?format=' + format + '&scope=' + scope;
    if (diaryId) url += '&dennik=' + diaryId;
    window.location.href = url;
    document.getElementById('exportModal').classList.add('hidden');
}
</script>

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

<?php
/**
 * Дополнительные экраны админки (вкладки после «Заказы»).
 * Ожидает: $conn, $view, $csrf_token, $bm_shop_ok, $bm_orders_ok
 */
if (!isset($conn) || !isset($view)) {
    return;
}

if (!$bm_shop_ok) {
    echo '<div class="content content-orders-full"><div class="panel-card">';
    echo '<div class="alert alert-error">Расширенные функции магазина не активированы. Откройте в браузере <strong>migrate_shop_full.php</strong> (рядом с <code>index.php</code>).</div>';
    echo '</div></div>';
    return;
}

switch ($view) {
    case 'users':
        $users_list = [];
        $ur = mysqli_query($conn, 'SELECT id, first_name, last_name, email, phone, email_verified FROM users ORDER BY id DESC LIMIT 300');
        if ($ur) {
            while ($row = mysqli_fetch_assoc($ur)) {
                $users_list[] = $row;
            }
        }
        ?>
        <div class="content content-orders-full">
            <div class="panel-card">
                <h2 style="margin-bottom:14px;">Клиенты</h2>
                <p style="font-size:0.85rem;color:#666;margin-bottom:14px;">Зарегистрированные пользователи (до 300 записей).</p>
                <?php if (count($users_list) === 0): ?>
                    <p style="color:#777;">Пользователей нет.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="products-table">
                            <thead>
                                <tr><th>ID</th><th>Имя</th><th>Email</th><th>Телефон</th><th>Почта подтв.</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users_list as $u): ?>
                                <tr>
                                    <td><?php echo (int) $u['id']; ?></td>
                                    <td><?php echo htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($u['email'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($u['phone'] ?? '')); ?></td>
                                    <td><?php echo !empty($u['email_verified']) ? 'да' : 'нет'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        break;

    case 'delivery':
        $dl = [];
        $dr = mysqli_query($conn, 'SELECT * FROM delivery_methods ORDER BY sort_order ASC, id ASC');
        while ($dr && ($row = mysqli_fetch_assoc($dr))) {
            $dl[] = $row;
        }
        ?>
        <div class="content content-orders-full">
            <div class="panel-card">
                <h2 style="margin-bottom:14px;">Доставка</h2>
                <form method="post" style="margin-bottom:22px;padding-bottom:18px;border-bottom:1px solid #eee;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="delivery_save">
                    <input type="hidden" name="delivery_id" value="0">
                    <div class="form-group"><label>Название</label><input type="text" name="title" required maxlength="128"></div>
                    <div class="form-group"><label>Цена (₽)</label><input type="number" name="price_rub" required min="0" max="999999" value="0"></div>
                    <div class="form-group"><label>Порядок</label><input type="number" name="sort_order" min="0" value="10"></div>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" name="active" value="1" checked> Активна</label>
                    <button type="submit" class="btn-main" style="margin-top:12px;">Добавить способ</button>
                </form>
                <?php foreach ($dl as $d): ?>
                <form method="post" style="margin-bottom:16px;padding:14px;background:#fafafa;border-radius:12px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="delivery_save">
                    <input type="hidden" name="delivery_id" value="<?php echo (int) $d['id']; ?>">
                    <div style="display:grid;grid-template-columns:2fr 1fr auto;gap:12px;align-items:end;">
                        <div class="form-group" style="margin:0;"><label>Название</label><input type="text" name="title" value="<?php echo htmlspecialchars($d['title']); ?>" required></div>
                        <div class="form-group" style="margin:0;"><label>Цена ₽</label><input type="number" name="price_rub" min="0" value="<?php echo (int) $d['price_rub']; ?>" required></div>
                        <label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" name="active" value="1"<?php echo !empty($d['active']) ? ' checked' : ''; ?>> вкл</label>
                    </div>
                    <div class="form-group"><label>Порядок</label><input type="number" name="sort_order" style="max-width:120px;" value="<?php echo (int) $d['sort_order']; ?>"></div>
                    <button type="submit" class="btn-edit">Сохранить</button>
                </form>
                <form method="post" style="display:inline;" onsubmit="return confirm('Удалить этот способ доставки?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="delivery_delete">
                    <input type="hidden" name="delivery_id" value="<?php echo (int) $d['id']; ?>">
                    <button type="submit" class="btn-delete">Удалить запись #<?php echo (int) $d['id']; ?></button>
                </form>
                <hr style="margin:18px 0;border:none;border-top:1px solid #eee;">
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        break;

    case 'promos':
        $pl = [];
        $pr = mysqli_query($conn, 'SELECT * FROM promocodes ORDER BY id DESC');
        while ($pr && ($row = mysqli_fetch_assoc($pr))) {
            $pl[] = $row;
        }
        ?>
        <div class="content content-orders-full">
            <div class="panel-card">
                <h2 style="margin-bottom:14px;">Промокоды</h2>
                <form method="post" style="margin-bottom:24px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="promo_save">
                    <input type="hidden" name="promo_id" value="0">
                    <div class="form-group"><label>Код</label><input type="text" name="code" required maxlength="32" placeholder="SALE10"></div>
                    <div class="form-group"><label>Тип скидки</label>
                        <select name="discount_type"><option value="percent">Процент</option><option value="fixed">Фикс ₽</option></select>
                    </div>
                    <div class="form-group"><label>Значение (% или ₽)</label><input type="number" name="discount_value" min="0" max="999999" value="10"></div>
                    <div class="form-group"><label>Мин. сумма заказа ₽</label><input type="number" name="min_order_rub" min="0" value="0"></div>
                    <div class="form-group"><label>Действует до (YYYY-MM-DD)</label><input type="text" name="valid_until" placeholder=""></div>
                    <label style="display:flex;gap:8px;align-items:center;"><input type="checkbox" name="active" value="1" checked> Активен</label>
                    <button type="submit" class="btn-main" style="margin-top:12px;">Добавить промокод</button>
                </form>
                <?php foreach ($pl as $p): ?>
                <form method="post" style="margin-bottom:14px;padding:14px;border:1px solid #eee;border-radius:12px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="promo_save">
                    <input type="hidden" name="promo_id" value="<?php echo (int) $p['id']; ?>">
                    <strong><?php echo htmlspecialchars($p['code']); ?></strong>
                    <div class="form-group"><label>Тип</label>
                        <select name="discount_type">
                            <option value="percent"<?php echo $p['discount_type'] === 'percent' ? ' selected' : ''; ?>>Процент</option>
                            <option value="fixed"<?php echo $p['discount_type'] === 'fixed' ? ' selected' : ''; ?>>Фикс ₽</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Значение</label><input type="number" name="discount_value" value="<?php echo (int) $p['discount_value']; ?>"></div>
                    <div class="form-group"><label>Мин. сумма</label><input type="number" name="min_order_rub" value="<?php echo (int) $p['min_order_rub']; ?>"></div>
                    <div class="form-group"><label>До даты</label><input type="text" name="valid_until" value="<?php echo htmlspecialchars(($p['valid_until'] && $p['valid_until'] !== '0000-00-00') ? $p['valid_until'] : ''); ?>"></div>
                    <label><input type="checkbox" name="active" value="1"<?php echo !empty($p['active']) ? ' checked' : ''; ?>> Активен</label>
                    <button type="submit" class="btn-edit">Сохранить</button>
                </form>
                <form method="post" style="display:inline;margin-bottom:22px;" onsubmit="return confirm('Удалить промокод?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="promo_delete">
                    <input type="hidden" name="promo_id" value="<?php echo (int) $p['id']; ?>">
                    <button type="submit" class="btn-delete">Удалить</button>
                </form>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        break;

    case 'cms':
        $pages = [];
        $pq = mysqli_query($conn, 'SELECT slug, title, published, updated_at FROM cms_pages ORDER BY slug ASC');
        while ($pq && ($row = mysqli_fetch_assoc($pq))) {
            $pages[] = $row;
        }
        $editSlug = isset($_GET['cms_slug']) ? preg_replace('/[^a-z0-9\-_]/i', '', $_GET['cms_slug']) : '';
        $editRow = null;
        if ($editSlug !== '') {
            $es = mysqli_real_escape_string($conn, $editSlug);
            $eq = mysqli_query($conn, "SELECT * FROM cms_pages WHERE slug='$es' LIMIT 1");
            $editRow = $eq ? mysqli_fetch_assoc($eq) : null;
        }
        ?>
        <div class="content content-orders-full">
            <div class="panel-card">
                <h2 style="margin-bottom:14px;">Страницы сайта</h2>
                <p style="font-size:0.85rem;color:#666;">Просмотр: <code>page.php?slug=...</code></p>
                <ul style="margin:14px 0;padding-left:18px;">
                    <?php foreach ($pages as $pg): ?>
                        <li><a href="?view=cms&amp;cms_slug=<?php echo urlencode($pg['slug']); ?>"><?php echo htmlspecialchars($pg['slug']); ?></a>
                            — <?php echo htmlspecialchars($pg['title']); ?></li>
                    <?php endforeach; ?>
                </ul>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="cms_save">
                    <div class="form-group"><label>Slug (латиница)</label><input type="text" name="slug" required maxlength="64" value="<?php echo htmlspecialchars($editRow['slug'] ?? ''); ?>"<?php echo $editRow ? ' readonly' : ''; ?>></div>
                    <div class="form-group"><label>Заголовок</label><input type="text" name="cms_title" required maxlength="255" value="<?php echo htmlspecialchars($editRow['title'] ?? ''); ?>"></div>
                    <div class="form-group"><label>HTML содержимое</label><textarea name="body_html" rows="12" required><?php echo htmlspecialchars($editRow['body_html'] ?? ''); ?></textarea></div>
                    <div class="form-group"><label>Meta description</label><input type="text" name="meta_description" maxlength="512" value="<?php echo htmlspecialchars($editRow['meta_description'] ?? ''); ?>"></div>
                    <label><input type="checkbox" name="published" value="1"<?php echo (!isset($editRow['published']) || !empty($editRow['published'])) ? ' checked' : ''; ?>> Опубликовано</label>
                    <button type="submit" class="btn-main" style="margin-top:12px;">Сохранить страницу</button>
                </form>
            </div>
        </div>
        <?php
        break;

    case 'media':
        $media_list = [];
        $mq = mysqli_query($conn, 'SELECT * FROM media_files ORDER BY id DESC LIMIT 100');
        while ($mq && ($row = mysqli_fetch_assoc($mq))) {
            $media_list[] = $row;
        }
        ?>
        <div class="content content-orders-full">
            <div class="panel-card">
                <h2 style="margin-bottom:14px;">Медиатека</h2>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="media_upload">
                    <div class="form-group"><label>Файл (jpg, png, webp, gif)</label><input type="file" name="media_file" accept="image/*" required></div>
                    <button type="submit" class="btn-main">Загрузить</button>
                </form>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-top:22px;">
                    <?php foreach ($media_list as $m): ?>
                        <div style="border:1px solid #eee;border-radius:10px;padding:8px;text-align:center;font-size:0.72rem;">
                            <div style="height:100px;background:#fafafa;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:6px;">
                                <img src="<?php echo htmlspecialchars('../' . $m['path']); ?>" alt="" style="max-width:100%;max-height:100%;">
                            </div>
                            <div style="word-break:break-all;"><?php echo htmlspecialchars($m['path']); ?></div>
                            <form method="post" onsubmit="return confirm('Удалить файл?');" style="margin-top:6px;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="action" value="media_delete">
                                <input type="hidden" name="media_id" value="<?php echo (int) $m['id']; ?>">
                                <button type="submit" class="btn-delete">Удалить</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        break;

    case 'logs':
        $secDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.security';
        $logs = ['login_attempts.log', 'admin_actions.log'];
        $shown = '';
        foreach ($logs as $lf) {
            $fp = $secDir . DIRECTORY_SEPARATOR . $lf;
            if (!is_file($fp)) {
                continue;
            }
            $txt = @file_get_contents($fp);
            if ($txt === false) {
                continue;
            }
            $lines = explode("\n", $txt);
            $tail = array_slice($lines, -120);
            $shown .= "\n\n=== " . htmlspecialchars($lf, ENT_QUOTES, 'UTF-8') . " (последние 120 строк) ===\n" . htmlspecialchars(implode("\n", $tail), ENT_QUOTES, 'UTF-8');
        }
        ?>
        <div class="content content-orders-full">
            <div class="panel-card">
                <h2 style="margin-bottom:14px;">Журнал безопасности</h2>
                <pre style="white-space:pre-wrap;font-size:0.72rem;background:#fafafa;padding:14px;border-radius:12px;max-height:480px;overflow:auto;"><?php echo $shown !== '' ? $shown : 'Логи не найдены или пусты.'; ?></pre>
            </div>
        </div>
        <?php
        break;

    case 'reports':
        $oc = 0;
        $sum = 0;
        if ($bm_orders_ok) {
            $st = mysqli_query($conn, 'SELECT COUNT(*) AS c, COALESCE(SUM(total_amount),0) AS s FROM orders');
            if ($st && ($row = mysqli_fetch_assoc($st))) {
                $oc = (int) $row['c'];
                $sum = (int) $row['s'];
            }
        }
        $mf = bm_shop_settings_get($conn, 'mail_from', 'noreply@basemood.ru');
        $mfn = bm_shop_settings_get($conn, 'mail_from_name', 'BASEMOOD');
        $adm = bm_shop_settings_get($conn, 'admin_notify_email', '');
        ?>
        <div class="content content-orders-full">
            <div class="panel-card">
                <h2 style="margin-bottom:14px;">Отчёты и экспорт</h2>
                <p>Всего заказов: <strong><?php echo $oc; ?></strong>, сумма по полю total_amount: <strong><?php echo number_format($sum, 0, '.', ' '); ?> ₽</strong></p>
                <p style="margin-top:14px;"><a class="btn-main" href="export_orders.php" style="display:inline-flex;text-decoration:none;">Скачать CSV заказов</a></p>
            </div>
            <div class="panel-card" style="margin-top:18px;">
                <h2 style="margin-bottom:14px;">Почта уведомлений</h2>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="save_shop_settings">
                    <div class="form-group"><label>Адрес отправителя (From)</label><input type="email" name="mail_from" value="<?php echo htmlspecialchars($mf); ?>" maxlength="255"></div>
                    <div class="form-group"><label>Имя отправителя</label><input type="text" name="mail_from_name" value="<?php echo htmlspecialchars($mfn); ?>" maxlength="128"></div>
                    <div class="form-group"><label>Email администратора для копий заказов</label><input type="email" name="admin_notify_email" value="<?php echo htmlspecialchars($adm); ?>" maxlength="255" placeholder="пусто — только клиенту"></div>
                    <button type="submit" class="btn-main">Сохранить настройки почты</button>
                    <p style="font-size:0.78rem;color:#777;margin-top:12px;">Требуется работающая функция mail() на сервере или настройка SMTP у хостера.</p>
                </form>
            </div>
        </div>
        <?php
        break;

    default:
        echo '<div class="content content-orders-full"><div class="panel-card"><p>Раздел не найден.</p></div></div>';
}

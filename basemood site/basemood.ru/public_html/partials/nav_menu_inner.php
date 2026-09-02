<?php
declare(strict_types=1);

if (!function_exists('bm_product_categories')) {
    require_once dirname(__DIR__) . '/catalog_helpers.php';
}
$bmNavCats = bm_product_categories();
$bmNavClothing = bm_catalog_nav_clothing_slugs();
$bmNavAccessories = bm_catalog_nav_accessories_slugs();
$bmNavSelf = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$bmNavHomeActive = $bmNavSelf === 'index.php' ? ' active' : '';
$bmNavAboutActive = $bmNavSelf === 'about.php' ? ' active' : '';
$bmNavCatalogCurrent = $bmNavSelf === 'catalog.php' ? ' nav-dropdown--current' : '';
?>
                <li><a href="index.php" class="nav-link<?php echo $bmNavHomeActive; ?>">Главная</a></li>
                <li class="nav-dropdown nav-catalog-desktop<?php echo $bmNavCatalogCurrent; ?>">
                    <a href="catalog.php" class="nav-dropdown-toggle" id="catalogNavToggle" aria-haspopup="true" aria-controls="catalogNavMenu" aria-expanded="false">
                        Каталог
                    </a>
                    <div class="nav-dropdown-menu" id="catalogNavMenu" role="menu">
                        <div class="nav-dropdown-columns">
                            <div class="nav-dropdown-col">
                                <div class="nav-dropdown-label">Одежда</div>
                                <?php foreach ($bmNavClothing as $slug): ?>
                                    <?php if (!isset($bmNavCats[$slug])) {
                                        continue;
                                    } ?>
                                    <a role="menuitem" href="catalog.php?cat=<?php echo urlencode($slug); ?>" class="nav-dropdown-link"><?php echo htmlspecialchars($bmNavCats[$slug], ENT_QUOTES, 'UTF-8'); ?></a>
                                <?php endforeach; ?>
                            </div>
                            <div class="nav-dropdown-col">
                                <div class="nav-dropdown-label">Аксессуары</div>
                                <?php foreach ($bmNavAccessories as $slug): ?>
                                    <?php if (!isset($bmNavCats[$slug])) {
                                        continue;
                                    } ?>
                                    <a role="menuitem" href="catalog.php?cat=<?php echo urlencode($slug); ?>" class="nav-dropdown-link"><?php echo htmlspecialchars($bmNavCats[$slug], ENT_QUOTES, 'UTF-8'); ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="nav-mobile-cat mobile-only">
                    <button type="button" class="nav-mobile-cat-toggle" aria-expanded="false" aria-controls="navMobileClothing" id="navMobileClothingBtn">
                        Одежда
                        <i class="fas fa-chevron-down nav-mobile-cat-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="nav-mobile-cat-panel" id="navMobileClothing" role="menu">
                        <?php foreach ($bmNavClothing as $slug): ?>
                            <?php if (!isset($bmNavCats[$slug])) {
                                continue;
                            } ?>
                            <a role="menuitem" href="catalog.php?cat=<?php echo urlencode($slug); ?>" class="nav-dropdown-link"><?php echo htmlspecialchars($bmNavCats[$slug], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php endforeach; ?>
                    </div>
                </li>
                <li class="nav-mobile-cat mobile-only">
                    <button type="button" class="nav-mobile-cat-toggle" aria-expanded="false" aria-controls="navMobileAccessories" id="navMobileAccessoriesBtn">
                        Аксессуары
                        <i class="fas fa-chevron-down nav-mobile-cat-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="nav-mobile-cat-panel" id="navMobileAccessories" role="menu">
                        <?php foreach ($bmNavAccessories as $slug): ?>
                            <?php if (!isset($bmNavCats[$slug])) {
                                continue;
                            } ?>
                            <a role="menuitem" href="catalog.php?cat=<?php echo urlencode($slug); ?>" class="nav-dropdown-link"><?php echo htmlspecialchars($bmNavCats[$slug], ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php endforeach; ?>
                    </div>
                </li>
                <li><a href="about.php" class="nav-link<?php echo $bmNavAboutActive; ?>">О нас</a></li>

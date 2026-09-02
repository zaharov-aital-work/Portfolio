<?php
declare(strict_types=1);
/** @var array $product */
$card_imgs = bm_product_card_urls($product);
$card_img_count = count($card_imgs);
$cat_slug = bm_normalize_category($product['category'] ?? '');
$sort_order = (int) ($product['sort_order'] ?? 0);
$popularity = (int) ($product['popularity'] ?? 0);
$price = (int) ($product['price'] ?? 0);
?>
                    <div class="product-card" data-product-id="<?= (int) $product['id'] ?>"
                         data-price="<?= $price ?>"
                         data-category="<?= htmlspecialchars($cat_slug, ENT_QUOTES, 'UTF-8') ?>"
                         data-popularity="<?= $popularity ?>"
                         data-sort-order="<?= $sort_order ?>">
                        <a href="product.php?id=<?= (int) $product['id'] ?>">
                            <div class="product-image-container">
                                <div class="product-image-wrapper">
                                    <div class="image-slider">
                                        <?php foreach ($card_imgs as $ci => $img_src): ?>
                                        <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($product['name']) ?><?= $ci > 0 ? ' — фото ' . ($ci + 1) : '' ?>" class="product-image<?= $ci === 0 ? ' active' : '' ?>">
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if ($card_img_count > 1): ?>
                                    <div class="slider-dots">
                                        <?php for ($di = 0; $di < $card_img_count; $di++): ?>
                                        <span class="dot<?= $di === 0 ? ' active' : '' ?>" data-index="<?= $di ?>"></span>
                                        <?php endfor; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <button class="wishlist-btn" data-id="<?= (int) $product['id'] ?>" aria-label="Добавить в избранное">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="product-price"><?= number_format($price, 0, '', ' ') ?> ₽</p>
                            </div>
                        </a>
                    </div>

// ==========================================================================
// BASEMOOD - Основной JavaScript файл (адаптирован для работы с PHP)
// ==========================================================================

class BasemoodStore {
    constructor() {
        this.cart = [];
        this.favorites = [];
        this.cartStorageKey = 'basemood-cart';
        this.favoritesStorageKey = 'basemood-favorites';
        // Убираем статический массив productsData — товары теперь из БД через PHP
        this.productsData = [];

        this.init();
    }

    // ==========================================================================
    // Секция: Инициализация приложения
    // ==========================================================================
    init() {
        // Загружаем товары из DOM (уже отрисованы PHP)
        this.loadProductsFromDOM();
        this.setupEventListeners();
        this.loadCartFromStorage();
        this.loadFavoritesFromStorage();
        this.hideLoadingSpinner();
        this.initProducts();
        this.updateWishlistButtons();
    }

    // ==========================================================================
    // НОВЫЙ МЕТОД: Загружаем товары из DOM (из PHP-карточек)
    // ==========================================================================
    loadProductsFromDOM() {
        const cards = document.querySelectorAll('.product-card');
        this.productsData = [];
        
        cards.forEach(card => {
            const id = parseInt(card.dataset.productId);
            const titleEl = card.querySelector('.product-title');
            const priceEl = card.querySelector('.product-price');
            const images = [];
            
            // Собираем изображения из карточки
            card.querySelectorAll('.product-image').forEach(img => {
                images.push({
                    src: img.src,
                    alt: img.alt
                });
            });
            
            if (titleEl && priceEl) {
                this.productsData.push({
                    id: id,
                    name: titleEl.textContent,
                    price: parseInt(priceEl.textContent.replace(/[^0-9]/g, '')),
                    url: card.querySelector('a')?.getAttribute('href') || `product-${id}.html`,
                    image: card.querySelector('.product-image')?.getAttribute('src') || '',
                    images: images,
                    tags: []
                });
            }
        });
    }

    // ==========================================================================
    // Секция: Настройка обработчиков событий
    // ==========================================================================
    setupEventListeners() {
        // Мобильное меню
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const navMenu = document.getElementById('navMenu');
        let mobileMenuBackdrop = document.querySelector('.mobile-menu-backdrop');

        const buildMobileMenuExtras = () => {
            if (!navMenu || navMenu.querySelector('.mobile-menu-head')) return;

            const isLoggedIn = Boolean(window.isLoggedIn);
            const accountHref = isLoggedIn ? 'account.php' : 'login.php';

            navMenu.insertAdjacentHTML('afterbegin', `
                <li class="mobile-only mobile-menu-head">
                    <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Закрыть меню">
                        <i class="fas fa-times"></i>
                    </button>
                </li>
            `);

            navMenu.insertAdjacentHTML('beforeend', `
                <li class="mobile-only mobile-menu-divider" aria-hidden="true"></li>
                <li class="mobile-only"><a href="${accountHref}" class="nav-link">Кабинет</a></li>
                <li class="mobile-only"><a href="account.php#wishlist" class="nav-link">Избранное</a></li>
                <li class="mobile-only"><a href="account.php#cart" class="nav-link">Корзина</a></li>
                <li class="mobile-only mobile-menu-divider" aria-hidden="true"></li>
                <li class="mobile-only">
                    <button class="mobile-menu-info-trigger" id="mobileInfoOpen" aria-label="Открыть раздел Информация">
                        <span>Информация</span>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </li>
                <li class="mobile-only mobile-socials">
                    <a href="https://vk.com/club237586332" aria-label="VK" target="_blank" rel="noopener noreferrer"><i class="fab fa-vk"></i><span>ВКонтакте</span></a>
                    <a href="https://max.ru/join/GSuv4hKzTfDOEAjg-SN_1B5zAeRmfCM5sSwL7DzsP3w" aria-label="MAX" target="_blank" rel="noopener noreferrer"><img src="img/Max_logo.png" class="mobile-max-logo" alt="MAX"><span>MAX</span></a>
                </li>
                <li class="mobile-only mobile-info-panel" id="mobileInfoPanel">
                    <button class="mobile-info-back" id="mobileInfoBack" aria-label="Назад к основному меню">
                        <i class="fas fa-arrow-left"></i>
                        <span>Назад</span>
                    </button>
                    <div class="mobile-info-title">Информация</div>
                    <ul class="mobile-info-links">
                        <li><a href="offer.php">Публичная оферта</a></li>
                        <li><a href="privacy.php">Политика конфиденциальности</a></li>
                        <li><a href="#">Вопросы и ответы</a></li>
                        <li><a href="about.php">Контакты</a></li>
                    </ul>
                </li>
            `);
        };

        /** Убирает Telegram из соцблока (актуально при кэше старой вёрстки или лишних ссылках). */
        const stripTelegramFromMobileMenu = () => {
            if (!navMenu) return;
            navMenu.querySelectorAll('.mobile-socials a').forEach((a) => {
                const href = (a.getAttribute('href') || '').toLowerCase();
                const label = (a.getAttribute('aria-label') || '').toLowerCase();
                const txt = (a.textContent || '').toLowerCase().trim();
                if (href.includes('t.me') || href.includes('telegram.me') || label.includes('telegram') || txt === 'telegram' || txt.includes('телеграм')) {
                    a.remove();
                }
            });
        };

        const closeMobileMenu = () => {
            if (!navMenu || !mobileMenuToggle) return;
            navMenu.classList.remove('active');
            navMenu.classList.remove('info-open');
            mobileMenuToggle.classList.remove('active');
            if (mobileMenuBackdrop) mobileMenuBackdrop.classList.remove('active');
            navMenu.querySelectorAll('.nav-dropdown').forEach((d) => {
                d.classList.remove('open');
                const b = d.querySelector('.nav-dropdown-toggle');
                if (b) b.setAttribute('aria-expanded', 'false');
            });
            navMenu.querySelectorAll('.nav-mobile-cat').forEach((d) => {
                d.classList.remove('open');
                const t = d.querySelector('.nav-mobile-cat-toggle');
                if (t) t.setAttribute('aria-expanded', 'false');
            });
        };
        
        if (mobileMenuToggle && navMenu) {
            buildMobileMenuExtras();
            stripTelegramFromMobileMenu();

            if (!mobileMenuBackdrop) {
                mobileMenuBackdrop = document.createElement('div');
                mobileMenuBackdrop.className = 'mobile-menu-backdrop';
                document.body.appendChild(mobileMenuBackdrop);
            }

            mobileMenuToggle.addEventListener('click', () => {
                const willOpen = !navMenu.classList.contains('active');
                navMenu.classList.toggle('active');
                mobileMenuToggle.classList.toggle('active');
                navMenu.classList.remove('info-open');
                if (mobileMenuBackdrop) {
                    mobileMenuBackdrop.classList.toggle('active', willOpen);
                }
            });

            const mobileMenuClose = document.getElementById('mobileMenuClose');
            const mobileInfoOpen = document.getElementById('mobileInfoOpen');
            const mobileInfoBack = document.getElementById('mobileInfoBack');

            if (mobileMenuClose) {
                mobileMenuClose.addEventListener('click', closeMobileMenu);
            }

            if (mobileMenuBackdrop) {
                mobileMenuBackdrop.addEventListener('click', closeMobileMenu);
            }

            if (mobileInfoOpen) {
                mobileInfoOpen.addEventListener('click', () => {
                    navMenu.classList.add('info-open');
                });
            }

            if (mobileInfoBack) {
                mobileInfoBack.addEventListener('click', () => {
                    navMenu.classList.remove('info-open');
                });
            }

            navMenu.querySelectorAll('a.nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    closeMobileMenu();
                });
            });

            navMenu.querySelectorAll('a.nav-dropdown-link').forEach(link => {
                link.addEventListener('click', () => {
                    closeMobileMenu();
                });
            });

            /* Делегирование: на iOS/Android тап часто попадает в <i>, а не в кнопку; без matchMedia — ширина viewport на устройстве может отличаться от эмулятора */
            navMenu.addEventListener('click', (e) => {
                const btn = e.target.closest('.nav-mobile-cat-toggle');
                if (!btn || !navMenu.contains(btn)) return;
                e.preventDefault();
                const li = btn.closest('.nav-mobile-cat');
                if (!li) return;
                const willOpen = !li.classList.contains('open');
                navMenu.querySelectorAll('.nav-mobile-cat').forEach((d) => {
                    if (d !== li) {
                        d.classList.remove('open');
                        const ot = d.querySelector('.nav-mobile-cat-toggle');
                        if (ot) ot.setAttribute('aria-expanded', 'false');
                    }
                });
                li.classList.toggle('open', willOpen);
                btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });
        }

        // Поиск
        const searchButton = document.getElementById('searchButton');
        const searchContainer = document.getElementById('searchContainer');
        const searchClose = document.getElementById('searchClose');
        const searchInputField = document.querySelector('.search-input');
        const searchAutocomplete = document.getElementById('searchAutocomplete');

        const clearSearchState = () => {
            if (searchInputField) {
                searchInputField.value = '';
            }

            if (searchAutocomplete) {
                searchAutocomplete.classList.remove('active');
                searchAutocomplete.innerHTML = '';
            }

            this.resetProductFilter();
        };
        
        if (searchButton && searchContainer) {
            searchButton.addEventListener('click', () => {
                const willOpen = !searchContainer.classList.contains('active');
                searchContainer.classList.toggle('active');
                if (willOpen) {
                    setTimeout(() => {
                        const searchInputFocus = document.querySelector('.search-input');
                        if (searchInputFocus) searchInputFocus.focus();
                    }, 100);
                } else {
                    clearSearchState();
                }
            });
        }
        
        if (searchClose && searchContainer) {
            searchClose.addEventListener('click', () => {
                searchContainer.classList.remove('active');
                clearSearchState();
            });
        }

        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            const onSearch = () => {
                const query = searchInput.value.trim().toLowerCase();
                if (!query) {
                    this.resetProductFilter();
                    return;
                }

                const filtered = this.productsData.filter(item => {
                    const byTitle = item.name.toLowerCase().includes(query);
                    const byTags = item.tags.some(tag => tag.toLowerCase().includes(query));
                    const byPrice = String(item.price).includes(query);
                    return byTitle || byTags || byPrice;
                });

                this.filterProductsBySearch(filtered);
                this.updateSearchMessage(filtered.length > 0);
            };

            const autocomplete = document.getElementById('searchAutocomplete');

            const renderAutocomplete = (query) => {
                if (!autocomplete) return;

                const normalized = query.trim().toLowerCase();
                if (!normalized) {
                    autocomplete.classList.remove('active');
                    autocomplete.innerHTML = '';
                    return;
                }

                const matches = this.productsData
                    .filter(item => item.name.toLowerCase().includes(normalized) || item.tags.some(tag => tag.toLowerCase().includes(normalized)))
                    .slice(0, 6);

                if (matches.length === 0) {
                    autocomplete.classList.remove('active');
                    autocomplete.innerHTML = '';
                    return;
                }

                autocomplete.innerHTML = matches.map(item => `
                    <div class="search-autocomplete-item" data-name="${item.name}">${item.name}</div>
                `).join('');
                autocomplete.classList.add('active');

                autocomplete.querySelectorAll('.search-autocomplete-item').forEach(item => {
                    item.addEventListener('click', () => {
                        searchInput.value = item.dataset.name;
                        autocomplete.classList.remove('active');
                        onSearch();
                    });
                });
            };

            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    onSearch();
                }
            });

            searchInput.addEventListener('input', () => {
                const query = searchInput.value;
                onSearch();
                renderAutocomplete(query);
            });

            document.addEventListener('click', (e) => {
                if (autocomplete && !e.target.closest('#searchContainer')) {
                    autocomplete.classList.remove('active');
                }
            });
        }

        // Корзина
        const cartButton = document.getElementById('cartButton');
        const wishlistButton = document.getElementById('wishlistButton');
        
        if (cartButton) {
            cartButton.addEventListener('click', () => {
                if (window.location.pathname.toLowerCase().includes('account.php')) {
                    const cartMenu = document.querySelector('.lk-nav a[data-section="cart"]');
                    if (cartMenu) cartMenu.click();
                } else {
                    window.location.href = 'account.php#cart';
                }
            });
        }

        if (wishlistButton) {
            wishlistButton.addEventListener('click', () => {
                if (window.location.pathname.toLowerCase().includes('account.php')) {
                    window.location.hash = 'wishlist';
                    const wishlistMenu = document.querySelector('.lk-nav a[data-section="wishlist"]');
                    if (wishlistMenu) wishlistMenu.click();
                } else {
                    window.location.href = 'account.php#wishlist';
                }
            });
        }

        // Скролл хедера
        window.addEventListener('scroll', () => {
            this.handleHeaderScroll();
        });

        // Логотип
        const logoLink = document.querySelector('.logo-link');
        if (logoLink) {
            logoLink.addEventListener('click', (e) => {
                const isHomePage = window.location.pathname.endsWith('index.php') || window.location.pathname === '/' || window.location.pathname.endsWith('/');
                if (isHomePage) {
                    e.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
                // На остальных страницах — стандартный переход по href
            });
        }

        // Навигационные ссылки
        const navLinks = document.querySelectorAll('.nav-link[href^="#"]');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href');
                
                if (targetId === '#home') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        const navHeight = document.querySelector('.nav-container').offsetHeight;
                        window.scrollTo({ top: targetElement.offsetTop - navHeight, behavior: 'smooth' });
                    }
                }
                
                const navMenu = document.getElementById('navMenu');
                const mobileMenuToggle = document.getElementById('mobileMenuToggle');
                if (navMenu && mobileMenuToggle) {
                    navMenu.classList.remove('active');
                    navMenu.classList.remove('info-open');
                    mobileMenuToggle.classList.remove('active');
                    const backdrop = document.querySelector('.mobile-menu-backdrop');
                    if (backdrop) backdrop.classList.remove('active');
                }
            });
        });

        // Закрытие на Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (searchContainer && searchContainer.classList.contains('active')) {
                    searchContainer.classList.remove('active');
                    clearSearchState();
                }
                if (navMenu) {
                    navMenu.classList.remove('active');
                    navMenu.classList.remove('info-open');
                }
                if (mobileMenuToggle) mobileMenuToggle.classList.remove('active');
                const backdrop = document.querySelector('.mobile-menu-backdrop');
                if (backdrop) backdrop.classList.remove('active');
                const authModal = document.getElementById('authModal');
                if (authModal) authModal.classList.remove('active');
            }
        });

        // Кнопка личного кабинета
        const userButton = document.getElementById('userButton');
        if (userButton) {
            userButton.addEventListener('click', () => {
                if (window.isLoggedIn) {
                    window.location.href = 'account.php';
                } else {
                    const authModal = document.getElementById('authModal');
                    if (authModal) {
                        authModal.classList.add('active');
                        setTimeout(() => {
                            const emailInput = document.getElementById('authEmail');
                            if (emailInput) emailInput.focus();
                        }, 100);
                    } else {
                        window.location.href = 'login.php';
                    }
                }
            });
        }

        // Модальное окно авторизации
        const authModal = document.getElementById('authModal');
        const authModalClose = document.getElementById('authModalClose');
        const authModalForm = document.getElementById('authModalForm');

        if (authModalClose) {
            authModalClose.addEventListener('click', () => {
                authModal.classList.remove('active');
            });
        }

        if (authModal) {
            authModal.addEventListener('click', (e) => {
                if (e.target === authModal) authModal.classList.remove('active');
            });
        }

        if (authModalForm) {
            authModalForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const msgEl = document.getElementById('authModalMsg');
                const submitBtn = document.getElementById('authModalSubmit');

                submitBtn.disabled = true;
                submitBtn.textContent = 'Вход...';
                if (msgEl) msgEl.style.display = 'none';

                const formData = new FormData();
                formData.append('email', document.getElementById('authEmail').value.trim());
                formData.append('password', document.getElementById('authPassword').value);
                formData.append('ajax', '1');

                try {
                    const res = await fetch('login.php', { method: 'POST', body: formData });
                    const data = await res.json();

                    if (data.success) {
                        window.location.href = 'account.php';
                    } else {
                        if (msgEl) {
                            msgEl.textContent = data.error || 'Неверный email или пароль';
                            msgEl.style.display = 'block';
                        }
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Войти';
                    }
                } catch (err) {
                    if (msgEl) {
                        msgEl.textContent = 'Ошибка сети. Попробуйте снова.';
                        msgEl.style.display = 'block';
                    }
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Войти';
                }
            });
        }
    }

    // ==========================================================================
    // Секция: Фильтрация товаров при поиске
    // ==========================================================================
    filterProductsBySearch(filteredProducts) {
        if (typeof window.catalogApplyFilters === 'function') {
            window.catalogApplyFilters();
            const visibleCount = Array.from(document.querySelectorAll('.product-card')).filter(
                c => c.style.display !== 'none'
            ).length;
            this.updateSearchMessage(visibleCount > 0);
            return;
        }

        const allCards = document.querySelectorAll('.product-card');
        
        if (filteredProducts.length === 0) {
            allCards.forEach(card => card.style.display = 'none');
        } else {
            const filteredIds = filteredProducts.map(p => p.id);
            allCards.forEach(card => {
                const cardId = parseInt(card.dataset.productId);
                card.style.display = filteredIds.includes(cardId) ? 'flex' : 'none';
            });
        }
    }

    resetProductFilter() {
        if (typeof window.catalogApplyFilters === 'function') {
            window.catalogApplyFilters();
            this.updateSearchMessage(true);
            return;
        }

        const allCards = document.querySelectorAll('.product-card');
        allCards.forEach(card => card.style.display = 'flex');
        this.updateSearchMessage(true);
    }

    updateSearchMessage(found) {
        let message = document.getElementById('searchResultMessage');
        const container = document.querySelector('.products .container');
        if (!container) return;

        if (!found) {
            if (!message) {
                message = document.createElement('div');
                message.id = 'searchResultMessage';
                message.style.margin = '1rem 0';
                message.style.color = '#555';
                message.style.fontWeight = '600';
                message.textContent = 'По запросу ничего не найдено.';
                container.insertBefore(message, container.querySelector('.product-grid'));
            }
        } else if (message) {
            message.remove();
        }
    }

    // ==========================================================================
    // Секция: Функциональность товаров
    // ==========================================================================
    initProducts() {
        this.setupProductInteractions();
        this.initProductImageLoading();
        this.setupProductHoverEffects();
        this.setupProductSwipeGestures();
    }

    setupProductInteractions() {
        // Клик по карточке товара (переход на страницу)
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.wishlist-btn') && !e.target.closest('.dot') && !e.target.closest('a')) {
                    this.openProductPage(card);
                }
            });
            card.style.cursor = 'pointer';
        });

        // Кнопки избранного: pointerup для тача; тот же жест даёт click — не тогглим дважды; click без pointerup (клавиатура) — один тоггл; click глушим от карточки
        const wishlistButtons = document.querySelectorAll('.product-card .wishlist-btn');
        wishlistButtons.forEach(button => {
            button.style.touchAction = 'manipulation';
            let ignoreNextClick = false;
            button.addEventListener('pointerup', (e) => {
                if (e.pointerType === 'mouse' && e.button !== 0) return;
                e.preventDefault();
                e.stopPropagation();
                ignoreNextClick = true;
                this.toggleWishlist(button);
            });
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (ignoreNextClick) {
                    ignoreNextClick = false;
                    return;
                }
                this.toggleWishlist(button);
            });
        });

        // Точки слайдера
        const allDots = document.querySelectorAll('.dot');
        allDots.forEach(dot => {
            dot.addEventListener('click', (e) => {
                e.stopPropagation();
                const card = e.target.closest('.product-card');
                const targetIndex = parseInt(e.target.dataset.index);
                this.showImageByIndex(card, targetIndex);
            });
        });
    }

    initProductImageLoading() {
        const productImages = document.querySelectorAll('.product-image');
        productImages.forEach(img => {
            if (img.complete) {
                img.classList.add('loaded');
            } else {
                img.addEventListener('load', () => img.classList.add('loaded'));
                img.addEventListener('error', () => {
                    const ph = '<svg xmlns="http://www.w3.org/2000/svg" width="360" height="390" viewBox="0 0 360 390"><rect width="360" height="390" fill="none"/><path d="M180 195V220M150 195H210M180 170V145M210 170H150" stroke="#e0e0e0" stroke-width="2" fill="none"/></svg>';
                    img.src = 'data:image/svg+xml,' + encodeURIComponent(ph);
                    img.alt = 'Изображение временно недоступно';
                    img.classList.add('loaded');
                });
            }
        });
    }

    setupProductHoverEffects() {
        const productCards = document.querySelectorAll('.product-card');
        
        productCards.forEach(card => {
            const imageWrapper = card.querySelector('.product-image-wrapper');
            if (!imageWrapper) return;

            const slider = card.querySelector('.image-slider');
            const images = slider ? slider.querySelectorAll('.product-image') : [];
            const imageCount = images.length;
            if (imageCount <= 1) return;

            let currentHoverIndex = 0;

            imageWrapper.addEventListener('mousemove', (event) => {
                const wrapperRect = imageWrapper.getBoundingClientRect();
                const cursorX = event.clientX - wrapperRect.left;
                const width = wrapperRect.width;
                if (width <= 0) return;
                const ratio = Math.min(1, Math.max(0, cursorX / width));
                const targetIndex = Math.min(imageCount - 1, Math.floor(ratio * imageCount));

                if (targetIndex !== currentHoverIndex) {
                    this.showImageByIndex(card, targetIndex);
                    currentHoverIndex = targetIndex;
                }
            });

            imageWrapper.addEventListener('mouseleave', () => {
                this.showImageByIndex(card, 0);
                currentHoverIndex = 0;
            });
        });
    }

    setupProductSwipeGestures() {
        const productCards = document.querySelectorAll('.product-card');

        productCards.forEach(card => {
            const imageWrapper = card.querySelector('.product-image-wrapper');
            const images = card.querySelectorAll('.product-image');

            if (!imageWrapper || images.length < 2) return;

            let startX = 0;
            let startY = 0;
            let deltaX = 0;
            let isHorizontalSwipe = false;

            imageWrapper.addEventListener('touchstart', (e) => {
                if (e.touches.length !== 1) return;

                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                deltaX = 0;
                isHorizontalSwipe = false;
            }, { passive: true });

            imageWrapper.addEventListener('touchmove', (e) => {
                if (e.touches.length !== 1) return;

                deltaX = e.touches[0].clientX - startX;
                const deltaY = e.touches[0].clientY - startY;

                if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 8) {
                    isHorizontalSwipe = true;
                    e.preventDefault();
                }
            }, { passive: false });

            imageWrapper.addEventListener('touchend', () => {
                if (!isHorizontalSwipe) return;

                const swipeThreshold = 40;
                if (Math.abs(deltaX) < swipeThreshold) return;

                const currentIndex = Array.from(images).findIndex(img => img.classList.contains('active'));
                if (currentIndex < 0) return;

                let targetIndex = currentIndex;
                if (deltaX < 0) {
                    targetIndex = Math.min(images.length - 1, currentIndex + 1);
                } else {
                    targetIndex = Math.max(0, currentIndex - 1);
                }

                this.showImageByIndex(card, targetIndex);

                // Блокируем одиночный клик по ссылке сразу после свайпа.
                card.dataset.swiped = '1';
                setTimeout(() => {
                    card.dataset.swiped = '';
                }, 120);
            });

            const cardLink = card.querySelector('a');
            if (cardLink) {
                cardLink.addEventListener('click', (e) => {
                    if (card.dataset.swiped === '1') {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                });
            }
        });
    }

    showImageByIndex(card, targetIndex) {
        const slider = card.querySelector('.image-slider');
        const images = slider?.querySelectorAll('.product-image');
        const dots = card.querySelectorAll('.dot');
        
        if (!images || targetIndex >= images.length) return;
        
        images.forEach(img => img.classList.remove('active'));
        images[targetIndex].classList.add('active');
        
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === targetIndex);
        });
    }

    updateDots(card, activeIndex) {
        const dots = card.querySelectorAll('.dot');
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === activeIndex);
        });
    }

    // ==========================================================================
    // Секция: Переход на страницу товара
    // ==========================================================================
    openProductPage(card) {
        const link = card.querySelector('a');
        if (link && link.href) {
            window.location.href = link.href;
        }
    }

    // ==========================================================================
    // Секция: Избранное
    // ==========================================================================
    toggleWishlist(button) {
        const id = Number.parseInt(button.dataset.id, 10);
        if (!Number.isFinite(id)) return;
        const icon = button.querySelector('i');
        const isActive = button.classList.contains('active');
        const product = this.getProductDataById(id, button.closest('.product-card'));

        if (isActive) {
            this.favorites = this.favorites.filter(item => this.getFavoriteId(item) !== id);
            button.classList.remove('active');
            if (icon) icon.className = 'far fa-heart';
        } else {
            if (!this.getFavoriteIds().includes(id)) {
                this.favorites.push({
                    id: id,
                    name: product.name || `Товар #${id}`,
                    price: product.price || 0,
                    image: product.image || '',
                    url: product.url || `product.php?id=${id}`
                });
            }
            button.classList.add('active');
            if (icon) icon.className = 'fas fa-heart';
        }

        this.saveFavoritesToStorage();
    }

    updateWishlistButtons() {
        document.querySelectorAll('.wishlist-btn').forEach(btn => {
            const id = Number.parseInt(btn.dataset.id, 10);
            if (!Number.isFinite(id)) return;
            if (this.getFavoriteIds().includes(id)) {
                btn.classList.add('active');
                const icon = btn.querySelector('i');
                if (icon) icon.className = 'fas fa-heart';
            } else {
                btn.classList.remove('active');
                const icon = btn.querySelector('i');
                if (icon) icon.className = 'far fa-heart';
            }
        });
    }

    getFavoriteId(item) {
        return typeof item === 'number' ? item : parseInt(item?.id, 10);
    }

    getFavoriteIds() {
        return this.favorites
            .map(item => this.getFavoriteId(item))
            .filter(id => Number.isFinite(id));
    }

    normalizeFavoritesData() {
        if (!Array.isArray(this.favorites)) {
            this.favorites = [];
            return;
        }

        const normalized = [];
        const seen = new Set();
        this.favorites.forEach(raw => {
            const id = this.getFavoriteId(raw);
            if (!Number.isFinite(id) || seen.has(id)) return;
            seen.add(id);

            const product = this.getProductDataById(id);
            if (typeof raw === 'number') {
                normalized.push({
                    id,
                    name: product.name || `Товар #${id}`,
                    price: product.price || 0,
                    image: product.image || '',
                    url: product.url || `product.php?id=${id}`
                });
            } else {
                normalized.push({
                    id,
                    name: raw.name || product.name || `Товар #${id}`,
                    price: Number(raw.price || product.price || 0),
                    image: raw.image || product.image || '',
                    url: raw.url || product.url || `product.php?id=${id}`
                });
            }
        });

        this.favorites = normalized;
    }

    getProductDataById(id, fallbackCard = null) {
        const parsedId = parseInt(id, 10);
        const fromData = this.productsData.find(p => p.id === parsedId);
        if (fromData) return fromData;

        const card = fallbackCard || document.querySelector(`.product-card[data-product-id="${parsedId}"]`);
        if (!card) {
            return {
                id: parsedId,
                name: `Товар #${parsedId}`,
                price: 0,
                image: '',
                url: `product.php?id=${parsedId}`
            };
        }

        const name = card.querySelector('.product-title')?.textContent?.trim() || `Товар #${parsedId}`;
        const priceText = card.querySelector('.product-price')?.textContent || '0';
        const price = parseInt(priceText.replace(/[^0-9]/g, ''), 10) || 0;
        const image = card.querySelector('.product-image')?.getAttribute('src') || '';
        const url = card.querySelector('a')?.getAttribute('href') || `product.php?id=${parsedId}`;

        return { id: parsedId, name, price, image, url };
    }

    // ==========================================================================
    // Секция: Обработка скролла
    // ==========================================================================
    handleHeaderScroll() {
        const navContainer = document.querySelector('.nav-container');
        if (navContainer) {
            navContainer.classList.toggle('scrolled', window.scrollY > 50);
        }
    }

    // ==========================================================================
    // Секция: Корзина (localStorage)
    // ==========================================================================
    loadCartFromStorage() {
        try {
            const savedCart = localStorage.getItem(this.cartStorageKey);
            if (savedCart) {
                const parsed = JSON.parse(savedCart);
                this.cart = Array.isArray(parsed) ? parsed : [];
            }
            this.updateCartCounter();
        } catch (error) {
            console.error('Ошибка загрузки корзины:', error);
            this.cart = [];
            this.updateCartCounter();
        }
    }

    saveCartToStorage() {
        try {
            localStorage.setItem(this.cartStorageKey, JSON.stringify(this.cart));
        } catch (error) {
            console.error('Ошибка сохранения корзины:', error);
        }
    }

    loadFavoritesFromStorage() {
        try {
            const savedFavorites = localStorage.getItem(this.favoritesStorageKey);
            if (savedFavorites) {
                this.favorites = JSON.parse(savedFavorites);
            }
            this.normalizeFavoritesData();
            this.saveFavoritesToStorage();
        } catch (error) {
            console.error('Ошибка загрузки избранного:', error);
            this.favorites = [];
        }
    }

    saveFavoritesToStorage() {
        try {
            localStorage.setItem(this.favoritesStorageKey, JSON.stringify(this.favorites));
        } catch (error) {
            console.error('Ошибка сохранения избранного:', error);
        }
    }

    updateCartCounter() {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            const total = this.cart.reduce((sum, item) => sum + (parseInt(item.quantity, 10) || 1), 0);
            cartCount.textContent = total;
            
            cartCount.style.transform = 'scale(1.3)';
            setTimeout(() => {
                cartCount.style.transform = 'scale(1)';
            }, 300);
        }
    }

    addToCart(product, size = 'M', quantity = 1, sourceButton = null) {
        const id = parseInt(product?.id, 10);
        if (!Number.isFinite(id)) return;

        const safeSize = size || 'M';
        const existing = this.cart.find(item => parseInt(item.id, 10) === id && (item.size || 'M') === safeSize);

        if (existing) {
            existing.quantity = (parseInt(existing.quantity, 10) || 1) + quantity;
        } else {
            this.cart.push({
                id: id,
                name: product.name || `Товар #${id}`,
                price: parseInt(product.price, 10) || 0,
                image: product.image || '',
                url: product.url || `product.php?id=${id}`,
                size: safeSize,
                quantity: quantity
            });
        }

        this.saveCartToStorage();
        this.updateCartCounter();
        this.animateAddToCartFeedback(sourceButton);
    }

    animateAddToCartFeedback(sourceButton = null) {
        const cartButton = document.getElementById('cartButton');
        if (cartButton && typeof cartButton.animate === 'function') {
            cartButton.animate([
                { transform: 'scale(1)' },
                { transform: 'scale(1.08)' },
                { transform: 'scale(0.96)' },
                { transform: 'scale(1)' }
            ], {
                duration: 520,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)'
            });
        }

        if (sourceButton) {
            sourceButton.classList.remove('is-added');
            void sourceButton.offsetWidth;
            sourceButton.classList.add('is-added');
            setTimeout(() => {
                sourceButton.classList.remove('is-added');
            }, 650);
        }

        if (sourceButton && cartButton) {
            const sourceRect = sourceButton.getBoundingClientRect();
            const cartRect = cartButton.getBoundingClientRect();
            const flyingIcon = document.createElement('div');

            flyingIcon.className = 'cart-fly-icon';
            flyingIcon.innerHTML = '<i class="fas fa-basket-shopping"></i>';
            flyingIcon.style.position = 'fixed';
            flyingIcon.style.left = `${sourceRect.left + sourceRect.width / 2 - 20}px`;
            flyingIcon.style.top = `${sourceRect.top + sourceRect.height / 2 - 20}px`;
            flyingIcon.style.width = '40px';
            flyingIcon.style.height = '40px';
            flyingIcon.style.borderRadius = '999px';
            flyingIcon.style.display = 'flex';
            flyingIcon.style.alignItems = 'center';
            flyingIcon.style.justifyContent = 'center';
            flyingIcon.style.background = '#111';
            flyingIcon.style.color = '#fff';
            flyingIcon.style.fontSize = '16px';
            flyingIcon.style.pointerEvents = 'none';
            flyingIcon.style.zIndex = '4000';
            flyingIcon.style.boxShadow = '0 12px 28px rgba(0, 0, 0, 0.18)';

            document.body.appendChild(flyingIcon);

            const deltaX = cartRect.left + cartRect.width / 2 - (sourceRect.left + sourceRect.width / 2);
            const deltaY = cartRect.top + cartRect.height / 2 - (sourceRect.top + sourceRect.height / 2);

            const animation = flyingIcon.animate([
                {
                    transform: 'translate3d(0, 0, 0) scale(1)',
                    opacity: 0
                },
                {
                    transform: `translate3d(${deltaX * 0.28}px, ${deltaY * 0.18 - 36}px, 0) scale(1.08)`,
                    opacity: 1,
                    offset: 0.25
                },
                {
                    transform: `translate3d(${deltaX * 0.74}px, ${deltaY * 0.72 - 12}px, 0) scale(0.94)`,
                    opacity: 1,
                    offset: 0.78
                },
                {
                    transform: `translate3d(${deltaX}px, ${deltaY}px, 0) scale(0.62)`,
                    opacity: 0
                }
            ], {
                duration: 720,
                easing: 'cubic-bezier(0.2, 0.8, 0.2, 1)',
                fill: 'forwards'
            });

            animation.onfinish = () => flyingIcon.remove();
        }
    }

    // ==========================================================================
    // Секция: Уведомления
    // ==========================================================================
    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `<div class="notification-content"><i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation' : 'info'}"></i><span>${message}</span></div>`;
        
        if (!document.querySelector('.notification-styles')) {
            const styles = document.createElement('style');
            styles.className = 'notification-styles';
            styles.textContent = `
                .notification { position: fixed; top: 100px; right: 20px; background: #28a745; color: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 3000; animation: slideInRight 0.3s ease; max-width: 300px; }
                .notification-error { background: #dc3545; }
                .notification-info { background: #17a2b8; }
                .notification-content { display: flex; align-items: center; gap: 0.5rem; }
                @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
            `;
            document.head.appendChild(styles);
        }
        
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.animation = 'slideInRight 0.3s ease reverse';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // ==========================================================================
    // Секция: Скрытие спиннера
    // ==========================================================================
    hideLoadingSpinner() {
        const loadingSpinner = document.getElementById('loadingSpinner');
        if (loadingSpinner) {
            setTimeout(() => {
                loadingSpinner.classList.add('hidden');
                setTimeout(() => loadingSpinner.remove(), 300);
            }, 500);
        }
    }
}

// Инициализация
if (typeof window !== 'undefined') {
    if (!NodeList.prototype.forEach) {
        NodeList.prototype.forEach = Array.prototype.forEach;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.basemoodStore = new BasemoodStore();
    
    // Инициализация размеров и галереи на странице товара
    initProductPage();
});

function initProductPage() {
    // Обработка размеров
    const sizeOptions = document.querySelectorAll('.size-option-simple');
    sizeOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            if (this.classList.contains('size-unavailable')) {
                e.preventDefault();
                return;
            }
            e.preventDefault();
            sizeOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            localStorage.setItem('selected-size', this.dataset.size);
        });
    });

    const act0 = document.querySelector('.size-option-simple.active');
    if (act0 && act0.dataset && act0.dataset.size) {
        localStorage.setItem('selected-size', act0.dataset.size);
    }

    // Обработка галереи (legacy-режим только когда нет swipe-галереи)
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainImage = document.getElementById('mainImage');
    const hasSwipeGallery = Boolean(document.getElementById('gallerySwipe'));

    if (mainImage && !hasSwipeGallery) {
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                const imageSrc = this.dataset.image;
                if (!imageSrc) return;
                thumbnails.forEach(thumb => thumb.classList.remove('active'));
                this.classList.add('active');
                mainImage.src = imageSrc;
            });
        });
    }
    
    // Кнопка "В корзину"
    const addToCartBtn = document.getElementById('addToCart');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            const activeOpt = document.querySelector('.size-option-simple.active');
            const selectedSize = (activeOpt && activeOpt.dataset.size)
                ? activeOpt.dataset.size
                : (localStorage.getItem('selected-size') || 'M');
            if (activeOpt && activeOpt.classList.contains('size-unavailable')) {
                alert('Выберите доступный размер.');
                return;
            }
            const title = document.querySelector('.product-main-title')?.textContent;
            const price = document.querySelector('.product-price-large')?.textContent;
            const mainImageSrc = document.getElementById('mainImage')?.getAttribute('src') || '';
            const params = new URLSearchParams(window.location.search);
            const productId = parseInt(params.get('id'), 10);
            const normalizedPrice = parseInt((price || '0').replace(/[^0-9]/g, ''), 10) || 0;
            
            if (window.basemoodStore) {
                window.basemoodStore.addToCart({
                    id: Number.isFinite(productId) ? productId : Date.now(),
                    name: title || 'Товар',
                    price: normalizedPrice,
                    image: mainImageSrc,
                    url: window.location.pathname + window.location.search
                }, selectedSize, 1, this);
            }
        });
    }
}
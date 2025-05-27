// تكوين API
const API_URL = 'https://example.com/marketplace/api';
// يمكن تغيير هذا الرابط إلى عنوان API الخاص بك

// عناصر DOM
const splashScreen = document.getElementById('splash-screen');
const mainContent = document.getElementById('main-content');
const navItems = document.querySelectorAll('.nav-item');
const pages = document.querySelectorAll('.page');
const featuredProductsContainer = document.getElementById('featured-products');
const newProductsContainer = document.getElementById('new-products');
const allCategoriesContainer = document.getElementById('all-categories');
const favoriteProductsContainer = document.getElementById('favorite-products');
const userOrdersContainer = document.getElementById('user-orders');
const searchInput = document.getElementById('search-input');
const searchBtn = document.getElementById('search-btn');
const cartCountElement = document.getElementById('cart-count');
const loginBtn = document.getElementById('login-btn');
const loginModal = document.getElementById('login-modal');
const registerModal = document.getElementById('register-modal');
const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const loginLink = document.getElementById('login-link');
const registerLink = document.getElementById('register-link');
const closeModalBtns = document.querySelectorAll('.close-modal');
const toast = document.getElementById('toast');
const toastContent = document.querySelector('.toast-content');
const userName = document.getElementById('user-name');
const userEmail = document.getElementById('user-email');

// بيانات التطبيق
let currentUser = null;
let cartItems = [];
let favoriteProducts = [];
let categories = [];
let featuredProducts = [];
let newProducts = [];
let userOrders = [];

// وظائف مساعدة
function showToast(message) {
    toastContent.textContent = message;
    toast.classList.add('active');
    setTimeout(() => {
        toast.classList.remove('active');
    }, 3000);
}

function formatCurrency(amount) {
    return `${amount.toFixed(2)} ر.س`;
}

function createStars(rating) {
    const starsContainer = document.createElement('div');
    starsContainer.className = 'stars';
    
    for (let i = 1; i <= 5; i++) {
        const star = document.createElement('span');
        star.innerHTML = i <= rating ? '★' : '☆';
        star.style.color = i <= rating ? '#FFC107' : '#ccc';
        starsContainer.appendChild(star);
    }
    
    return starsContainer;
}

// وظائف التحميل
function loadApp() {
    // محاكاة تحميل التطبيق
    setTimeout(() => {
        splashScreen.style.display = 'none';
        mainContent.style.display = 'block';
        
        // تحميل البيانات
        loadCategories();
        loadFeaturedProducts();
        loadNewProducts();
        loadCartCount();
        
        // التحقق من تسجيل الدخول
        checkLoginStatus();
    }, 2000);
}

function checkLoginStatus() {
    // التحقق من وجود رمز التوثيق في التخزين المحلي
    const token = localStorage.getItem('authToken');
    if (token) {
        // محاكاة جلب بيانات المستخدم
        currentUser = {
            id: 1,
            name: 'مستخدم السوق',
            email: 'user@example.com'
        };
        
        updateUserInterface();
        loadFavoriteProducts();
        loadUserOrders();
    }
}

function updateUserInterface() {
    if (currentUser) {
        userName.textContent = currentUser.name;
        userEmail.textContent = currentUser.email;
        loginBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>تسجيل الخروج</span>
        `;
        loginBtn.removeEventListener('click', showLoginModal);
        loginBtn.addEventListener('click', logout);
    } else {
        userName.textContent = 'تسجيل الدخول';
        userEmail.textContent = '';
        loginBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                <polyline points="10 17 15 12 10 7"></polyline>
                <line x1="15" y1="12" x2="3" y2="12"></line>
            </svg>
            <span>تسجيل الدخول</span>
        `;
        loginBtn.removeEventListener('click', logout);
        loginBtn.addEventListener('click', showLoginModal);
    }
}

// وظائف تحميل البيانات
function loadCategories() {
    // محاكاة جلب التصنيفات من API
    categories = [
        { id: 1, name: 'الإلكترونيات', product_count: 120, icon: 'smartphone' },
        { id: 2, name: 'الملابس', product_count: 85, icon: 'shopping-bag' },
        { id: 3, name: 'المنزل والمطبخ', product_count: 64, icon: 'coffee' },
        { id: 4, name: 'الرياضة', product_count: 42, icon: 'activity' },
        { id: 5, name: 'الجمال والعناية الشخصية', product_count: 56, icon: 'scissors' },
        { id: 6, name: 'الكتب', product_count: 38, icon: 'book' },
        { id: 7, name: 'الألعاب', product_count: 29, icon: 'target' },
        { id: 8, name: 'السيارات', product_count: 17, icon: 'truck' }
    ];
    
    renderCategories();
}

function renderCategories() {
    if (!allCategoriesContainer) return;
    
    allCategoriesContainer.innerHTML = '';
    
    categories.forEach(category => {
        const categoryElement = document.createElement('div');
        categoryElement.className = 'category-list-item';
        categoryElement.innerHTML = `
            <div class="category-list-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-${category.icon}">
                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                    <line x1="12" y1="18" x2="12.01" y2="18"></line>
                </svg>
            </div>
            <div class="category-list-info">
                <h3>${category.name}</h3>
                <p>${category.product_count} منتج</p>
            </div>
        `;
        
        categoryElement.addEventListener('click', () => {
            // التنقل إلى صفحة التصنيف
            showToast(`تم اختيار تصنيف ${category.name}`);
        });
        
        allCategoriesContainer.appendChild(categoryElement);
    });
}

function loadFeaturedProducts() {
    // محاكاة جلب المنتجات المميزة من API
    featuredProducts = [
        { id: 1, name: 'هاتف ذكي - موديل X', price: 1999.99, original_price: 2499.99, image: 'https://via.placeholder.com/300', rating: 4.5, reviews_count: 120 },
        { id: 2, name: 'سماعات لاسلكية', price: 349.99, original_price: 499.99, image: 'https://via.placeholder.com/300', rating: 4.2, reviews_count: 85 },
        { id: 3, name: 'ساعة ذكية', price: 699.99, original_price: 899.99, image: 'https://via.placeholder.com/300', rating: 4.7, reviews_count: 64 },
        { id: 4, name: 'لابتوب - موديل Y', price: 3999.99, original_price: 4599.99, image: 'https://via.placeholder.com/300', rating: 4.8, reviews_count: 42 }
    ];
    
    renderProducts(featuredProductsContainer, featuredProducts);
}

function loadNewProducts() {
    // محاكاة جلب المنتجات الجديدة من API
    newProducts = [
        { id: 5, name: 'سماعة بلوتوث', price: 129.99, original_price: 199.99, image: 'https://via.placeholder.com/300', rating: 4.1, reviews_count: 28 },
        { id: 6, name: 'كاميرا رقمية', price: 1299.99, original_price: 1599.99, image: 'https://via.placeholder.com/300', rating: 4.6, reviews_count: 37 },
        { id: 7, name: 'شاحن لاسلكي', price: 89.99, original_price: 129.99, image: 'https://via.placeholder.com/300', rating: 4.3, reviews_count: 52 },
        { id: 8, name: 'مكبر صوت ذكي', price: 399.99, original_price: 599.99, image: 'https://via.placeholder.com/300', rating: 4.4, reviews_count: 19 }
    ];
    
    renderProducts(newProductsContainer, newProducts);
}

function loadFavoriteProducts() {
    if (!currentUser) return;
    
    // محاكاة جلب المنتجات المفضلة من API
    favoriteProducts = [
        { id: 1, name: 'هاتف ذكي - موديل X', price: 1999.99, original_price: 2499.99, image: 'https://via.placeholder.com/300', rating: 4.5, reviews_count: 120 },
        { id: 3, name: 'ساعة ذكية', price: 699.99, original_price: 899.99, image: 'https://via.placeholder.com/300', rating: 4.7, reviews_count: 64 }
    ];
    
    renderProducts(favoriteProductsContainer, favoriteProducts, true);
}

function loadUserOrders() {
    if (!currentUser) return;
    
    // محاكاة جلب طلبات المستخدم من API
    userOrders = [
        { id: 1001, date: '2025-05-01', status: 'delivered', total: 2349.98, items: 3 },
        { id: 1002, date: '2025-05-04', status: 'shipped', total: 699.99, items: 1 },
        { id: 1003, date: '2025-05-06', status: 'processing', total: 4129.98, items: 2 }
    ];
    
    renderOrders();
}

function loadCartCount() {
    // محاكاة جلب عدد عناصر السلة من التخزين المحلي
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    cartItems = cart;
    cartCountElement.textContent = cart.length;
}

// وظائف العرض
function renderProducts(container, products, isFavorites = false) {
    if (!container) return;
    
    container.innerHTML = '';
    
    if (products.length === 0) {
        const emptyMessage = document.createElement('div');
        emptyMessage.className = 'empty-message';
        emptyMessage.textContent = isFavorites ? 'لا توجد منتجات في المفضلة' : 'لا توجد منتجات';
        container.appendChild(emptyMessage);
        return;
    }
    
    const template = document.getElementById('product-template');
    
    products.forEach(product => {
        const productElement = template.content.cloneNode(true);
        
        const productImage = productElement.querySelector('.product-image img');
        productImage.src = product.image;
        productImage.alt = product.name;
        
        const favoriteBtn = productElement.querySelector('.favorite-btn');
        if (isFavorites || favoriteProducts.some(p => p.id === product.id)) {
            favoriteBtn.classList.add('active');
            favoriteBtn.querySelector('svg').style.fill = '#F44336';
        }
        
        favoriteBtn.addEventListener('click', () => {
            toggleFavorite(product.id, favoriteBtn);
        });
        
        productElement.querySelector('.product-title').textContent = product.name;
        productElement.querySelector('.current-price').textContent = formatCurrency(product.price);
        
        if (product.original_price && product.original_price > product.price) {
            productElement.querySelector('.original-price').textContent = formatCurrency(product.original_price);
        } else {
            productElement.querySelector('.original-price').style.display = 'none';
        }
        
        const starsContainer = productElement.querySelector('.stars');
        starsContainer.appendChild(createStars(product.rating));
        
        productElement.querySelector('.rating-count').textContent = `(${product.reviews_count})`;
        
        const addToCartBtn = productElement.querySelector('.add-to-cart-btn');
        addToCartBtn.addEventListener('click', () => {
            addToCart(product);
        });
        
        container.appendChild(productElement);
    });
}

function renderOrders() {
    if (!userOrdersContainer) return;
    
    userOrdersContainer.innerHTML = '';
    
    if (userOrders.length === 0) {
        const emptyMessage = document.createElement('div');
        emptyMessage.className = 'empty-message';
        emptyMessage.textContent = 'لا توجد طلبات';
        userOrdersContainer.appendChild(emptyMessage);
        return;
    }
    
    userOrders.forEach(order => {
        const orderElement = document.createElement('div');
        orderElement.className = 'order-card';
        
        const statusClass = `status-${order.status}`;
        let statusText = '';
        
        switch (order.status) {
            case 'pending':
                statusText = 'قيد الانتظار';
                break;
            case 'processing':
                statusText = 'قيد المعالجة';
                break;
            case 'shipped':
                statusText = 'تم الشحن';
                break;
            case 'delivered':
                statusText = 'تم التوصيل';
                break;
            case 'cancelled':
                statusText = 'ملغي';
                break;
            default:
                statusText = order.status;
        }
        
        orderElement.innerHTML = `
            <div class="order-header">
                <span class="order-id">طلب #${order.id}</span>
                <span class="order-date">${order.date}</span>
            </div>
            <span class="order-status ${statusClass}">${statusText}</span>
            <div class="order-total">${formatCurrency(order.total)} (${order.items} منتجات)</div>
            <div class="order-actions">
                <button class="view-details-btn">تفاصيل الطلب</button>
                <button class="track-order-btn">تتبع الطلب</button>
            </div>
        `;
        
        orderElement.querySelector('.view-details-btn').addEventListener('click', () => {
            showToast(`عرض تفاصيل الطلب رقم ${order.id}`);
        });
        
        orderElement.querySelector('.track-order-btn').addEventListener('click', () => {
            showToast(`تتبع الطلب رقم ${order.id}`);
        });
        
        userOrdersContainer.appendChild(orderElement);
    });
}

// وظائف التفاعل
function navigateToPage(pageId) {
    pages.forEach(page => {
        page.classList.remove('active');
    });
    
    navItems.forEach(item => {
        item.classList.remove('active');
    });
    
    document.getElementById(`${pageId}-page`).classList.add('active');
    document.querySelector(`.nav-item[data-page="${pageId}"]`).classList.add('active');
}

function toggleFavorite(productId, button) {
    if (!currentUser) {
        showLoginModal();
        return;
    }
    
    const isFavorite = favoriteProducts.some(p => p.id === productId);
    
    if (isFavorite) {
        // إزالة من المفضلة
        favoriteProducts = favoriteProducts.filter(p => p.id !== productId);
        button.classList.remove('active');
        button.querySelector('svg').style.fill = 'none';
        showToast('تمت إزالة المنتج من المفضلة');
    } else {
        // إضافة إلى المفضلة
        const product = [...featuredProducts, ...newProducts].find(p => p.id === productId);
        if (product) {
            favoriteProducts.push(product);
            button.classList.add('active');
            button.querySelector('svg').style.fill = '#F44336';
            showToast('تمت إضافة المنتج إلى المفضلة');
        }
    }
    
    // تحديث صفحة المفضلة إذا كانت مفتوحة
    if (document.getElementById('favorites-page').classList.contains('active')) {
        renderProducts(favoriteProductsContainer, favoriteProducts, true);
    }
}

function addToCart(product) {
    cartItems.push({
        id: product.id,
        name: product.name,
        price: product.price,
        quantity: 1,
        image: product.image
    });
    
    localStorage.setItem('cart', JSON.stringify(cartItems));
    cartCountElement.textContent = cartItems.length;
    
    showToast('تمت إضافة المنتج إلى سلة التسوق');
}

function showLoginModal() {
    loginModal.classList.add('active');
}

function showRegisterModal() {
    registerModal.classList.add('active');
}

function closeModals() {
    loginModal.classList.remove('active');
    registerModal.classList.remove('active');
}

function login(event) {
    event.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    // محاكاة تسجيل الدخول
    if (email && password) {
        // محاكاة استجابة API
        currentUser = {
            id: 1,
            name: 'مستخدم السوق',
            email: email
        };
        
        localStorage.setItem('authToken', 'fake-jwt-token');
        
        updateUserInterface();
        loadFavoriteProducts();
        loadUserOrders();
        
        closeModals();
        showToast('تم تسجيل الدخول بنجاح');
    }
}

function register(event) {
    event.preventDefault();
    
    const name = document.getElementById('name').value;
    const email = document.getElementById('register-email').value;
    const password = document.getElementById('register-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    
    // التحقق من تطابق كلمات المرور
    if (password !== confirmPassword) {
        showToast('كلمات المرور غير متطابقة');
        return;
    }
    
    // محاكاة التسجيل
    if (name && email && password) {
        // محاكاة استجابة API
        currentUser = {
            id: 1,
            name: name,
            email: email
        };
        
        localStorage.setItem('authToken', 'fake-jwt-token');
        
        updateUserInterface();
        
        closeModals();
        showToast('تم إنشاء الحساب بنجاح');
    }
}

function logout() {
    currentUser = null;
    localStorage.removeItem('authToken');
    favoriteProducts = [];
    userOrders = [];
    
    updateUserInterface();
    
    // إعادة تحميل صفحة المفضلة إذا كانت مفتوحة
    if (document.getElementById('favorites-page').classList.contains('active')) {
        renderProducts(favoriteProductsContainer, [], true);
    }
    
    // إعادة تحميل صفحة الطلبات إذا كانت مفتوحة
    if (document.getElementById('orders-page').classList.contains('active')) {
        userOrdersContainer.innerHTML = '<div class="empty-message">لا توجد طلبات</div>';
    }
    
    showToast('تم تسجيل الخروج بنجاح');
}

function search() {
    const query = searchInput.value.trim();
    
    if (query) {
        showToast(`جاري البحث عن: ${query}`);
        // هنا يمكن إضافة منطق البحث الفعلي
    }
}

// إعداد مستمعي الأحداث
function setupEventListeners() {
    // التنقل بين الصفحات
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            const pageId = item.getAttribute('data-page');
            navigateToPage(pageId);
        });
    });
    
    // البحث
    searchBtn.addEventListener('click', search);
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            search();
        }
    });
    
    // تسجيل الدخول والتسجيل
    loginForm.addEventListener('submit', login);
    registerForm.addEventListener('submit', register);
    
    loginLink.addEventListener('click', (e) => {
        e.preventDefault();
        registerModal.classList.remove('active');
        loginModal.classList.add('active');
    });
    
    registerLink.addEventListener('click', (e) => {
        e.preventDefault();
        loginModal.classList.remove('active');
        registerModal.classList.add('active');
    });
    
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', closeModals);
    });
    
    // إغلاق النوافذ المنبثقة عند النقر خارجها
    window.addEventListener('click', (e) => {
        if (e.target === loginModal) {
            loginModal.classList.remove('active');
        }
        if (e.target === registerModal) {
            registerModal.classList.remove('active');
        }
    });
}

// تهيئة التطبيق
document.addEventListener('DOMContentLoaded', () => {
    setupEventListeners();
    loadApp();
});

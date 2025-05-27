import React, { useState, useEffect } from 'react';
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonSearchbar,
  IonList,
  IonItem,
  IonLabel,
  IonSelect,
  IonSelectOption,
  IonRange,
  IonButton,
  IonIcon,
  IonGrid,
  IonRow,
  IonCol,
  IonCard,
  IonCardHeader,
  IonCardContent,
  IonCardTitle,
  IonCardSubtitle,
  IonSkeletonText,
  IonInfiniteScroll,
  IonInfiniteScrollContent,
  IonChip,
  IonBadge,
  IonToggle,
  IonBackButton,
  IonButtons
} from '@ionic/react';
import { 
  searchOutline, 
  filterOutline, 
  pricetagOutline, 
  starOutline, 
  heartOutline, 
  cartOutline, 
  eyeOutline,
  closeCircleOutline
} from 'ionicons/icons';
import { searchProducts, getCategories } from '../services/products';
import { addToFavorites, removeFromFavorites, isFavorite } from '../services/favorites';
import { addToCart } from '../services/cart';
import './AdvancedSearch.css';

interface Category {
  id: number;
  name: string;
}

interface Product {
  id: number;
  name: string;
  price: number;
  image: string;
  rating: number;
  store_name: string;
  store_id: number;
}

const AdvancedSearch: React.FC = () => {
  const [searchTerm, setSearchTerm] = useState<string>('');
  const [categories, setCategories] = useState<Category[]>([]);
  const [selectedCategory, setSelectedCategory] = useState<number>(0);
  const [priceRange, setPriceRange] = useState<{ lower: number; upper: number }>({ lower: 0, upper: 1000 });
  const [sortBy, setSortBy] = useState<string>('relevance');
  const [inStockOnly, setInStockOnly] = useState<boolean>(false);
  const [hasDiscount, setHasDiscount] = useState<boolean>(false);
  const [minRating, setMinRating] = useState<number>(0);
  
  const [products, setProducts] = useState<Product[]>([]);
  const [page, setPage] = useState<number>(1);
  const [totalPages, setTotalPages] = useState<number>(1);
  const [loading, setLoading] = useState<boolean>(false);
  const [initialLoading, setInitialLoading] = useState<boolean>(true);
  const [productFavorites, setProductFavorites] = useState<{[key: number]: boolean}>({});
  
  const [showFilters, setShowFilters] = useState<boolean>(false);
  const [appliedFilters, setAppliedFilters] = useState<number>(0);

  useEffect(() => {
    fetchCategories();
  }, []);

  const fetchCategories = async () => {
    try {
      const data = await getCategories();
      setCategories(data);
      setInitialLoading(false);
    } catch (error) {
      console.error('Error fetching categories:', error);
      setInitialLoading(false);
    }
  };

  const handleSearch = async (reset: boolean = true) => {
    try {
      if (reset) {
        setLoading(true);
        setPage(1);
      }
      
      const searchParams = {
        term: searchTerm,
        category_id: selectedCategory,
        price_min: priceRange.lower,
        price_max: priceRange.upper,
        sort_by: sortBy,
        in_stock: inStockOnly,
        has_discount: hasDiscount,
        min_rating: minRating,
        page: reset ? 1 : page
      };
      
      const data = await searchProducts(searchParams);
      
      if (reset) {
        setProducts(data.products);
      } else {
        setProducts([...products, ...data.products]);
      }
      
      setTotalPages(data.total_pages);
      setLoading(false);
      
      // Check favorites status for new products
      checkFavoriteStatus(data.products);
    } catch (error) {
      console.error('Error searching products:', error);
      setLoading(false);
    }
  };

  const loadMore = async (event: CustomEvent<void>) => {
    if (page < totalPages) {
      setPage(prevPage => prevPage + 1);
      await handleSearch(false);
    }
    (event.target as HTMLIonInfiniteScrollElement).complete();
  };

  const checkFavoriteStatus = async (productsToCheck: Product[]) => {
    const favorites: {[key: number]: boolean} = {...productFavorites};
    
    for (const product of productsToCheck) {
      if (favorites[product.id] === undefined) {
        try {
          const isFav = await isFavorite('product', product.id);
          favorites[product.id] = isFav;
        } catch (error) {
          favorites[product.id] = false;
        }
      }
    }
    
    setProductFavorites(favorites);
  };

  const toggleFavorite = async (productId: number) => {
    try {
      if (productFavorites[productId]) {
        await removeFromFavorites('product', productId);
      } else {
        await addToFavorites('product', productId);
      }
      
      setProductFavorites({
        ...productFavorites,
        [productId]: !productFavorites[productId]
      });
    } catch (error) {
      console.error('Error toggling favorite:', error);
    }
  };

  const handleAddToCart = async (product: Product) => {
    try {
      await addToCart(product, 1);
      // يمكن إضافة إشعار هنا لإخبار المستخدم بأن المنتج تمت إضافته للسلة
    } catch (error) {
      console.error('Error adding product to cart:', error);
    }
  };

  const applyFilters = () => {
    // حساب عدد الفلاتر المطبقة
    let count = 0;
    if (selectedCategory > 0) count++;
    if (priceRange.lower > 0 || priceRange.upper < 1000) count++;
    if (sortBy !== 'relevance') count++;
    if (inStockOnly) count++;
    if (hasDiscount) count++;
    if (minRating > 0) count++;
    
    setAppliedFilters(count);
    setShowFilters(false);
    handleSearch();
  };

  const resetFilters = () => {
    setSelectedCategory(0);
    setPriceRange({ lower: 0, upper: 1000 });
    setSortBy('relevance');
    setInStockOnly(false);
    setHasDiscount(false);
    setMinRating(0);
    setAppliedFilters(0);
  };

  const renderProductSkeletons = () => {
    return Array(4).fill(0).map((_, index) => (
      <IonCol size="6" key={index}>
        <IonCard className="product-card">
          <div className="product-image-container">
            <IonSkeletonText animated style={{ width: '100%', height: '150px' }} />
          </div>
          <IonCardHeader>
            <IonCardTitle>
              <IonSkeletonText animated style={{ width: '80%' }} />
            </IonCardTitle>
            <IonCardSubtitle>
              <IonSkeletonText animated style={{ width: '60%' }} />
            </IonCardSubtitle>
          </IonCardHeader>
          <IonCardContent>
            <IonSkeletonText animated style={{ width: '40%' }} />
            <div className="product-actions">
              <IonSkeletonText animated style={{ width: '100%', height: '30px' }} />
            </div>
          </IonCardContent>
        </IonCard>
      </IonCol>
    ));
  };

  const renderProducts = () => {
    if (initialLoading) {
      return renderProductSkeletons();
    }

    if (products.length === 0 && !loading) {
      return (
        <div className="empty-results">
          <IonIcon icon={searchOutline} className="empty-icon" />
          <h3>لا توجد نتائج</h3>
          <p>لم يتم العثور على منتجات تطابق معايير البحث. حاول تغيير معايير البحث أو استخدم كلمات مفتاحية مختلفة.</p>
          <IonButton expand="block" color="warning" onClick={resetFilters}>
            إعادة ضبط الفلاتر
          </IonButton>
        </div>
      );
    }

    return products.map(product => (
      <IonCol size="6" key={product.id}>
        <IonCard className="product-card">
          <div className="product-image-container">
            <img src={product.image} alt={product.name} className="product-image" />
            <div 
              className={`favorite-button ${productFavorites[product.id] ? 'active' : ''}`}
              onClick={() => toggleFavorite(product.id)}
            >
              <IonIcon icon={heartOutline} className="favorite-icon" />
            </div>
          </div>
          <IonCardHeader>
            <IonCardTitle>{product.name}</IonCardTitle>
            <IonCardSubtitle>{product.store_name}</IonCardSubtitle>
          </IonCardHeader>
          <IonCardContent>
            <div className="product-rating">
              {Array(5).fill(0).map((_, i) => (
                <span key={i} className={i < Math.floor(product.rating) ? 'star filled' : 'star'}>★</span>
              ))}
              <span className="rating-text">{product.rating}</span>
            </div>
            <div className="product-price">
              {product.price} ر.س
            </div>
            <div className="product-actions">
              <IonButton 
                fill="clear" 
                size="small" 
                routerLink={`/product/${product.id}`}
              >
                <IonIcon slot="icon-only" icon={eyeOutline} />
              </IonButton>
              <IonButton 
                fill="solid" 
                color="warning" 
                size="small" 
                onClick={() => handleAddToCart(product)}
              >
                <IonIcon slot="icon-only" icon={cartOutline} />
              </IonButton>
            </div>
          </IonCardContent>
        </IonCard>
      </IonCol>
    ));
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonButtons slot="start">
            <IonBackButton defaultHref="/products" />
          </IonButtons>
          <IonTitle>البحث المتقدم</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        <div className="search-container">
          <div className="search-bar-container">
            <IonSearchbar
              value={searchTerm}
              onIonChange={e => setSearchTerm(e.detail.value!)}
              placeholder="ابحث عن منتجات..."
              showCancelButton="never"
              animated
              className="search-bar"
              onKeyPress={e => e.key === 'Enter' && handleSearch()}
            />
            <IonButton 
              className="filter-button" 
              fill="clear" 
              onClick={() => setShowFilters(!showFilters)}
            >
              <IonIcon icon={filterOutline} />
              {appliedFilters > 0 && (
                <IonBadge color="warning" className="filter-badge">{appliedFilters}</IonBadge>
              )}
            </IonButton>
          </div>

          {showFilters && (
            <div className="filters-container">
              <h4 className="filter-title">
                <IonIcon icon={filterOutline} />
                فلترة النتائج
              </h4>
              
              <div className="filter-section">
                <IonLabel>الفئة</IonLabel>
                <IonSelect
                  value={selectedCategory}
                  placeholder="جميع الفئات"
                  onIonChange={e => setSelectedCategory(e.detail.value)}
                  interface="popover"
                >
                  <IonSelectOption value={0}>جميع الفئات</IonSelectOption>
                  {categories.map(category => (
                    <IonSelectOption key={category.id} value={category.id}>
                      {category.name}
                    </IonSelectOption>
                  ))}
                </IonSelect>
              </div>
              
              <div className="filter-section">
                <IonLabel>نطاق السعر: {priceRange.lower} - {priceRange.upper} ر.س</IonLabel>
                <IonRange
                  dual-knobs
                  min={0}
                  max={1000}
                  step={10}
                  value={priceRange}
                  onIonChange={e => setPriceRange(e.detail.value as any)}
                />
              </div>
              
              <div className="filter-section">
                <IonLabel>ترتيب حسب</IonLabel>
                <IonSelect
                  value={sortBy}
                  onIonChange={e => setSortBy(e.detail.value)}
                  interface="popover"
                >
                  <IonSelectOption value="relevance">الصلة</IonSelectOption>
                  <IonSelectOption value="price_asc">السعر: من الأقل للأعلى</IonSelectOption>
                  <IonSelectOption value="price_desc">السعر: من الأعلى للأقل</IonSelectOption>
                  <IonSelectOption value="rating">التقييم</IonSelectOption>
                  <IonSelectOption value="newest">الأحدث</IonSelectOption>
                </IonSelect>
              </div>
              
              <div className="filter-section toggle-filters">
                <div className="toggle-filter">
                  <IonLabel>متوفر في المخزون فقط</IonLabel>
                  <IonToggle
                    checked={inStockOnly}
                    onIonChange={e => setInStockOnly(e.detail.checked)}
                  />
                </div>
                
                <div className="toggle-filter">
                  <IonLabel>العروض والخصومات فقط</IonLabel>
                  <IonToggle
                    checked={hasDiscount}
                    onIonChange={e => setHasDiscount(e.detail.checked)}
                  />
                </div>
              </div>
              
              <div className="filter-section">
                <IonLabel>الحد الأدنى للتقييم: {minRating} نجوم</IonLabel>
                <IonRange
                  min={0}
                  max={5}
                  step={1}
                  snaps={true}
                  pin={true}
                  value={minRating}
                  onIonChange={e => setMinRating(e.detail.value as number)}
                />
              </div>
              
              <div className="filter-actions">
                <IonButton expand="block" onClick={applyFilters} color="warning">
                  تطبيق الفلاتر
                </IonButton>
                <IonButton expand="block" onClick={resetFilters} fill="outline" color="medium">
                  إعادة ضبط
                </IonButton>
              </div>
            </div>
          )}

          {appliedFilters > 0 && !showFilters && (
            <div className="applied-filters">
              {selectedCategory > 0 && (
                <IonChip color="warning">
                  <IonLabel>
                    {categories.find(c => c.id === selectedCategory)?.name}
                  </IonLabel>
                  <IonIcon icon={closeCircleOutline} onClick={() => {
                    setSelectedCategory(0);
                    setAppliedFilters(prev => prev - 1);
                  }} />
                </IonChip>
              )}
              
              {(priceRange.lower > 0 || priceRange.upper < 1000) && (
                <IonChip color="warning">
                  <IonLabel>
                    السعر: {priceRange.lower} - {priceRange.upper} ر.س
                  </IonLabel>
                  <IonIcon icon={closeCircleOutline} onClick={() => {
                    setPriceRange({ lower: 0, upper: 1000 });
                    setAppliedFilters(prev => prev - 1);
                  }} />
                </IonChip>
              )}
              
              {sortBy !== 'relevance' && (
                <IonChip color="warning">
                  <IonLabel>
                    ترتيب: {
                      sortBy === 'price_asc' ? 'السعر (تصاعدي)' :
                      sortBy === 'price_desc' ? 'السعر (تنازلي)' :
                      sortBy === 'rating' ? 'التقييم' :
                      'الأحدث'
                    }
                  </IonLabel>
                  <IonIcon icon={closeCircleOutline} onClick={() => {
                    setSortBy('relevance');
                    setAppliedFilters(prev => prev - 1);
                  }} />
                </IonChip>
              )}
              
              {inStockOnly && (
                <IonChip color="warning">
                  <IonLabel>متوفر في المخزون</IonLabel>
                  <IonIcon icon={closeCircleOutline} onClick={() => {
                    setInStockOnly(false);
                    setAppliedFilters(prev => prev - 1);
                  }} />
                </IonChip>
              )}
              
              {hasDiscount && (
                <IonChip color="warning">
                  <IonLabel>العروض والخصومات</IonLabel>
                  <IonIcon icon={closeCircleOutline} onClick={() => {
                    setHasDiscount(false);
                    setAppliedFilters(prev => prev - 1);
                  }} />
                </IonChip>
              )}
              
              {minRating > 0 && (
                <IonChip color="warning">
                  <IonLabel>
                    التقييم: {minRating}+ نجوم
                  </IonLabel>
                  <IonIcon icon={closeCircleOutline} onClick={() => {
                    setMinRating(0);
                    setAppliedFilters(prev => prev - 1);
                  }} />
                </IonChip>
              )}
              
              <IonChip color="medium" onClick={resetFilters}>
                <IonLabel>إعادة ضبط الكل</IonLabel>
                <IonIcon icon={closeCircleOutline} />
              </IonChip>
            </div>
          )}

          <IonButton 
            expand="block" 
            color="warning" 
            className="search-button"
            onClick={() => handleSearch()}
          >
            <IonIcon slot="start" icon={searchOutline} />
            بحث
          </IonButton>

          <div className="results-container">
            <IonGrid>
              <IonRow>
                {loading && page === 1 ? renderProductSkeletons() : renderProducts()}
                {loading && page > 1 && renderProductSkeletons()}
              </IonRow>
            </IonGrid>
            
            <IonInfiniteScroll
              threshold="100px"
              disabled={page >= totalPages || initialLoading}
              onIonInfinite={loadMore}
            >
              <IonInfiniteScrollContent
                loadingSpinner="bubbles"
                loadingText="جاري تحميل المزيد من المنتجات..."
              ></IonInfiniteScrollContent>
            </IonInfiniteScroll>
          </div>
        </div>
      </IonContent>
    </IonPage>
  );
};

export default AdvancedSearch;

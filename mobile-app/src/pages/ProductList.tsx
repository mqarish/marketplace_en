import React, { useEffect, useState } from 'react';
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonSearchbar,
  IonGrid,
  IonRow,
  IonCol,
  IonCard,
  IonCardHeader,
  IonCardSubtitle,
  IonCardTitle,
  IonCardContent,
  IonButton,
  IonImg,
  IonInfiniteScroll,
  IonInfiniteScrollContent,
  IonSelect,
  IonSelectOption,
  IonItem,
  IonLabel,
  IonSkeletonText,
  IonChip,
  IonIcon,
  IonBadge
} from '@ionic/react';
import { filterOutline, optionsOutline, heartOutline, starOutline } from 'ionicons/icons';
import { getProducts, Product, ProductFilter } from '../services/products';
import { addToCart } from '../services/cart';
import './ProductList.css';

const ProductList: React.FC = () => {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [searchTerm, setSearchTerm] = useState('');
  const [filters, setFilters] = useState<ProductFilter>({
    order_by: 'created_at',
    order_dir: 'DESC',
    limit: 12
  });
  const [showFilters, setShowFilters] = useState(false);

  // جلب المنتجات
  const fetchProducts = async (newPage = 1, newFilters = filters) => {
    try {
      setLoading(true);
      const response = await getProducts({
        ...newFilters,
        page: newPage,
        search: searchTerm
      });
      
      if (newPage === 1) {
        setProducts(response.products);
      } else {
        setProducts([...products, ...response.products]);
      }
      
      setTotalPages(response.pagination.total_pages);
      setLoading(false);
    } catch (error) {
      console.error('Error fetching products:', error);
      setLoading(false);
    }
  };

  // تحميل المنتجات عند تحميل الصفحة
  useEffect(() => {
    fetchProducts();
  }, []);

  // إعادة تحميل المنتجات عند تغيير الفلاتر
  useEffect(() => {
    setPage(1);
    fetchProducts(1, filters);
  }, [filters, searchTerm]);

  // دالة للتعامل مع البحث
  const handleSearch = (e: CustomEvent) => {
    setSearchTerm(e.detail.value);
  };

  // دالة للتعامل مع تحميل المزيد من المنتجات
  const loadMoreProducts = async (event: CustomEvent<void>) => {
    if (page < totalPages) {
      const nextPage = page + 1;
      await fetchProducts(nextPage);
      setPage(nextPage);
    }
    (event.target as HTMLIonInfiniteScrollElement).complete();
  };

  // دالة لتغيير الترتيب
  const handleSortChange = (e: CustomEvent) => {
    const value = e.detail.value;
    let orderBy = 'created_at';
    let orderDir = 'DESC';

    switch (value) {
      case 'newest':
        orderBy = 'created_at';
        orderDir = 'DESC';
        break;
      case 'price_low':
        orderBy = 'price';
        orderDir = 'ASC';
        break;
      case 'price_high':
        orderBy = 'price';
        orderDir = 'DESC';
        break;
      case 'rating':
        orderBy = 'avg_rating';
        orderDir = 'DESC';
        break;
      case 'popularity':
        orderBy = 'likes_count';
        orderDir = 'DESC';
        break;
    }

    setFilters({
      ...filters,
      order_by: orderBy,
      order_dir: orderDir as 'ASC' | 'DESC'
    });
  };

  // دالة لإضافة منتج إلى سلة التسوق
  const handleAddToCart = (product: Product, event: React.MouseEvent) => {
    event.preventDefault();
    event.stopPropagation();
    addToCart(product, 1);
    // يمكن إضافة إشعار هنا
  };

  // دالة لتبديل عرض الفلاتر
  const toggleFilters = () => {
    setShowFilters(!showFilters);
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonTitle>المنتجات</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        {/* قسم العنوان (Hero Section) */}
        <div className="hero-section product-hero">
          <h1>تسوق المنتجات</h1>
          <p>اكتشف منتجات متنوعة من مختلف المتاجر</p>
        </div>

        {/* قسم البحث والفلاتر */}
        <div className="search-container ion-padding">
          <IonSearchbar
            placeholder="ابحث عن منتجات..."
            value={searchTerm}
            onIonChange={handleSearch}
            debounce={500}
            className="product-searchbar"
          />
          
          <div className="filter-options">
            <IonChip color="warning" onClick={toggleFilters}>
              <IonIcon icon={filterOutline} />
              <IonLabel>الفلاتر</IonLabel>
            </IonChip>
            
            <IonSelect
              interface="popover"
              placeholder="الترتيب"
              onIonChange={handleSortChange}
              className="sort-select"
            >
              <IonSelectOption value="newest">الأحدث</IonSelectOption>
              <IonSelectOption value="price_low">السعر: من الأقل للأعلى</IonSelectOption>
              <IonSelectOption value="price_high">السعر: من الأعلى للأقل</IonSelectOption>
              <IonSelectOption value="rating">التقييم</IonSelectOption>
              <IonSelectOption value="popularity">الأكثر شعبية</IonSelectOption>
            </IonSelect>
          </div>
          
          {showFilters && (
            <div className="filters-panel">
              {/* يمكن إضافة المزيد من الفلاتر هنا */}
              <IonItem>
                <IonLabel>السعر</IonLabel>
                <IonSelect
                  placeholder="نطاق السعر"
                  onIonChange={(e) => {
                    const value = e.detail.value;
                    let minPrice, maxPrice;
                    
                    switch (value) {
                      case 'under_50':
                        minPrice = 0;
                        maxPrice = 50;
                        break;
                      case '50_100':
                        minPrice = 50;
                        maxPrice = 100;
                        break;
                      case '100_500':
                        minPrice = 100;
                        maxPrice = 500;
                        break;
                      case 'over_500':
                        minPrice = 500;
                        maxPrice = undefined;
                        break;
                      default:
                        minPrice = undefined;
                        maxPrice = undefined;
                    }
                    
                    setFilters({
                      ...filters,
                      min_price: minPrice,
                      max_price: maxPrice
                    });
                  }}
                >
                  <IonSelectOption value="all">الكل</IonSelectOption>
                  <IonSelectOption value="under_50">أقل من 50 ريال</IonSelectOption>
                  <IonSelectOption value="50_100">50 - 100 ريال</IonSelectOption>
                  <IonSelectOption value="100_500">100 - 500 ريال</IonSelectOption>
                  <IonSelectOption value="over_500">أكثر من 500 ريال</IonSelectOption>
                </IonSelect>
              </IonItem>
            </div>
          )}
        </div>

        {/* عرض المنتجات */}
        <div className="ion-padding">
          <IonGrid>
            <IonRow>
              {loading && products.length === 0
                ? [...Array(6)].map((_, index) => (
                    <IonCol size="6" sizeMd="4" key={index}>
                      <IonCard>
                        <div className="product-image-placeholder">
                          <IonSkeletonText animated style={{ height: '100%' }} />
                        </div>
                        <IonCardHeader>
                          <IonSkeletonText animated style={{ width: '70%' }} />
                          <IonSkeletonText animated style={{ width: '40%' }} />
                        </IonCardHeader>
                        <IonCardContent>
                          <IonSkeletonText animated style={{ width: '50%' }} />
                          <IonSkeletonText animated style={{ width: '80%' }} />
                        </IonCardContent>
                      </IonCard>
                    </IonCol>
                  ))
                : products.map((product) => (
                    <IonCol size="6" sizeMd="4" key={product.id}>
                      <IonCard className="product-card" routerLink={`/product/${product.id}`}>
                        <div className="product-image-container">
                          <IonImg src={product.image} alt={product.name} className="product-image" />
                          {product.avg_rating > 0 && (
                            <div className="product-rating-badge">
                              <IonIcon icon={starOutline} />
                              <span>{product.avg_rating.toFixed(1)}</span>
                            </div>
                          )}
                        </div>
                        <IonCardHeader>
                          <IonCardSubtitle>{product.store_name}</IonCardSubtitle>
                          <IonCardTitle>{product.name}</IonCardTitle>
                        </IonCardHeader>
                        <IonCardContent>
                          <div className="product-price">{product.price} ريال</div>
                          <IonButton 
                            expand="block" 
                            color="warning" 
                            onClick={(e) => handleAddToCart(product, e)}
                          >
                            إضافة للسلة
                          </IonButton>
                        </IonCardContent>
                      </IonCard>
                    </IonCol>
                  ))}
            </IonRow>
          </IonGrid>

          {products.length === 0 && !loading && (
            <div className="no-results">
              <h3>لا توجد منتجات</h3>
              <p>جرب تغيير معايير البحث أو الفلاتر</p>
            </div>
          )}

          <IonInfiniteScroll
            onIonInfinite={loadMoreProducts}
            threshold="100px"
            disabled={page >= totalPages || loading}
          >
            <IonInfiniteScrollContent
              loadingSpinner="bubbles"
              loadingText="جاري تحميل المزيد من المنتجات..."
            />
          </IonInfiniteScroll>
        </div>
      </IonContent>
    </IonPage>
  );
};

export default ProductList;

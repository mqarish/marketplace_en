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
import { filterOutline, optionsOutline, starOutline, locationOutline } from 'ionicons/icons';
import { getStores, Store, StoreFilter } from '../services/stores';
import './StoreList.css';

const StoreList: React.FC = () => {
  const [stores, setStores] = useState<Store[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [searchTerm, setSearchTerm] = useState('');
  const [filters, setFilters] = useState<StoreFilter>({
    order_by: 'created_at',
    order_dir: 'DESC',
    limit: 12
  });
  const [showFilters, setShowFilters] = useState(false);

  // جلب المتاجر
  const fetchStores = async (newPage = 1, newFilters = filters) => {
    try {
      setLoading(true);
      const response = await getStores({
        ...newFilters,
        page: newPage,
        search: searchTerm
      });
      
      if (newPage === 1) {
        setStores(response.stores);
      } else {
        setStores([...stores, ...response.stores]);
      }
      
      setTotalPages(response.pagination.total_pages);
      setLoading(false);
    } catch (error) {
      console.error('Error fetching stores:', error);
      setLoading(false);
    }
  };

  // تحميل المتاجر عند تحميل الصفحة
  useEffect(() => {
    fetchStores();
  }, []);

  // إعادة تحميل المتاجر عند تغيير الفلاتر
  useEffect(() => {
    setPage(1);
    fetchStores(1, filters);
  }, [filters, searchTerm]);

  // دالة للتعامل مع البحث
  const handleSearch = (e: CustomEvent) => {
    setSearchTerm(e.detail.value);
  };

  // دالة للتعامل مع تحميل المزيد من المتاجر
  const loadMoreStores = async (event: CustomEvent<void>) => {
    if (page < totalPages) {
      const nextPage = page + 1;
      await fetchStores(nextPage);
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
      case 'rating':
        orderBy = 'avg_rating';
        orderDir = 'DESC';
        break;
      case 'products':
        orderBy = 'products_count';
        orderDir = 'DESC';
        break;
      case 'name_asc':
        orderBy = 'name';
        orderDir = 'ASC';
        break;
      case 'name_desc':
        orderBy = 'name';
        orderDir = 'DESC';
        break;
    }

    setFilters({
      ...filters,
      order_by: orderBy,
      order_dir: orderDir as 'ASC' | 'DESC'
    });
  };

  // دالة لتبديل عرض الفلاتر
  const toggleFilters = () => {
    setShowFilters(!showFilters);
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonTitle>المتاجر</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        {/* قسم العنوان (Hero Section) */}
        <div className="hero-section store-hero">
          <h1>تصفح المتاجر</h1>
          <p>اكتشف متاجر متنوعة وتسوق من أفضل البائعين</p>
        </div>

        {/* قسم البحث والفلاتر */}
        <div className="search-container ion-padding">
          <IonSearchbar
            placeholder="ابحث عن متاجر..."
            value={searchTerm}
            onIonChange={handleSearch}
            debounce={500}
            className="store-searchbar"
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
              <IonSelectOption value="rating">التقييم</IonSelectOption>
              <IonSelectOption value="products">عدد المنتجات</IonSelectOption>
              <IonSelectOption value="name_asc">الاسم: أ-ي</IonSelectOption>
              <IonSelectOption value="name_desc">الاسم: ي-أ</IonSelectOption>
            </IonSelect>
          </div>
          
          {showFilters && (
            <div className="filters-panel">
              <IonItem>
                <IonLabel>الموقع</IonLabel>
                <IonInput 
                  placeholder="أدخل الموقع للبحث" 
                  onIonChange={(e) => {
                    setFilters({
                      ...filters,
                      location: e.detail.value || undefined
                    });
                  }}
                />
              </IonItem>
            </div>
          )}
        </div>

        {/* عرض المتاجر */}
        <div className="ion-padding">
          <IonGrid>
            <IonRow>
              {loading && stores.length === 0
                ? [...Array(6)].map((_, index) => (
                    <IonCol size="6" sizeMd="4" key={index}>
                      <IonCard className="store-card">
                        <div className="store-logo-placeholder">
                          <IonSkeletonText animated style={{ width: '100%', height: '100%' }} />
                        </div>
                        <IonCardHeader>
                          <IonSkeletonText animated style={{ width: '70%' }} />
                          <IonSkeletonText animated style={{ width: '40%' }} />
                        </IonCardHeader>
                        <IonCardContent>
                          <IonSkeletonText animated style={{ width: '100%', height: '30px' }} />
                        </IonCardContent>
                      </IonCard>
                    </IonCol>
                  ))
                : stores.map((store) => (
                    <IonCol size="6" sizeMd="4" key={store.id}>
                      <IonCard className="store-card" routerLink={`/store/${store.id}`}>
                        <div className="store-logo-container">
                          <IonImg src={store.logo} alt={store.name} className="store-logo" />
                          {store.avg_rating > 0 && (
                            <div className="store-rating-badge">
                              <IonIcon icon={starOutline} />
                              <span>{store.avg_rating.toFixed(1)}</span>
                            </div>
                          )}
                        </div>
                        <IonCardHeader>
                          <IonCardTitle>{store.name}</IonCardTitle>
                          <IonCardSubtitle className="store-location">
                            <IonIcon icon={locationOutline} />
                            <span>{store.location}</span>
                          </IonCardSubtitle>
                        </IonCardHeader>
                        <IonCardContent>
                          <div className="store-products-count">
                            <span>{store.products_count} منتج</span>
                          </div>
                          <IonButton 
                            expand="block" 
                            color="warning"
                            className="visit-store-btn"
                          >
                            زيارة المتجر
                          </IonButton>
                        </IonCardContent>
                      </IonCard>
                    </IonCol>
                  ))}
            </IonRow>
          </IonGrid>

          {stores.length === 0 && !loading && (
            <div className="no-results">
              <h3>لا توجد متاجر</h3>
              <p>جرب تغيير معايير البحث أو الفلاتر</p>
            </div>
          )}

          <IonInfiniteScroll
            onIonInfinite={loadMoreStores}
            threshold="100px"
            disabled={page >= totalPages || loading}
          >
            <IonInfiniteScrollContent
              loadingSpinner="bubbles"
              loadingText="جاري تحميل المزيد من المتاجر..."
            />
          </IonInfiniteScroll>
        </div>
      </IonContent>
    </IonPage>
  );
};

export default StoreList;

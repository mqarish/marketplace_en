import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router';
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonBackButton,
  IonButtons,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonItem,
  IonLabel,
  IonGrid,
  IonRow,
  IonCol,
  IonImg,
  IonIcon,
  IonButton,
  IonChip,
  IonSkeletonText,
  IonText,
  IonBadge
} from '@ionic/react';
import { 
  callOutline, 
  locationOutline, 
  timeOutline, 
  starOutline, 
  star, 
  gridOutline,
  cartOutline
} from 'ionicons/icons';
import { getStoreById, StoreDetailResponse } from '../services/stores';
import { addToCart } from '../services/cart';
import './StoreDetail.css';

const StoreDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [store, setStore] = useState<StoreDetailResponse | null>(null);
  const [loading, setLoading] = useState(true);

  // جلب بيانات المتجر
  useEffect(() => {
    const fetchStore = async () => {
      try {
        setLoading(true);
        const data = await getStoreById(parseInt(id));
        setStore(data);
        setLoading(false);
      } catch (error) {
        console.error('Error fetching store:', error);
        setLoading(false);
      }
    };

    fetchStore();
  }, [id]);

  // دالة لإضافة منتج إلى سلة التسوق
  const handleAddToCart = (productId: number, event: React.MouseEvent) => {
    event.preventDefault();
    event.stopPropagation();
    
    if (store) {
      const product = store.products.find(p => p.id === productId);
      if (product) {
        addToCart(product, 1);
        // يمكن إضافة إشعار هنا
      }
    }
  };

  // دالة لعرض نجوم التقييم
  const renderStars = (value: number, max: number = 5) => {
    return Array.from({ length: max }).map((_, index) => (
      <IonIcon
        key={index}
        icon={index < value ? star : starOutline}
        color={index < value ? 'warning' : 'medium'}
      />
    ));
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonButtons slot="start">
            <IonBackButton defaultHref="/stores" />
          </IonButtons>
          <IonTitle>{loading ? 'تفاصيل المتجر' : store?.name}</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        {loading ? (
          <div className="loading-container">
            <div className="store-header-placeholder">
              <IonSkeletonText animated style={{ height: '100%' }} />
            </div>
            <div className="ion-padding">
              <IonSkeletonText animated style={{ width: '70%', height: '30px', margin: '10px 0' }} />
              <IonSkeletonText animated style={{ width: '50%', height: '20px', margin: '10px 0' }} />
              <IonSkeletonText animated style={{ width: '90%', height: '100px', margin: '20px 0' }} />
            </div>
          </div>
        ) : store ? (
          <>
            {/* رأس المتجر */}
            <div className="store-header">
              <div className="store-logo-wrapper">
                <div className="store-logo-container">
                  <IonImg src={store.logo} alt={store.name} className="store-logo" />
                </div>
              </div>
              <h1>{store.name}</h1>
              <div className="store-rating">
                {renderStars(store.avg_rating || 0)}
                <span>({store.avg_rating ? store.avg_rating.toFixed(1) : 'جديد'})</span>
              </div>
            </div>

            {/* معلومات المتجر */}
            <div className="ion-padding store-info">
              <IonCard className="store-details-card">
                <IonCardHeader>
                  <IonCardTitle>معلومات المتجر</IonCardTitle>
                </IonCardHeader>
                <IonCardContent>
                  <div className="store-detail-item">
                    <IonIcon icon={locationOutline} color="medium" />
                    <span>{store.location}</span>
                  </div>
                  
                  {store.phone && (
                    <div className="store-detail-item">
                      <IonIcon icon={callOutline} color="medium" />
                      <span>{store.phone}</span>
                    </div>
                  )}
                  
                  <div className="store-detail-item">
                    <IonIcon icon={timeOutline} color="medium" />
                    <span>انضم منذ {new Date(store.created_at).toLocaleDateString('ar-SA')}</span>
                  </div>
                  
                  <div className="store-detail-item">
                    <IonIcon icon={gridOutline} color="medium" />
                    <span>{store.products_count} منتج</span>
                  </div>
                  
                  {store.description && (
                    <div className="store-description">
                      <h3>عن المتجر</h3>
                      <p>{store.description}</p>
                    </div>
                  )}
                </IonCardContent>
              </IonCard>

              {/* منتجات المتجر */}
              <div className="store-products-section">
                <div className="section-header">
                  <h2>منتجات المتجر</h2>
                  <IonButton 
                    fill="clear" 
                    size="small" 
                    routerLink={`/products?store=${store.id}`}
                  >
                    عرض الكل
                  </IonButton>
                </div>
                
                {store.products && store.products.length > 0 ? (
                  <IonGrid>
                    <IonRow>
                      {store.products.map((product) => (
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
                              <IonCardTitle>{product.name}</IonCardTitle>
                            </IonCardHeader>
                            <IonCardContent>
                              <div className="product-price">{product.price} ريال</div>
                              <IonButton 
                                expand="block" 
                                color="warning" 
                                onClick={(e) => handleAddToCart(product.id, e)}
                              >
                                <IonIcon slot="start" icon={cartOutline} />
                                إضافة للسلة
                              </IonButton>
                            </IonCardContent>
                          </IonCard>
                        </IonCol>
                      ))}
                    </IonRow>
                  </IonGrid>
                ) : (
                  <div className="no-products">
                    <p>لا توجد منتجات متاحة حالياً</p>
                  </div>
                )}
              </div>
            </div>
          </>
        ) : (
          <div className="error-container ion-padding">
            <IonText color="danger">
              <h2>عذراً، لم يتم العثور على المتجر</h2>
              <p>قد يكون المتجر غير متوفر أو تم حذفه.</p>
            </IonText>
            <IonButton routerLink="/stores" expand="block">
              العودة إلى المتاجر
            </IonButton>
          </div>
        )}
      </IonContent>
    </IonPage>
  );
};

export default StoreDetail;

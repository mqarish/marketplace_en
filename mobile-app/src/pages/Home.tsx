import React, { useEffect, useState } from 'react';
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonCard,
  IonCardHeader,
  IonCardSubtitle,
  IonCardTitle,
  IonCardContent,
  IonItem,
  IonLabel,
  IonButton,
  IonSlides,
  IonSlide,
  IonGrid,
  IonRow,
  IonCol,
  IonSkeletonText,
  IonImg
} from '@ionic/react';
import { getProducts, Product } from '../services/products';
import { getStores, Store } from '../services/stores';
import { addToCart } from '../services/cart';
import './Home.css';

const Home: React.FC = () => {
  const [featuredProducts, setFeaturedProducts] = useState<Product[]>([]);
  const [topStores, setTopStores] = useState<Store[]>([]);
  const [loading, setLoading] = useState(true);

  // إعدادات السلايدر
  const slideOpts = {
    initialSlide: 0,
    speed: 400,
    autoplay: true,
    loop: true
  };

  useEffect(() => {
    const fetchData = async () => {
      try {
        // جلب المنتجات المميزة
        const productsResponse = await getProducts({
          order_by: 'avg_rating',
          order_dir: 'DESC',
          limit: 10
        });
        setFeaturedProducts(productsResponse.products);

        // جلب أفضل المتاجر
        const storesResponse = await getStores({
          order_by: 'avg_rating',
          order_dir: 'DESC',
          limit: 5
        });
        setTopStores(storesResponse.stores);

        setLoading(false);
      } catch (error) {
        console.error('Error fetching data:', error);
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  // دالة لإضافة منتج إلى سلة التسوق
  const handleAddToCart = (product: Product) => {
    addToCart(product, 1);
    // يمكن إضافة إشعار هنا
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonTitle>سوق</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen className="ion-padding">
        {/* قسم العنوان (Hero Section) */}
        <div className="hero-section">
          <h1>مرحباً بك في سوق</h1>
          <p>اكتشف آلاف المنتجات من مئات المتاجر</p>
        </div>

        {/* قسم المنتجات المميزة */}
        <IonItem lines="none" className="section-header">
          <IonLabel>
            <h2>المنتجات المميزة</h2>
          </IonLabel>
          <IonButton slot="end" fill="clear" routerLink="/products">
            عرض الكل
          </IonButton>
        </IonItem>

        {loading ? (
          <IonSlides options={slideOpts}>
            {[...Array(5)].map((_, index) => (
              <IonSlide key={index}>
                <IonCard>
                  <div className="product-image-placeholder">
                    <IonSkeletonText animated style={{ height: '100%' }} />
                  </div>
                  <IonCardHeader>
                    <IonSkeletonText animated style={{ width: '70%' }} />
                    <IonSkeletonText animated style={{ width: '40%' }} />
                  </IonCardHeader>
                </IonCard>
              </IonSlide>
            ))}
          </IonSlides>
        ) : (
          <IonSlides options={slideOpts} className="featured-products-slider">
            {featuredProducts.map((product) => (
              <IonSlide key={product.id}>
                <IonCard className="product-card" routerLink={`/product/${product.id}`}>
                  <div className="product-image-container">
                    <IonImg src={product.image} alt={product.name} className="product-image" />
                  </div>
                  <IonCardHeader>
                    <IonCardSubtitle>{product.store_name}</IonCardSubtitle>
                    <IonCardTitle>{product.name}</IonCardTitle>
                  </IonCardHeader>
                  <IonCardContent>
                    <div className="product-price">{product.price} ريال</div>
                    <div className="product-rating">
                      <span>★</span> {product.avg_rating ? product.avg_rating.toFixed(1) : 'جديد'}
                    </div>
                    <IonButton 
                      expand="block" 
                      color="warning" 
                      onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        handleAddToCart(product);
                      }}
                    >
                      إضافة للسلة
                    </IonButton>
                  </IonCardContent>
                </IonCard>
              </IonSlide>
            ))}
          </IonSlides>
        )}

        {/* قسم أفضل المتاجر */}
        <IonItem lines="none" className="section-header">
          <IonLabel>
            <h2>أفضل المتاجر</h2>
          </IonLabel>
          <IonButton slot="end" fill="clear" routerLink="/stores">
            عرض الكل
          </IonButton>
        </IonItem>

        <IonGrid>
          <IonRow>
            {loading
              ? [...Array(4)].map((_, index) => (
                  <IonCol size="6" key={index}>
                    <IonCard>
                      <div className="store-logo-placeholder">
                        <IonSkeletonText animated style={{ height: '100%' }} />
                      </div>
                      <IonCardHeader>
                        <IonSkeletonText animated style={{ width: '80%' }} />
                      </IonCardHeader>
                    </IonCard>
                  </IonCol>
                ))
              : topStores.map((store) => (
                  <IonCol size="6" key={store.id}>
                    <IonCard className="store-card" routerLink={`/store/${store.id}`}>
                      <div className="store-logo-container">
                        <IonImg src={store.logo} alt={store.name} className="store-logo" />
                      </div>
                      <IonCardHeader>
                        <IonCardTitle>{store.name}</IonCardTitle>
                        <IonCardSubtitle>
                          <span>★</span> {store.avg_rating ? store.avg_rating.toFixed(1) : 'جديد'}
                        </IonCardSubtitle>
                      </IonCardHeader>
                    </IonCard>
                  </IonCol>
                ))}
          </IonRow>
        </IonGrid>
      </IonContent>
    </IonPage>
  );
};

export default Home;

import React, { useState, useEffect } from 'react';
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonGrid,
  IonRow,
  IonCol,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardSubtitle,
  IonCardContent,
  IonButton,
  IonIcon,
  IonSegment,
  IonSegmentButton,
  IonLabel,
  IonSkeletonText,
  IonText,
  IonRefresher,
  IonRefresherContent
} from '@ionic/react';
import { 
  heartOutline, 
  heartDislikeOutline, 
  cartOutline, 
  eyeOutline, 
  storefront
} from 'ionicons/icons';
import { getFavoriteProducts, getFavoriteStores, removeFromFavorites } from '../services/favorites';
import { addToCart } from '../services/cart';
import './Favorites.css';

interface Product {
  id: number;
  name: string;
  price: number;
  image: string;
  store_name: string;
  store_id: number;
}

interface Store {
  id: number;
  name: string;
  logo: string;
  products_count: number;
  rating: number;
}

const Favorites: React.FC = () => {
  const [segment, setSegment] = useState<'products' | 'stores'>('products');
  const [favoriteProducts, setFavoriteProducts] = useState<Product[]>([]);
  const [favoriteStores, setFavoriteStores] = useState<Store[]>([]);
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    fetchFavorites();
  }, []);

  const fetchFavorites = async () => {
    setLoading(true);
    try {
      if (segment === 'products') {
        const products = await getFavoriteProducts();
        setFavoriteProducts(products);
      } else {
        const stores = await getFavoriteStores();
        setFavoriteStores(stores);
      }
    } catch (error) {
      console.error('Error fetching favorites:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchFavorites();
  }, [segment]);

  const handleRefresh = async (event: CustomEvent) => {
    await fetchFavorites();
    event.detail.complete();
  };

  const handleRemoveProduct = async (productId: number) => {
    try {
      await removeFromFavorites('product', productId);
      setFavoriteProducts(favoriteProducts.filter(product => product.id !== productId));
    } catch (error) {
      console.error('Error removing product from favorites:', error);
    }
  };

  const handleRemoveStore = async (storeId: number) => {
    try {
      await removeFromFavorites('store', storeId);
      setFavoriteStores(favoriteStores.filter(store => store.id !== storeId));
    } catch (error) {
      console.error('Error removing store from favorites:', error);
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

  const renderProductSkeletons = () => {
    return Array(4).fill(0).map((_, index) => (
      <IonCol size="6" key={index}>
        <IonCard className="favorite-product-card">
          <div className="product-image-container">
            <IonSkeletonText animated style={{ width: '100%', height: '120px' }} />
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

  const renderStoreSkeletons = () => {
    return Array(4).fill(0).map((_, index) => (
      <IonCol size="6" key={index}>
        <IonCard className="favorite-store-card">
          <div className="store-logo-container">
            <IonSkeletonText animated style={{ width: '80px', height: '80px', borderRadius: '50%', margin: '0 auto' }} />
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
            <div className="store-actions">
              <IonSkeletonText animated style={{ width: '100%', height: '30px' }} />
            </div>
          </IonCardContent>
        </IonCard>
      </IonCol>
    ));
  };

  const renderEmptyState = () => {
    return (
      <div className="empty-favorites">
        <IonIcon 
          icon={segment === 'products' ? heartDislikeOutline : storefront} 
          className="empty-icon" 
        />
        <h3>
          {segment === 'products' 
            ? 'لا توجد منتجات في المفضلة' 
            : 'لا توجد متاجر في المفضلة'}
        </h3>
        <p>
          {segment === 'products'
            ? 'أضف منتجات إلى المفضلة لتتمكن من العودة إليها بسهولة لاحقاً'
            : 'أضف متاجر إلى المفضلة لمتابعة منتجاتها وعروضها'}
        </p>
        <IonButton 
          expand="block" 
          color="warning" 
          routerLink={segment === 'products' ? '/products' : '/stores'}
        >
          {segment === 'products' ? 'تصفح المنتجات' : 'تصفح المتاجر'}
        </IonButton>
      </div>
    );
  };

  const renderProducts = () => {
    if (loading) {
      return renderProductSkeletons();
    }

    if (favoriteProducts.length === 0) {
      return renderEmptyState();
    }

    return favoriteProducts.map(product => (
      <IonCol size="6" key={product.id}>
        <IonCard className="favorite-product-card">
          <div className="product-image-container">
            <img src={product.image} alt={product.name} className="product-image" />
            <div className="favorite-button" onClick={() => handleRemoveProduct(product.id)}>
              <IonIcon icon={heartOutline} className="favorite-icon" />
            </div>
          </div>
          <IonCardHeader>
            <IonCardTitle>{product.name}</IonCardTitle>
            <IonCardSubtitle>
              <IonIcon icon={storefront} /> {product.store_name}
            </IonCardSubtitle>
          </IonCardHeader>
          <IonCardContent>
            <div className="product-price">
              <IonText color="dark">{product.price} ر.س</IonText>
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

  const renderStores = () => {
    if (loading) {
      return renderStoreSkeletons();
    }

    if (favoriteStores.length === 0) {
      return renderEmptyState();
    }

    return favoriteStores.map(store => (
      <IonCol size="6" key={store.id}>
        <IonCard className="favorite-store-card">
          <div className="store-logo-container">
            <img src={store.logo} alt={store.name} className="store-logo" />
            <div className="favorite-button" onClick={() => handleRemoveStore(store.id)}>
              <IonIcon icon={heartOutline} className="favorite-icon" />
            </div>
          </div>
          <IonCardHeader>
            <IonCardTitle>{store.name}</IonCardTitle>
            <IonCardSubtitle>
              <div className="store-rating">
                {Array(5).fill(0).map((_, i) => (
                  <span key={i} className={i < Math.floor(store.rating) ? 'star filled' : 'star'}>★</span>
                ))}
                <span className="rating-text">{store.rating}</span>
              </div>
            </IonCardSubtitle>
          </IonCardHeader>
          <IonCardContent>
            <div className="store-products-count">
              <IonText color="medium">{store.products_count} منتج</IonText>
            </div>
            <div className="store-actions">
              <IonButton 
                expand="block" 
                fill="solid" 
                color="warning" 
                size="small" 
                routerLink={`/store/${store.id}`}
              >
                زيارة المتجر
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
          <IonTitle>المفضلة</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        <IonRefresher slot="fixed" onIonRefresh={handleRefresh}>
          <IonRefresherContent></IonRefresherContent>
        </IonRefresher>
        
        <IonSegment value={segment} onIonChange={e => setSegment(e.detail.value as 'products' | 'stores')}>
          <IonSegmentButton value="products">
            <IonLabel>المنتجات</IonLabel>
          </IonSegmentButton>
          <IonSegmentButton value="stores">
            <IonLabel>المتاجر</IonLabel>
          </IonSegmentButton>
        </IonSegment>

        <div className="favorites-container">
          <IonGrid>
            <IonRow>
              {segment === 'products' ? renderProducts() : renderStores()}
            </IonRow>
          </IonGrid>
        </div>
      </IonContent>
    </IonPage>
  );
};

export default Favorites;

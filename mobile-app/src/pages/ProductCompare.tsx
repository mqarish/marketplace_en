import React, { useState, useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonButtons,
  IonBackButton,
  IonGrid,
  IonRow,
  IonCol,
  IonCard,
  IonCardHeader,
  IonCardContent,
  IonCardTitle,
  IonButton,
  IonIcon,
  IonSkeletonText,
  IonText,
  IonChip,
  IonLabel,
  IonAlert
} from '@ionic/react';
import { 
  closeCircleOutline, 
  cartOutline, 
  heartOutline, 
  heart, 
  checkmarkCircleOutline,
  closeCircle
} from 'ionicons/icons';
import { getProductById } from '../services/products';
import { addToCart } from '../services/cart';
import { addToFavorites, removeFromFavorites, isFavorite } from '../services/favorites';
import './ProductCompare.css';

interface ProductDetail {
  id: number;
  name: string;
  price: number;
  original_price?: number;
  description: string;
  image: string;
  images: string[];
  rating: number;
  reviews_count: number;
  store_name: string;
  store_id: number;
  category_name: string;
  category_id: number;
  stock: number;
  attributes: {
    [key: string]: string;
  };
}

const ProductCompare: React.FC = () => {
  const location = useLocation();
  const queryParams = new URLSearchParams(location.search);
  const productIds = queryParams.get('ids')?.split(',').map(id => parseInt(id)) || [];
  
  const [products, setProducts] = useState<ProductDetail[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [favorites, setFavorites] = useState<{[key: number]: boolean}>({});
  const [showAlert, setShowAlert] = useState<boolean>(false);
  const [alertMessage, setAlertMessage] = useState<string>('');
  
  // جمع كل الخصائص من جميع المنتجات
  const allAttributes = products.reduce((attrs, product) => {
    Object.keys(product.attributes).forEach(key => {
      if (!attrs.includes(key)) {
        attrs.push(key);
      }
    });
    return attrs;
  }, [] as string[]);

  useEffect(() => {
    if (productIds.length > 0) {
      fetchProducts();
    } else {
      setLoading(false);
    }
  }, [productIds]);

  const fetchProducts = async () => {
    setLoading(true);
    try {
      const productDetails = await Promise.all(
        productIds.map(id => getProductById(id))
      );
      setProducts(productDetails);
      
      // التحقق من حالة المفضلة لكل منتج
      const favStatus = await Promise.all(
        productIds.map(id => isFavorite('product', id))
      );
      
      const favMap: {[key: number]: boolean} = {};
      productIds.forEach((id, index) => {
        favMap[id] = favStatus[index];
      });
      
      setFavorites(favMap);
    } catch (error) {
      console.error('Error fetching products for comparison:', error);
      setAlertMessage('حدث خطأ أثناء جلب بيانات المنتجات');
      setShowAlert(true);
    } finally {
      setLoading(false);
    }
  };

  const handleRemoveProduct = (productId: number) => {
    const newProductIds = productIds.filter(id => id !== productId);
    if (newProductIds.length > 0) {
      const newUrl = `/product-compare?ids=${newProductIds.join(',')}`;
      window.location.href = newUrl;
    } else {
      window.history.back();
    }
  };

  const handleAddToCart = async (product: ProductDetail) => {
    try {
      await addToCart(product, 1);
      setAlertMessage('تمت إضافة المنتج إلى سلة التسوق');
      setShowAlert(true);
    } catch (error) {
      console.error('Error adding product to cart:', error);
      setAlertMessage('حدث خطأ أثناء إضافة المنتج إلى سلة التسوق');
      setShowAlert(true);
    }
  };

  const toggleFavorite = async (productId: number) => {
    try {
      if (favorites[productId]) {
        await removeFromFavorites('product', productId);
      } else {
        await addToFavorites('product', productId);
      }
      
      setFavorites({
        ...favorites,
        [productId]: !favorites[productId]
      });
    } catch (error) {
      console.error('Error toggling favorite:', error);
      setAlertMessage('حدث خطأ أثناء تحديث المفضلة');
      setShowAlert(true);
    }
  };

  const renderProductSkeletons = () => {
    return Array(productIds.length || 2).fill(0).map((_, index) => (
      <IonCol key={index} size="12" sizeMd="6" sizeLg={12 / (productIds.length || 2)}>
        <IonCard className="product-compare-card">
          <div className="product-header">
            <IonSkeletonText animated style={{ width: '80%', height: '20px' }} />
          </div>
          <div className="product-image-container">
            <IonSkeletonText animated style={{ width: '100%', height: '150px' }} />
          </div>
          <IonCardContent>
            <div className="product-price">
              <IonSkeletonText animated style={{ width: '40%', height: '24px' }} />
            </div>
            <div className="product-actions">
              <IonSkeletonText animated style={{ width: '100%', height: '40px' }} />
            </div>
            
            <div className="product-attributes">
              {Array(5).fill(0).map((_, i) => (
                <div key={i} className="attribute-row">
                  <IonSkeletonText animated style={{ width: '40%', height: '16px' }} />
                  <IonSkeletonText animated style={{ width: '60%', height: '16px' }} />
                </div>
              ))}
            </div>
          </IonCardContent>
        </IonCard>
      </IonCol>
    ));
  };

  const renderEmptyState = () => {
    return (
      <div className="empty-compare">
        <IonIcon icon={closeCircleOutline} className="empty-icon" />
        <h3>لا توجد منتجات للمقارنة</h3>
        <p>قم بإضافة منتجات للمقارنة من صفحة تفاصيل المنتج.</p>
        <IonButton routerLink="/products" expand="block" color="warning">
          تصفح المنتجات
        </IonButton>
      </div>
    );
  };

  const renderProducts = () => {
    if (products.length === 0) {
      return renderEmptyState();
    }

    return (
      <>
        <div className="compare-header">
          <h4>مقارنة المنتجات</h4>
          <p>قارن بين خصائص المنتجات لاتخاذ قرار شراء أفضل</p>
        </div>
        
        <div className="compare-table">
          <IonGrid className="product-grid">
            <IonRow>
              {products.map(product => (
                <IonCol key={product.id} size="12" sizeMd="6" sizeLg={12 / products.length}>
                  <IonCard className="product-compare-card">
                    <div className="product-header">
                      <IonCardTitle>{product.name}</IonCardTitle>
                      <IonButton 
                        fill="clear" 
                        size="small" 
                        className="remove-button"
                        onClick={() => handleRemoveProduct(product.id)}
                      >
                        <IonIcon icon={closeCircleOutline} />
                      </IonButton>
                    </div>
                    
                    <div className="product-image-container">
                      <img src={product.image} alt={product.name} className="product-image" />
                      <IonButton 
                        fill="clear" 
                        className="favorite-button" 
                        onClick={() => toggleFavorite(product.id)}
                      >
                        <IonIcon icon={favorites[product.id] ? heart : heartOutline} />
                      </IonButton>
                    </div>
                    
                    <IonCardContent>
                      <div className="product-price">
                        {product.original_price && product.original_price > product.price && (
                          <span className="original-price">{product.original_price} ر.س</span>
                        )}
                        <span className="current-price">{product.price} ر.س</span>
                      </div>
                      
                      <div className="product-store">
                        <IonChip color="medium" routerLink={`/store/${product.store_id}`}>
                          <IonLabel>{product.store_name}</IonLabel>
                        </IonChip>
                      </div>
                      
                      <div className="product-stock">
                        {product.stock > 0 ? (
                          <IonChip color="success">
                            <IonIcon icon={checkmarkCircleOutline} />
                            <IonLabel>متوفر في المخزون</IonLabel>
                          </IonChip>
                        ) : (
                          <IonChip color="danger">
                            <IonIcon icon={closeCircle} />
                            <IonLabel>غير متوفر</IonLabel>
                          </IonChip>
                        )}
                      </div>
                      
                      <div className="product-actions">
                        <IonButton 
                          expand="block" 
                          color="warning" 
                          onClick={() => handleAddToCart(product)}
                          disabled={product.stock <= 0}
                        >
                          <IonIcon slot="start" icon={cartOutline} />
                          إضافة للسلة
                        </IonButton>
                      </div>
                    </IonCardContent>
                  </IonCard>
                </IonCol>
              ))}
            </IonRow>
          </IonGrid>
          
          <div className="attributes-section">
            <h4>مقارنة الخصائص</h4>
            
            <div className="attributes-table">
              {allAttributes.map(attr => (
                <div key={attr} className="attribute-row">
                  <div className="attribute-name">{attr}</div>
                  <div className="attribute-values">
                    <IonRow>
                      {products.map(product => (
                        <IonCol key={product.id} size="12" sizeMd="6" sizeLg={12 / products.length}>
                          <div className="attribute-value">
                            {product.attributes[attr] || '-'}
                          </div>
                        </IonCol>
                      ))}
                    </IonRow>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </>
    );
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonButtons slot="start">
            <IonBackButton defaultHref="/products" />
          </IonButtons>
          <IonTitle>مقارنة المنتجات</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        <div className="compare-container">
          {loading ? renderProductSkeletons() : renderProducts()}
        </div>
        
        <IonAlert
          isOpen={showAlert}
          onDidDismiss={() => setShowAlert(false)}
          header="تنبيه"
          message={alertMessage}
          buttons={['حسناً']}
        />
      </IonContent>
    </IonPage>
  );
};

export default ProductCompare;

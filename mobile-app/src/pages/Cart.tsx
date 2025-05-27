import React, { useEffect, useState } from 'react';
import { useHistory } from 'react-router-dom';
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonList,
  IonItem,
  IonLabel,
  IonThumbnail,
  IonImg,
  IonButton,
  IonIcon,
  IonItemSliding,
  IonItemOptions,
  IonItemOption,
  IonFooter,
  IonText,
  IonAlert,
  IonLoading,
  IonCard,
  IonCardHeader,
  IonCardContent,
  IonInput,
  IonTextarea
} from '@ionic/react';
import { 
  trashOutline, 
  addOutline, 
  removeOutline, 
  cartOutline, 
  arrowForwardOutline,
  locationOutline
} from 'ionicons/icons';
import { getCart, updateCartItemQuantity, removeFromCart, clearCart, checkout, CartItem } from '../services/cart';
import { isAuthenticated } from '../services/auth';
import './Cart.css';

const Cart: React.FC = () => {
  const history = useHistory();
  const [cart, setCart] = useState<{ items: CartItem[], total: number }>({ items: [], total: 0 });
  const [showCheckout, setShowCheckout] = useState(false);
  const [showLoginAlert, setShowLoginAlert] = useState(false);
  const [showClearAlert, setShowClearAlert] = useState(false);
  const [loading, setLoading] = useState(false);
  const [address, setAddress] = useState('');
  const [city, setCity] = useState('');
  const [phone, setPhone] = useState('');
  const [notes, setNotes] = useState('');

  // جلب محتويات سلة التسوق
  useEffect(() => {
    const cartData = getCart();
    setCart(cartData);
  }, []);

  // دالة لتحديث كمية منتج
  const handleUpdateQuantity = (itemId: number, newQuantity: number) => {
    if (newQuantity <= 0) {
      return;
    }
    const updatedCart = updateCartItemQuantity(itemId, newQuantity);
    setCart(updatedCart);
  };

  // دالة لحذف منتج من السلة
  const handleRemoveItem = (itemId: number) => {
    const updatedCart = removeFromCart(itemId);
    setCart(updatedCart);
  };

  // دالة لتفريغ السلة
  const handleClearCart = () => {
    const emptyCart = clearCart();
    setCart(emptyCart);
    setShowClearAlert(false);
  };

  // دالة لبدء عملية الدفع
  const handleProceedToCheckout = () => {
    if (!isAuthenticated()) {
      setShowLoginAlert(true);
      return;
    }

    setShowCheckout(true);
  };

  // دالة لإتمام الطلب
  const handleCompleteOrder = async () => {
    if (!address || !city || !phone) {
      return; // يمكن إضافة تنبيه هنا
    }

    try {
      setLoading(true);
      
      const shippingAddress = {
        address,
        city,
        phone,
        notes
      };
      
      await checkout(shippingAddress);
      
      // إعادة تعيين السلة
      setCart({ items: [], total: 0 });
      setShowCheckout(false);
      
      // إعادة توجيه المستخدم إلى صفحة تأكيد الطلب
      history.push('/order-success');
    } catch (error) {
      console.error('Checkout error:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonTitle>سلة التسوق</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        {cart.items.length === 0 ? (
          <div className="empty-cart">
            <IonIcon icon={cartOutline} color="medium" />
            <h2>سلة التسوق فارغة</h2>
            <p>لم تقم بإضافة أي منتجات إلى سلة التسوق بعد.</p>
            <IonButton routerLink="/products" expand="block" color="warning">
              تصفح المنتجات
            </IonButton>
          </div>
        ) : (
          <>
            {!showCheckout ? (
              <>
                <div className="cart-header">
                  <h2>منتجات السلة ({cart.items.length})</h2>
                  <IonButton 
                    fill="clear" 
                    color="medium" 
                    size="small"
                    onClick={() => setShowClearAlert(true)}
                  >
                    تفريغ السلة
                  </IonButton>
                </div>
                
                <IonList className="cart-list">
                  {cart.items.map((item) => (
                    <IonItemSliding key={item.id}>
                      <IonItem className="cart-item">
                        <IonThumbnail slot="start">
                          <IonImg src={item.product.image} alt={item.product.name} />
                        </IonThumbnail>
                        <IonLabel>
                          <h2>{item.product.name}</h2>
                          <p>{item.product.store_name}</p>
                          <div className="item-price">{item.price} ريال</div>
                        </IonLabel>
                        <div className="quantity-control">
                          <IonButton 
                            fill="clear" 
                            size="small"
                            onClick={() => handleUpdateQuantity(item.id, item.quantity - 1)}
                          >
                            <IonIcon slot="icon-only" icon={removeOutline} />
                          </IonButton>
                          <span className="quantity">{item.quantity}</span>
                          <IonButton 
                            fill="clear" 
                            size="small"
                            onClick={() => handleUpdateQuantity(item.id, item.quantity + 1)}
                          >
                            <IonIcon slot="icon-only" icon={addOutline} />
                          </IonButton>
                        </div>
                      </IonItem>
                      
                      <IonItemOptions side="end">
                        <IonItemOption color="danger" onClick={() => handleRemoveItem(item.id)}>
                          <IonIcon slot="icon-only" icon={trashOutline} />
                        </IonItemOption>
                      </IonItemOptions>
                    </IonItemSliding>
                  ))}
                </IonList>
              </>
            ) : (
              <div className="checkout-form ion-padding">
                <h2>إتمام الطلب</h2>
                
                <IonCard>
                  <IonCardHeader>
                    <IonLabel>معلومات الشحن</IonLabel>
                  </IonCardHeader>
                  <IonCardContent>
                    <IonItem>
                      <IonLabel position="floating">العنوان</IonLabel>
                      <IonInput 
                        value={address} 
                        onIonChange={e => setAddress(e.detail.value!)} 
                        required
                      />
                    </IonItem>
                    
                    <IonItem>
                      <IonLabel position="floating">المدينة</IonLabel>
                      <IonInput 
                        value={city} 
                        onIonChange={e => setCity(e.detail.value!)} 
                        required
                      />
                    </IonItem>
                    
                    <IonItem>
                      <IonLabel position="floating">رقم الهاتف</IonLabel>
                      <IonInput 
                        type="tel" 
                        value={phone} 
                        onIonChange={e => setPhone(e.detail.value!)} 
                        required
                      />
                    </IonItem>
                    
                    <IonItem>
                      <IonLabel position="floating">ملاحظات إضافية</IonLabel>
                      <IonTextarea 
                        value={notes} 
                        onIonChange={e => setNotes(e.detail.value!)}
                      />
                    </IonItem>
                  </IonCardContent>
                </IonCard>
                
                <IonCard>
                  <IonCardHeader>
                    <IonLabel>ملخص الطلب</IonLabel>
                  </IonCardHeader>
                  <IonCardContent>
                    <div className="order-summary">
                      <div className="summary-item">
                        <span>عدد المنتجات:</span>
                        <span>{cart.items.length}</span>
                      </div>
                      <div className="summary-item">
                        <span>إجمالي المنتجات:</span>
                        <span>{cart.total.toFixed(2)} ريال</span>
                      </div>
                      <div className="summary-item">
                        <span>رسوم الشحن:</span>
                        <span>0.00 ريال</span>
                      </div>
                      <div className="summary-divider"></div>
                      <div className="summary-total">
                        <span>الإجمالي:</span>
                        <span>{cart.total.toFixed(2)} ريال</span>
                      </div>
                    </div>
                  </IonCardContent>
                </IonCard>
                
                <div className="checkout-actions">
                  <IonButton 
                    expand="block" 
                    color="medium" 
                    onClick={() => setShowCheckout(false)}
                  >
                    <IonIcon slot="start" icon={arrowForwardOutline} />
                    العودة للسلة
                  </IonButton>
                  
                  <IonButton 
                    expand="block" 
                    color="warning" 
                    onClick={handleCompleteOrder}
                    disabled={!address || !city || !phone}
                  >
                    <IonIcon slot="start" icon={locationOutline} />
                    تأكيد الطلب
                  </IonButton>
                </div>
              </div>
            )}
          </>
        )}
        
        <IonAlert
          isOpen={showLoginAlert}
          onDidDismiss={() => setShowLoginAlert(false)}
          header="تنبيه"
          message="يجب تسجيل الدخول أولاً لإتمام عملية الشراء."
          buttons={[
            {
              text: 'إلغاء',
              role: 'cancel'
            },
            {
              text: 'تسجيل الدخول',
              handler: () => {
                history.push('/login');
              }
            }
          ]}
        />
        
        <IonAlert
          isOpen={showClearAlert}
          onDidDismiss={() => setShowClearAlert(false)}
          header="تأكيد"
          message="هل أنت متأكد من رغبتك في تفريغ سلة التسوق؟"
          buttons={[
            {
              text: 'إلغاء',
              role: 'cancel'
            },
            {
              text: 'تفريغ',
              handler: handleClearCart
            }
          ]}
        />
        
        <IonLoading
          isOpen={loading}
          message={'جاري معالجة الطلب...'}
        />
      </IonContent>
      
      {!showCheckout && cart.items.length > 0 && (
        <IonFooter>
          <div className="cart-footer">
            <div className="cart-total">
              <span>الإجمالي:</span>
              <span className="total-price">{cart.total.toFixed(2)} ريال</span>
            </div>
            <IonButton 
              expand="block" 
              color="warning" 
              onClick={handleProceedToCheckout}
            >
              متابعة الشراء
            </IonButton>
          </div>
        </IonFooter>
      )}
    </IonPage>
  );
};

export default Cart;

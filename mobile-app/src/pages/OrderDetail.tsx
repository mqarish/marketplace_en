import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
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
  IonCardContent,
  IonCardTitle,
  IonCardSubtitle,
  IonList,
  IonItem,
  IonLabel,
  IonBadge,
  IonButton,
  IonIcon,
  IonSkeletonText,
  IonGrid,
  IonRow,
  IonCol,
  IonText,
  IonAlert
} from '@ionic/react';
import { 
  timeOutline, 
  locationOutline, 
  cardOutline, 
  closeCircleOutline,
  callOutline,
  mailOutline
} from 'ionicons/icons';
import { getOrderById, cancelOrder, OrderDetail as OrderDetailType } from '../services/orders';
import './OrderDetail.css';

const OrderDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [order, setOrder] = useState<OrderDetailType | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [showCancelAlert, setShowCancelAlert] = useState<boolean>(false);

  useEffect(() => {
    fetchOrderDetails();
  }, [id]);

  const fetchOrderDetails = async () => {
    try {
      setLoading(true);
      const data = await getOrderById(parseInt(id));
      setOrder(data);
      setLoading(false);
    } catch (error) {
      console.error('Error fetching order details:', error);
      setLoading(false);
    }
  };

  const handleCancelOrder = async () => {
    if (!order) return;
    
    try {
      await cancelOrder(order.id);
      // تحديث حالة الطلب محلياً
      setOrder({ ...order, status: 'cancelled' });
    } catch (error) {
      console.error('Error cancelling order:', error);
    }
  };

  const getStatusBadge = (status: string) => {
    let color = 'medium';
    let text = 'غير معروف';
    
    switch (status) {
      case 'pending':
        color = 'warning';
        text = 'قيد الانتظار';
        break;
      case 'processing':
        color = 'primary';
        text = 'قيد المعالجة';
        break;
      case 'shipped':
        color = 'tertiary';
        text = 'تم الشحن';
        break;
      case 'delivered':
        color = 'success';
        text = 'تم التوصيل';
        break;
      case 'cancelled':
        color = 'danger';
        text = 'ملغي';
        break;
    }
    
    return <IonBadge color={color}>{text}</IonBadge>;
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-SA', { 
      year: 'numeric', 
      month: 'short', 
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const renderOrderSkeleton = () => {
    return (
      <>
        <IonCard className="order-header-card">
          <IonCardHeader>
            <IonCardTitle>
              <IonSkeletonText animated style={{ width: '60%' }} />
            </IonCardTitle>
            <IonCardSubtitle>
              <IonSkeletonText animated style={{ width: '40%' }} />
            </IonCardSubtitle>
          </IonCardHeader>
          <IonCardContent>
            <div className="order-status-container">
              <IonSkeletonText animated style={{ width: '30%', height: '20px' }} />
            </div>
          </IonCardContent>
        </IonCard>

        <IonCard className="order-info-card">
          <IonCardHeader>
            <IonCardTitle>
              <IonSkeletonText animated style={{ width: '40%' }} />
            </IonCardTitle>
          </IonCardHeader>
          <IonCardContent>
            <IonGrid>
              <IonRow>
                <IonCol size="6">
                  <IonSkeletonText animated style={{ width: '90%' }} />
                </IonCol>
                <IonCol size="6">
                  <IonSkeletonText animated style={{ width: '90%' }} />
                </IonCol>
              </IonRow>
              <IonRow>
                <IonCol size="6">
                  <IonSkeletonText animated style={{ width: '90%' }} />
                </IonCol>
                <IonCol size="6">
                  <IonSkeletonText animated style={{ width: '90%' }} />
                </IonCol>
              </IonRow>
            </IonGrid>
          </IonCardContent>
        </IonCard>

        <IonCard className="order-items-card">
          <IonCardHeader>
            <IonCardTitle>
              <IonSkeletonText animated style={{ width: '40%' }} />
            </IonCardTitle>
          </IonCardHeader>
          <IonCardContent>
            {Array(3).fill(0).map((_, index) => (
              <div key={index} className="order-item-skeleton">
                <div className="item-image-skeleton">
                  <IonSkeletonText animated style={{ width: '100%', height: '100%' }} />
                </div>
                <div className="item-details-skeleton">
                  <IonSkeletonText animated style={{ width: '70%' }} />
                  <IonSkeletonText animated style={{ width: '40%' }} />
                  <IonSkeletonText animated style={{ width: '30%' }} />
                </div>
              </div>
            ))}
          </IonCardContent>
        </IonCard>
      </>
    );
  };

  const renderOrderDetails = () => {
    if (!order) return null;

    return (
      <>
        <IonCard className="order-header-card">
          <IonCardHeader>
            <IonCardTitle>طلب #{order.id}</IonCardTitle>
            <IonCardSubtitle>
              <IonIcon icon={timeOutline} /> {formatDate(order.created_at)}
            </IonCardSubtitle>
          </IonCardHeader>
          <IonCardContent>
            <div className="order-status-container">
              <div className="status-label">حالة الطلب:</div>
              {getStatusBadge(order.status)}
            </div>

            {(order.status === 'pending' || order.status === 'processing') && (
              <IonButton 
                expand="block" 
                color="danger" 
                className="cancel-button"
                onClick={() => setShowCancelAlert(true)}
              >
                <IonIcon slot="start" icon={closeCircleOutline} />
                إلغاء الطلب
              </IonButton>
            )}
          </IonCardContent>
        </IonCard>

        <IonCard className="order-info-card">
          <IonCardHeader>
            <IonCardTitle>معلومات الطلب</IonCardTitle>
          </IonCardHeader>
          <IonCardContent>
            <div className="info-section">
              <div className="info-title">
                <IonIcon icon={locationOutline} />
                عنوان الشحن
              </div>
              <div className="address-details">
                <div className="address-name">{order.shipping_address.name}</div>
                <div className="address-phone">
                  <IonIcon icon={callOutline} />
                  {order.shipping_address.phone}
                </div>
                <div className="address-line">{order.shipping_address.address}</div>
                <div className="address-city">{order.shipping_address.city} - {order.shipping_address.postal_code}</div>
              </div>
            </div>

            <div className="info-section">
              <div className="info-title">
                <IonIcon icon={cardOutline} />
                طريقة الدفع
              </div>
              <div className="payment-method">
                {order.payment_method === 'cod' ? 'الدفع عند الاستلام' : 
                 order.payment_method === 'credit_card' ? 'بطاقة ائتمان' : 
                 order.payment_method === 'bank_transfer' ? 'تحويل بنكي' : 
                 order.payment_method}
              </div>
            </div>
          </IonCardContent>
        </IonCard>

        <IonCard className="order-items-card">
          <IonCardHeader>
            <IonCardTitle>المنتجات</IonCardTitle>
          </IonCardHeader>
          <IonCardContent>
            <div className="order-items-list">
              {order.items.map(item => (
                <div key={item.id} className="order-item">
                  <div className="item-image-container">
                    <img src={item.product_image} alt={item.product_name} className="item-image" />
                  </div>
                  <div className="item-details">
                    <div className="item-name">{item.product_name}</div>
                    <div className="item-quantity">الكمية: {item.quantity}</div>
                    <div className="item-price">{item.price.toFixed(2)} ر.س</div>
                  </div>
                  <div className="item-subtotal">
                    <div className="subtotal-label">المجموع</div>
                    <div className="subtotal-value">{item.subtotal.toFixed(2)} ر.س</div>
                  </div>
                </div>
              ))}
            </div>

            <div className="order-summary">
              <div className="summary-row">
                <div className="summary-label">المجموع الفرعي</div>
                <div className="summary-value">{order.total_amount.toFixed(2)} ر.س</div>
              </div>
              <div className="summary-row">
                <div className="summary-label">الشحن</div>
                <div className="summary-value">0.00 ر.س</div>
              </div>
              <div className="summary-row total">
                <div className="summary-label">الإجمالي</div>
                <div className="summary-value">{order.total_amount.toFixed(2)} ر.س</div>
              </div>
            </div>
          </IonCardContent>
        </IonCard>
      </>
    );
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonButtons slot="start">
            <IonBackButton defaultHref="/orders" />
          </IonButtons>
          <IonTitle>تفاصيل الطلب</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        <div className="order-detail-container">
          {loading ? renderOrderSkeleton() : renderOrderDetails()}
        </div>

        <IonAlert
          isOpen={showCancelAlert}
          onDidDismiss={() => setShowCancelAlert(false)}
          header="تأكيد إلغاء الطلب"
          message="هل أنت متأكد من رغبتك في إلغاء هذا الطلب؟ لا يمكن التراجع عن هذا الإجراء."
          buttons={[
            {
              text: 'إلغاء',
              role: 'cancel',
              cssClass: 'secondary'
            },
            {
              text: 'تأكيد',
              handler: handleCancelOrder
            }
          ]}
        />
      </IonContent>
    </IonPage>
  );
};

export default OrderDetail;

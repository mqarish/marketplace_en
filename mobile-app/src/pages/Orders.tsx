import React, { useState, useEffect } from 'react';
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonList,
  IonItem,
  IonLabel,
  IonBadge,
  IonSkeletonText,
  IonButton,
  IonIcon,
  IonRefresher,
  IonRefresherContent,
  IonCard,
  IonCardHeader,
  IonCardContent,
  IonCardTitle,
  IonCardSubtitle,
  IonAlert
} from '@ionic/react';
import { 
  timeOutline, 
  cartOutline, 
  chevronForwardOutline, 
  closeCircleOutline,
  documentTextOutline
} from 'ionicons/icons';
import { getOrders, cancelOrder } from '../services/orders';
import './Orders.css';

interface Order {
  id: number;
  total_amount: number;
  status: string;
  created_at: string;
  items_count: number;
}

const Orders: React.FC = () => {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [showCancelAlert, setShowCancelAlert] = useState<boolean>(false);
  const [selectedOrderId, setSelectedOrderId] = useState<number | null>(null);

  useEffect(() => {
    fetchOrders();
  }, []);

  const fetchOrders = async () => {
    try {
      setLoading(true);
      const data = await getOrders();
      setOrders(data.orders);
      setLoading(false);
    } catch (error) {
      console.error('Error fetching orders:', error);
      setLoading(false);
    }
  };

  const handleRefresh = async (event: CustomEvent) => {
    await fetchOrders();
    event.detail.complete();
  };

  const handleCancelOrder = async () => {
    if (selectedOrderId === null) return;
    
    try {
      await cancelOrder(selectedOrderId);
      // تحديث حالة الطلب في القائمة المحلية
      setOrders(orders.map(order => 
        order.id === selectedOrderId 
          ? { ...order, status: 'cancelled' } 
          : order
      ));
    } catch (error) {
      console.error('Error cancelling order:', error);
    }
  };

  const confirmCancelOrder = (orderId: number) => {
    setSelectedOrderId(orderId);
    setShowCancelAlert(true);
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

  const renderOrderSkeletons = () => {
    return Array(3).fill(0).map((_, index) => (
      <IonCard key={index} className="order-card">
        <IonCardHeader>
          <IonCardTitle>
            <IonSkeletonText animated style={{ width: '60%' }} />
          </IonCardTitle>
          <IonCardSubtitle>
            <IonSkeletonText animated style={{ width: '40%' }} />
          </IonCardSubtitle>
        </IonCardHeader>
        <IonCardContent>
          <div className="order-details">
            <div className="order-info">
              <IonSkeletonText animated style={{ width: '70%' }} />
              <IonSkeletonText animated style={{ width: '50%' }} />
            </div>
            <div className="order-status">
              <IonSkeletonText animated style={{ width: '30%', height: '20px' }} />
            </div>
          </div>
          <div className="order-actions">
            <IonSkeletonText animated style={{ width: '100%', height: '40px' }} />
          </div>
        </IonCardContent>
      </IonCard>
    ));
  };

  const renderEmptyState = () => {
    return (
      <div className="empty-orders">
        <IonIcon icon={documentTextOutline} className="empty-icon" />
        <h3>لا توجد طلبات</h3>
        <p>لم تقم بإجراء أي طلبات بعد. تصفح المنتجات وأضفها إلى سلة التسوق لإنشاء طلب جديد.</p>
        <IonButton expand="block" color="warning" routerLink="/products">
          تصفح المنتجات
        </IonButton>
      </div>
    );
  };

  const renderOrders = () => {
    if (loading) {
      return renderOrderSkeletons();
    }

    if (orders.length === 0) {
      return renderEmptyState();
    }

    return orders.map(order => (
      <IonCard key={order.id} className="order-card">
        <IonCardHeader>
          <IonCardTitle>طلب #{order.id}</IonCardTitle>
          <IonCardSubtitle>
            <IonIcon icon={timeOutline} /> {formatDate(order.created_at)}
          </IonCardSubtitle>
        </IonCardHeader>
        <IonCardContent>
          <div className="order-details">
            <div className="order-info">
              <div className="order-price">{order.total_amount.toFixed(2)} ر.س</div>
              <div className="order-items-count">{order.items_count} {order.items_count === 1 ? 'منتج' : 'منتجات'}</div>
            </div>
            <div className="order-status">
              {getStatusBadge(order.status)}
            </div>
          </div>
          <div className="order-actions">
            <IonButton 
              fill="clear" 
              color="medium" 
              routerLink={`/order/${order.id}`}
            >
              التفاصيل
              <IonIcon slot="end" icon={chevronForwardOutline} />
            </IonButton>
            
            {(order.status === 'pending' || order.status === 'processing') && (
              <IonButton 
                fill="clear" 
                color="danger" 
                onClick={() => confirmCancelOrder(order.id)}
              >
                إلغاء الطلب
                <IonIcon slot="end" icon={closeCircleOutline} />
              </IonButton>
            )}
          </div>
        </IonCardContent>
      </IonCard>
    ));
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonTitle>طلباتي</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        <IonRefresher slot="fixed" onIonRefresh={handleRefresh}>
          <IonRefresherContent></IonRefresherContent>
        </IonRefresher>
        
        <div className="orders-container">
          {renderOrders()}
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

export default Orders;

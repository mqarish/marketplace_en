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
  IonIcon,
  IonSkeletonText,
  IonButton,
  IonBadge,
  IonRefresher,
  IonRefresherContent,
  IonText,
  IonItemSliding,
  IonItemOptions,
  IonItemOption
} from '@ionic/react';
import { 
  notificationsOutline, 
  timeOutline, 
  cartOutline, 
  starOutline, 
  chatbubbleOutline, 
  pricetagOutline,
  alertCircleOutline
} from 'ionicons/icons';
import { getNotifications, markAsRead, deleteNotification } from '../services/notifications';
import './Notifications.css';

interface Notification {
  id: number;
  title: string;
  message: string;
  type: string;
  read: boolean;
  created_at: string;
  link?: string;
}

const Notifications: React.FC = () => {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    fetchNotifications();
  }, []);

  const fetchNotifications = async () => {
    try {
      setLoading(true);
      const data = await getNotifications();
      setNotifications(data);
      setLoading(false);
    } catch (error) {
      console.error('Error fetching notifications:', error);
      setLoading(false);
    }
  };

  const handleRefresh = async (event: CustomEvent) => {
    await fetchNotifications();
    event.detail.complete();
  };

  const handleMarkAsRead = async (id: number) => {
    try {
      await markAsRead(id);
      setNotifications(notifications.map(notification => 
        notification.id === id ? { ...notification, read: true } : notification
      ));
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  };

  const handleDelete = async (id: number) => {
    try {
      await deleteNotification(id);
      setNotifications(notifications.filter(notification => notification.id !== id));
    } catch (error) {
      console.error('Error deleting notification:', error);
    }
  };

  const getNotificationIcon = (type: string) => {
    switch (type) {
      case 'order':
        return cartOutline;
      case 'review':
        return starOutline;
      case 'message':
        return chatbubbleOutline;
      case 'promotion':
        return pricetagOutline;
      default:
        return alertCircleOutline;
    }
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

  const renderNotifications = () => {
    if (loading) {
      return Array(5).fill(0).map((_, index) => (
        <IonItem key={index}>
          <IonIcon icon={notificationsOutline} slot="start" className="notification-icon skeleton" />
          <IonLabel>
            <h2><IonSkeletonText animated style={{ width: '70%' }} /></h2>
            <p><IonSkeletonText animated style={{ width: '90%' }} /></p>
            <p><IonSkeletonText animated style={{ width: '40%' }} /></p>
          </IonLabel>
        </IonItem>
      ));
    }

    if (notifications.length === 0) {
      return (
        <div className="empty-notifications">
          <IonIcon icon={notificationsOutline} className="empty-icon" />
          <h3>لا توجد إشعارات</h3>
          <p>ستظهر هنا الإشعارات الخاصة بطلباتك وتحديثات المتاجر والعروض</p>
          <IonButton expand="block" color="warning" onClick={fetchNotifications}>تحديث</IonButton>
        </div>
      );
    }

    return notifications.map(notification => (
      <IonItemSliding key={notification.id}>
        <IonItem 
          className={notification.read ? 'notification-read' : 'notification-unread'} 
          routerLink={notification.link}
          detail={!!notification.link}
        >
          <IonIcon icon={getNotificationIcon(notification.type)} slot="start" className="notification-icon" />
          <IonLabel>
            <h2>{notification.title}</h2>
            <p>{notification.message}</p>
            <div className="notification-meta">
              <IonIcon icon={timeOutline} className="time-icon" />
              <IonText className="time-text">{formatDate(notification.created_at)}</IonText>
            </div>
          </IonLabel>
          {!notification.read && <IonBadge color="warning" slot="end">جديد</IonBadge>}
        </IonItem>
        <IonItemOptions side="end">
          {!notification.read && (
            <IonItemOption color="primary" onClick={() => handleMarkAsRead(notification.id)}>
              تم القراءة
            </IonItemOption>
          )}
          <IonItemOption color="danger" onClick={() => handleDelete(notification.id)}>
            حذف
          </IonItemOption>
        </IonItemOptions>
      </IonItemSliding>
    ));
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonTitle>الإشعارات</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        <IonRefresher slot="fixed" onIonRefresh={handleRefresh}>
          <IonRefresherContent></IonRefresherContent>
        </IonRefresher>
        
        <div className="notification-header">
          <h3>إشعاراتك</h3>
          <p>اسحب للأسفل للتحديث أو اسحب الإشعار لليسار للحذف</p>
        </div>

        <IonList>
          {renderNotifications()}
        </IonList>
      </IonContent>
    </IonPage>
  );
};

export default Notifications;

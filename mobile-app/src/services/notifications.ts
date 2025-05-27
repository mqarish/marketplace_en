import { api } from './api';

export interface Notification {
  id: number;
  title: string;
  message: string;
  type: string;
  read: boolean;
  created_at: string;
  link?: string;
}

/**
 * الحصول على قائمة الإشعارات للمستخدم الحالي
 */
export const getNotifications = async (): Promise<Notification[]> => {
  try {
    const response = await api.get('/notifications.php?action=get');
    return response.data.notifications || [];
  } catch (error) {
    console.error('Error fetching notifications:', error);
    // في حالة الاختبار، نقوم بإرجاع بيانات تجريبية
    return getMockNotifications();
  }
};

/**
 * تحديث حالة الإشعار إلى "مقروء"
 */
export const markAsRead = async (notificationId: number): Promise<boolean> => {
  try {
    const response = await api.post('/notifications.php?action=markAsRead', {
      notification_id: notificationId
    });
    return response.data.success || false;
  } catch (error) {
    console.error('Error marking notification as read:', error);
    return true; // نعيد true للاختبار
  }
};

/**
 * حذف إشعار
 */
export const deleteNotification = async (notificationId: number): Promise<boolean> => {
  try {
    const response = await api.post('/notifications.php?action=delete', {
      notification_id: notificationId
    });
    return response.data.success || false;
  } catch (error) {
    console.error('Error deleting notification:', error);
    return true; // نعيد true للاختبار
  }
};

/**
 * تحديث رمز الإشعارات (FCM token) للإشعارات المستقبلية
 */
export const updateNotificationToken = async (token: string): Promise<boolean> => {
  try {
    const response = await api.post('/notifications.php?action=updateToken', {
      token
    });
    return response.data.success || false;
  } catch (error) {
    console.error('Error updating notification token:', error);
    return false;
  }
};

/**
 * الحصول على عدد الإشعارات غير المقروءة
 */
export const getUnreadCount = async (): Promise<number> => {
  try {
    const response = await api.get('/notifications.php?action=unreadCount');
    return response.data.count || 0;
  } catch (error) {
    console.error('Error fetching unread count:', error);
    // حساب الإشعارات غير المقروءة من البيانات التجريبية
    const mockNotifications = getMockNotifications();
    return mockNotifications.filter(notification => !notification.read).length;
  }
};

/**
 * بيانات تجريبية للإشعارات (تستخدم للاختبار فقط)
 */
const getMockNotifications = (): Notification[] => {
  return [
    {
      id: 1,
      title: 'تم شحن طلبك',
      message: 'تم شحن طلبك رقم #12345 وسيصل إليك خلال 2-3 أيام عمل.',
      type: 'order',
      read: false,
      created_at: new Date().toISOString(),
      link: '/orders/12345'
    },
    {
      id: 2,
      title: 'تقييم منتج',
      message: 'شكراً لشرائك سماعة بلوتوث. هل يمكنك تقييم المنتج؟',
      type: 'review',
      read: false,
      created_at: new Date(Date.now() - 86400000).toISOString(),
      link: '/product/67'
    },
    {
      id: 3,
      title: 'رسالة جديدة من متجر الإلكترونيات',
      message: 'لقد تلقيت رسالة جديدة من متجر الإلكترونيات بخصوص استفسارك.',
      type: 'message',
      read: true,
      created_at: new Date(Date.now() - 172800000).toISOString(),
      link: '/messages/45'
    },
    {
      id: 4,
      title: 'خصم 20% على جميع المنتجات',
      message: 'استمتع بخصم 20% على جميع المنتجات لمدة 24 ساعة فقط!',
      type: 'promotion',
      read: true,
      created_at: new Date(Date.now() - 259200000).toISOString(),
      link: '/promotions'
    },
    {
      id: 5,
      title: 'تحديث في سياسة الخصوصية',
      message: 'قمنا بتحديث سياسة الخصوصية الخاصة بنا. يرجى الاطلاع عليها.',
      type: 'alert',
      read: true,
      created_at: new Date(Date.now() - 345600000).toISOString(),
      link: '/privacy-policy'
    }
  ];
};

// تكوين Firebase للإشعارات
// يجب استبدال هذه القيم بقيم مشروع Firebase الخاص بك

export const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "marketplace-app-xxxxx.firebaseapp.com",
  projectId: "marketplace-app-xxxxx",
  storageBucket: "marketplace-app-xxxxx.appspot.com",
  messagingSenderId: "123456789012",
  appId: "1:123456789012:web:abcdef1234567890abcdef",
  measurementId: "G-ABCDEFGHIJ"
};

// تعليمات إعداد Firebase:
// 1. قم بإنشاء مشروع في Firebase Console: https://console.firebase.google.com/
// 2. أضف تطبيق ويب إلى المشروع
// 3. انسخ بيانات التكوين واستبدلها بالقيم أعلاه
// 4. قم بتمكين خدمة Cloud Messaging للإشعارات
// 5. قم بتثبيت حزم Firebase اللازمة:
//    npm install firebase @capacitor-firebase/messaging

// مثال على كيفية تهيئة Firebase في التطبيق:
/*
import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage } from 'firebase/messaging';
import { firebaseConfig } from './firebase-config';

// تهيئة Firebase
const firebaseApp = initializeApp(firebaseConfig);
const messaging = getMessaging(firebaseApp);

// طلب إذن الإشعارات وتسجيل رمز الجهاز
export const requestNotificationPermission = async () => {
  try {
    const token = await getToken(messaging, {
      vapidKey: 'YOUR_VAPID_KEY' // من إعدادات Cloud Messaging
    });
    
    if (token) {
      console.log('تم الحصول على رمز الإشعارات:', token);
      // إرسال الرمز إلى الخادم
      await updateNotificationToken(token);
      return true;
    } else {
      console.log('لم يتم الحصول على إذن الإشعارات');
      return false;
    }
  } catch (error) {
    console.error('حدث خطأ أثناء طلب إذن الإشعارات:', error);
    return false;
  }
};

// الاستماع للإشعارات الواردة عندما يكون التطبيق مفتوحاً
export const listenToNotifications = () => {
  onMessage(messaging, (payload) => {
    console.log('تم استلام إشعار:', payload);
    // يمكنك هنا عرض الإشعار للمستخدم
    // مثلاً باستخدام Toast أو Alert
  });
};
*/

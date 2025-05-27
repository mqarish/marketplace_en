import axios from 'axios';
import { getToken } from './auth';

// تكوين الـ API الأساسي
const API_URL = 'http://localhost/marketplace/api';

// إنشاء نسخة من axios مع الإعدادات الافتراضية
const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// إضافة معترض للطلبات لإضافة رمز المصادقة
api.interceptors.request.use(
  async (config) => {
    const token = await getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// إضافة معترض للاستجابات للتعامل مع الأخطاء
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    // التعامل مع أخطاء المصادقة
    if (error.response && error.response.status === 401) {
      // إعادة توجيه المستخدم إلى صفحة تسجيل الدخول
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;

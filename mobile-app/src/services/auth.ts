import api from './api';

// واجهات البيانات
interface LoginData {
  email: string;
  password: string;
  user_type: 'customer' | 'store' | 'admin';
}

interface RegisterData {
  name: string;
  email: string;
  password: string;
  user_type: 'customer' | 'store';
}

interface AuthResponse {
  token: string;
  user: {
    id: number;
    name: string;
    email: string;
    user_type: string;
  };
}

// مفاتيح التخزين المحلي
const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';

// دالة تسجيل الدخول
export const login = async (data: LoginData): Promise<AuthResponse> => {
  try {
    const response = await api.post('/auth.php?action=login', data);
    
    // حفظ بيانات المصادقة في التخزين المحلي
    localStorage.setItem(TOKEN_KEY, response.data.token);
    localStorage.setItem(USER_KEY, JSON.stringify(response.data.user));
    
    return response.data;
  } catch (error) {
    throw error;
  }
};

// دالة التسجيل
export const register = async (data: RegisterData): Promise<AuthResponse> => {
  try {
    const response = await api.post('/auth.php?action=register', data);
    
    // حفظ بيانات المصادقة في التخزين المحلي
    localStorage.setItem(TOKEN_KEY, response.data.token);
    localStorage.setItem(USER_KEY, JSON.stringify(response.data.user));
    
    return response.data;
  } catch (error) {
    throw error;
  }
};

// دالة تسجيل الخروج
export const logout = (): void => {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
  window.location.href = '/login';
};

// دالة للتحقق مما إذا كان المستخدم مسجل الدخول
export const isAuthenticated = (): boolean => {
  return !!localStorage.getItem(TOKEN_KEY);
};

// دالة للحصول على رمز المصادقة
export const getToken = (): string | null => {
  return localStorage.getItem(TOKEN_KEY);
};

// دالة للحصول على بيانات المستخدم
export const getUser = (): any => {
  const user = localStorage.getItem(USER_KEY);
  return user ? JSON.parse(user) : null;
};

// دالة للتحقق مما إذا كان المستخدم من نوع معين
export const isUserType = (type: string): boolean => {
  const user = getUser();
  return user && user.user_type === type;
};

export default {
  login,
  register,
  logout,
  isAuthenticated,
  getToken,
  getUser,
  isUserType
};

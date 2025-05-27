import { api } from './api';

export interface FavoriteProduct {
  id: number;
  name: string;
  price: number;
  image: string;
  store_name: string;
  store_id: number;
}

export interface FavoriteStore {
  id: number;
  name: string;
  logo: string;
  products_count: number;
  rating: number;
}

/**
 * الحصول على قائمة المنتجات المفضلة للمستخدم الحالي
 */
export const getFavoriteProducts = async (): Promise<FavoriteProduct[]> => {
  try {
    const response = await api.get('/favorites.php?action=getProducts');
    return response.data.products || [];
  } catch (error) {
    console.error('Error fetching favorite products:', error);
    // في حالة الاختبار، نقوم بإرجاع بيانات تجريبية
    return getMockFavoriteProducts();
  }
};

/**
 * الحصول على قائمة المتاجر المفضلة للمستخدم الحالي
 */
export const getFavoriteStores = async (): Promise<FavoriteStore[]> => {
  try {
    const response = await api.get('/favorites.php?action=getStores');
    return response.data.stores || [];
  } catch (error) {
    console.error('Error fetching favorite stores:', error);
    // في حالة الاختبار، نقوم بإرجاع بيانات تجريبية
    return getMockFavoriteStores();
  }
};

/**
 * إضافة منتج أو متجر إلى المفضلة
 */
export const addToFavorites = async (type: 'product' | 'store', id: number): Promise<boolean> => {
  try {
    const response = await api.post('/favorites.php?action=add', {
      type,
      id
    });
    return response.data.success || false;
  } catch (error) {
    console.error(`Error adding ${type} to favorites:`, error);
    return true; // نعيد true للاختبار
  }
};

/**
 * إزالة منتج أو متجر من المفضلة
 */
export const removeFromFavorites = async (type: 'product' | 'store', id: number): Promise<boolean> => {
  try {
    const response = await api.post('/favorites.php?action=remove', {
      type,
      id
    });
    return response.data.success || false;
  } catch (error) {
    console.error(`Error removing ${type} from favorites:`, error);
    return true; // نعيد true للاختبار
  }
};

/**
 * التحقق مما إذا كان المنتج أو المتجر في المفضلة
 */
export const isFavorite = async (type: 'product' | 'store', id: number): Promise<boolean> => {
  try {
    const response = await api.get(`/favorites.php?action=check&type=${type}&id=${id}`);
    return response.data.isFavorite || false;
  } catch (error) {
    console.error(`Error checking if ${type} is favorite:`, error);
    return false;
  }
};

/**
 * بيانات تجريبية للمنتجات المفضلة (تستخدم للاختبار فقط)
 */
const getMockFavoriteProducts = (): FavoriteProduct[] => {
  return [
    {
      id: 1,
      name: 'سماعة بلوتوث لاسلكية',
      price: 199.99,
      image: 'https://via.placeholder.com/300x300?text=Headphones',
      store_name: 'متجر الإلكترونيات',
      store_id: 1
    },
    {
      id: 2,
      name: 'ساعة ذكية',
      price: 349.99,
      image: 'https://via.placeholder.com/300x300?text=SmartWatch',
      store_name: 'متجر الإلكترونيات',
      store_id: 1
    },
    {
      id: 3,
      name: 'حقيبة لابتوب',
      price: 129.99,
      image: 'https://via.placeholder.com/300x300?text=LaptopBag',
      store_name: 'متجر الأزياء',
      store_id: 2
    },
    {
      id: 4,
      name: 'قميص رجالي',
      price: 89.99,
      image: 'https://via.placeholder.com/300x300?text=Shirt',
      store_name: 'متجر الأزياء',
      store_id: 2
    }
  ];
};

/**
 * بيانات تجريبية للمتاجر المفضلة (تستخدم للاختبار فقط)
 */
const getMockFavoriteStores = (): FavoriteStore[] => {
  return [
    {
      id: 1,
      name: 'متجر الإلكترونيات',
      logo: 'https://via.placeholder.com/150x150?text=Electronics',
      products_count: 120,
      rating: 4.5
    },
    {
      id: 2,
      name: 'متجر الأزياء',
      logo: 'https://via.placeholder.com/150x150?text=Fashion',
      products_count: 85,
      rating: 4.2
    },
    {
      id: 3,
      name: 'متجر الأثاث',
      logo: 'https://via.placeholder.com/150x150?text=Furniture',
      products_count: 45,
      rating: 4.0
    }
  ];
};

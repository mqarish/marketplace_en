import api from './api';

// واجهات البيانات
export interface Store {
  id: number;
  name: string;
  description: string;
  logo: string;
  location: string;
  phone: string;
  products_count: number;
  avg_rating: number;
  created_at: string;
}

export interface StoresResponse {
  stores: Store[];
  pagination: {
    total: number;
    page: number;
    limit: number;
    total_pages: number;
  };
}

export interface StoreDetailResponse extends Store {
  products: {
    id: number;
    name: string;
    description: string;
    price: number;
    image: string;
    category_id: number;
    category_name: string;
    avg_rating: number;
    likes_count: number;
  }[];
}

export interface StoreFilter {
  search?: string;
  location?: string;
  order_by?: string;
  order_dir?: 'ASC' | 'DESC';
  page?: number;
  limit?: number;
}

// دالة للحصول على قائمة المتاجر
export const getStores = async (filters: StoreFilter = {}): Promise<StoresResponse> => {
  try {
    const response = await api.get('/stores.php', { params: filters });
    return response.data;
  } catch (error) {
    throw error;
  }
};

// دالة للحصول على متجر بواسطة المعرف
export const getStoreById = async (id: number): Promise<StoreDetailResponse> => {
  try {
    const response = await api.get(`/stores.php?id=${id}`);
    return response.data;
  } catch (error) {
    throw error;
  }
};

// دالة لتحديث معلومات المتجر
export const updateStore = async (storeData: Partial<Store>): Promise<any> => {
  try {
    const response = await api.put('/stores.php', storeData);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export default {
  getStores,
  getStoreById,
  updateStore
};

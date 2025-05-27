import api from './api';

// واجهات البيانات
export interface Product {
  id: number;
  name: string;
  description: string;
  price: number;
  image: string;
  category_id: number;
  category_name: string;
  store_id: number;
  store_name: string;
  quantity: number;
  avg_rating: number;
  likes_count: number;
  created_at: string;
}

export interface ProductsResponse {
  products: Product[];
  pagination: {
    total: number;
    page: number;
    limit: number;
    total_pages: number;
  };
}

export interface ProductDetailResponse extends Product {
  additional_images: {
    id: number;
    product_id: number;
    image_url: string;
  }[];
  reviews: {
    id: number;
    product_id: number;
    customer_id: number;
    customer_name: string;
    rating: number;
    comment: string;
    created_at: string;
  }[];
}

export interface ProductFilter {
  search?: string;
  category?: number;
  store?: number;
  min_price?: number;
  max_price?: number;
  order_by?: string;
  order_dir?: 'ASC' | 'DESC';
  page?: number;
  limit?: number;
}

// دالة للحصول على قائمة المنتجات
export const getProducts = async (filters: ProductFilter = {}): Promise<ProductsResponse> => {
  try {
    const response = await api.get('/products.php', { params: filters });
    return response.data;
  } catch (error) {
    throw error;
  }
};

// دالة للحصول على منتج بواسطة المعرف
export const getProductById = async (id: number): Promise<ProductDetailResponse> => {
  try {
    const response = await api.get(`/products.php?id=${id}`);
    return response.data;
  } catch (error) {
    throw error;
  }
};

// دالة لإضافة منتج جديد
export const addProduct = async (productData: Partial<Product>): Promise<Product> => {
  try {
    const response = await api.post('/products.php', productData);
    return response.data;
  } catch (error) {
    throw error;
  }
};

// دالة لتحديث منتج
export const updateProduct = async (id: number, productData: Partial<Product>): Promise<any> => {
  try {
    const response = await api.put('/products.php', { id, ...productData });
    return response.data;
  } catch (error) {
    throw error;
  }
};

// دالة لحذف منتج
export const deleteProduct = async (id: number): Promise<any> => {
  try {
    const response = await api.delete('/products.php', { data: { id } });
    return response.data;
  } catch (error) {
    throw error;
  }
};

// دالة لإضافة تقييم لمنتج
export const addReview = async (productId: number, rating: number, comment: string): Promise<any> => {
  try {
    const response = await api.post('/reviews.php', {
      product_id: productId,
      rating,
      comment
    });
    return response.data;
  } catch (error) {
    throw error;
  }
};

// دالة للإعجاب بمنتج
export const likeProduct = async (productId: number): Promise<any> => {
  try {
    const response = await api.post('/handle_like.php', {
      product_id: productId
    });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export default {
  getProducts,
  getProductById,
  addProduct,
  updateProduct,
  deleteProduct,
  addReview,
  likeProduct
};

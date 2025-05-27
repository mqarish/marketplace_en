import { api } from './api';

export interface Order {
  id: number;
  total_amount: number;
  status: string;
  created_at: string;
  items_count: number;
}

export interface OrderDetail {
  id: number;
  total_amount: number;
  shipping_address: {
    name: string;
    phone: string;
    address: string;
    city: string;
    postal_code: string;
  };
  payment_method: string;
  status: string;
  created_at: string;
  items: OrderItem[];
}

export interface OrderItem {
  id: number;
  product_id: number;
  product_name: string;
  product_image: string;
  quantity: number;
  price: number;
  subtotal: number;
}

export interface ShippingAddress {
  name: string;
  phone: string;
  address: string;
  city: string;
  postal_code: string;
}

export interface OrderCreateRequest {
  items: {
    product_id: number;
    quantity: number;
    price: number;
  }[];
  shipping_address: ShippingAddress;
  payment_method: string;
}

/**
 * إنشاء طلب جديد
 */
export const createOrder = async (orderData: OrderCreateRequest): Promise<{ order_id: number; total_amount: number }> => {
  try {
    const response = await api.post('/orders.php?action=create', orderData);
    return response.data;
  } catch (error) {
    console.error('Error creating order:', error);
    throw error;
  }
};

/**
 * الحصول على تفاصيل طلب محدد
 */
export const getOrderById = async (orderId: number): Promise<OrderDetail> => {
  try {
    const response = await api.get(`/orders.php?action=get&id=${orderId}`);
    return response.data.order;
  } catch (error) {
    console.error(`Error fetching order #${orderId}:`, error);
    throw error;
  }
};

/**
 * الحصول على قائمة الطلبات
 */
export const getOrders = async (page: number = 1, limit: number = 10): Promise<{ orders: Order[]; total: number; page: number; limit: number; total_pages: number }> => {
  try {
    const response = await api.get(`/orders.php?action=list&page=${page}&limit=${limit}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching orders:', error);
    // في حالة الاختبار، نقوم بإرجاع بيانات تجريبية
    return getMockOrders();
  }
};

/**
 * إلغاء طلب
 */
export const cancelOrder = async (orderId: number): Promise<boolean> => {
  try {
    const response = await api.post('/orders.php?action=cancel', {
      order_id: orderId
    });
    return response.data.success || false;
  } catch (error) {
    console.error(`Error cancelling order #${orderId}:`, error);
    return false;
  }
};

/**
 * بيانات تجريبية للطلبات (تستخدم للاختبار فقط)
 */
const getMockOrders = (): { orders: Order[]; total: number; page: number; limit: number; total_pages: number } => {
  const mockOrders: Order[] = [
    {
      id: 1001,
      total_amount: 549.97,
      status: 'delivered',
      created_at: '2025-04-30T14:30:00',
      items_count: 3
    },
    {
      id: 1002,
      total_amount: 129.99,
      status: 'shipped',
      created_at: '2025-05-02T10:15:00',
      items_count: 1
    },
    {
      id: 1003,
      total_amount: 789.50,
      status: 'processing',
      created_at: '2025-05-04T16:45:00',
      items_count: 4
    },
    {
      id: 1004,
      total_amount: 299.99,
      status: 'pending',
      created_at: '2025-05-05T09:20:00',
      items_count: 2
    }
  ];

  return {
    orders: mockOrders,
    total: mockOrders.length,
    page: 1,
    limit: 10,
    total_pages: 1
  };
};

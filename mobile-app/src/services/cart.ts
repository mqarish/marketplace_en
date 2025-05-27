import api from './api';
import { Product } from './products';

// واجهات البيانات
export interface CartItem {
  id: number;
  product_id: number;
  product: Product;
  quantity: number;
  price: number;
}

export interface Cart {
  items: CartItem[];
  total: number;
}

// مفتاح التخزين المحلي
const CART_KEY = 'shopping_cart';

// دالة للحصول على سلة التسوق
export const getCart = (): Cart => {
  const cartData = localStorage.getItem(CART_KEY);
  return cartData ? JSON.parse(cartData) : { items: [], total: 0 };
};

// دالة لحفظ سلة التسوق
const saveCart = (cart: Cart): void => {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
};

// دالة لإضافة منتج إلى سلة التسوق
export const addToCart = (product: Product, quantity: number = 1): Cart => {
  const cart = getCart();
  const existingItemIndex = cart.items.findIndex(item => item.product_id === product.id);
  
  if (existingItemIndex !== -1) {
    // إذا كان المنتج موجودًا بالفعل، قم بزيادة الكمية
    cart.items[existingItemIndex].quantity += quantity;
  } else {
    // إذا لم يكن المنتج موجودًا، أضفه إلى السلة
    const newItem: CartItem = {
      id: Date.now(), // معرف مؤقت
      product_id: product.id,
      product: product,
      quantity: quantity,
      price: product.price
    };
    cart.items.push(newItem);
  }
  
  // إعادة حساب المجموع
  cart.total = calculateTotal(cart.items);
  
  // حفظ السلة
  saveCart(cart);
  
  return cart;
};

// دالة لتحديث كمية منتج في سلة التسوق
export const updateCartItemQuantity = (itemId: number, quantity: number): Cart => {
  const cart = getCart();
  const itemIndex = cart.items.findIndex(item => item.id === itemId);
  
  if (itemIndex !== -1) {
    if (quantity <= 0) {
      // إذا كانت الكمية صفرًا أو أقل، قم بإزالة المنتج
      cart.items.splice(itemIndex, 1);
    } else {
      // تحديث الكمية
      cart.items[itemIndex].quantity = quantity;
    }
    
    // إعادة حساب المجموع
    cart.total = calculateTotal(cart.items);
    
    // حفظ السلة
    saveCart(cart);
  }
  
  return cart;
};

// دالة لإزالة منتج من سلة التسوق
export const removeFromCart = (itemId: number): Cart => {
  const cart = getCart();
  const itemIndex = cart.items.findIndex(item => item.id === itemId);
  
  if (itemIndex !== -1) {
    cart.items.splice(itemIndex, 1);
    
    // إعادة حساب المجموع
    cart.total = calculateTotal(cart.items);
    
    // حفظ السلة
    saveCart(cart);
  }
  
  return cart;
};

// دالة لتفريغ سلة التسوق
export const clearCart = (): Cart => {
  const emptyCart: Cart = { items: [], total: 0 };
  saveCart(emptyCart);
  return emptyCart;
};

// دالة لحساب المجموع
const calculateTotal = (items: CartItem[]): number => {
  return items.reduce((total, item) => total + (item.price * item.quantity), 0);
};

// دالة لإرسال الطلب
export const checkout = async (shippingAddress: any): Promise<any> => {
  try {
    const cart = getCart();
    
    // تحويل عناصر السلة إلى تنسيق مناسب للـ API
    const orderItems = cart.items.map(item => ({
      product_id: item.product_id,
      quantity: item.quantity,
      price: item.price
    }));
    
    const orderData = {
      items: orderItems,
      shipping_address: shippingAddress,
      total: cart.total
    };
    
    const response = await api.post('/orders.php', orderData);
    
    // تفريغ السلة بعد إتمام الطلب بنجاح
    clearCart();
    
    return response.data;
  } catch (error) {
    throw error;
  }
};

export default {
  getCart,
  addToCart,
  updateCartItemQuantity,
  removeFromCart,
  clearCart,
  checkout
};

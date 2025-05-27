import { Storage } from '@capacitor/storage';

// اسم المفتاح في التخزين المحلي
const COMPARE_STORAGE_KEY = 'product_compare_list';

/**
 * إضافة منتج إلى قائمة المقارنة
 * @param productId معرف المنتج
 * @returns وعد بنجاح العملية
 */
export const addToCompare = async (productId: number): Promise<boolean> => {
  try {
    // الحصول على القائمة الحالية
    const currentList = await getCompareList();
    
    // التحقق من وجود المنتج بالفعل في القائمة
    if (currentList.includes(productId)) {
      return true; // المنتج موجود بالفعل
    }
    
    // التحقق من عدم تجاوز الحد الأقصى (4 منتجات)
    if (currentList.length >= 4) {
      throw new Error('لا يمكن مقارنة أكثر من 4 منتجات في وقت واحد');
    }
    
    // إضافة المنتج إلى القائمة
    const newList = [...currentList, productId];
    
    // حفظ القائمة الجديدة
    await Storage.set({
      key: COMPARE_STORAGE_KEY,
      value: JSON.stringify(newList),
    });
    
    return true;
  } catch (error) {
    console.error('خطأ في إضافة المنتج إلى قائمة المقارنة:', error);
    throw error;
  }
};

/**
 * إزالة منتج من قائمة المقارنة
 * @param productId معرف المنتج
 * @returns وعد بنجاح العملية
 */
export const removeFromCompare = async (productId: number): Promise<boolean> => {
  try {
    // الحصول على القائمة الحالية
    const currentList = await getCompareList();
    
    // إزالة المنتج من القائمة
    const newList = currentList.filter(id => id !== productId);
    
    // حفظ القائمة الجديدة
    await Storage.set({
      key: COMPARE_STORAGE_KEY,
      value: JSON.stringify(newList),
    });
    
    return true;
  } catch (error) {
    console.error('خطأ في إزالة المنتج من قائمة المقارنة:', error);
    throw error;
  }
};

/**
 * الحصول على قائمة المنتجات للمقارنة
 * @returns وعد بقائمة معرفات المنتجات
 */
export const getCompareList = async (): Promise<number[]> => {
  try {
    const { value } = await Storage.get({ key: COMPARE_STORAGE_KEY });
    
    if (!value) {
      return [];
    }
    
    return JSON.parse(value);
  } catch (error) {
    console.error('خطأ في الحصول على قائمة المقارنة:', error);
    return [];
  }
};

/**
 * التحقق مما إذا كان المنتج موجودًا في قائمة المقارنة
 * @param productId معرف المنتج
 * @returns وعد بنتيجة التحقق
 */
export const isInCompareList = async (productId: number): Promise<boolean> => {
  try {
    const compareList = await getCompareList();
    return compareList.includes(productId);
  } catch (error) {
    console.error('خطأ في التحقق من وجود المنتج في قائمة المقارنة:', error);
    return false;
  }
};

/**
 * مسح قائمة المقارنة بالكامل
 * @returns وعد بنجاح العملية
 */
export const clearCompareList = async (): Promise<boolean> => {
  try {
    await Storage.set({
      key: COMPARE_STORAGE_KEY,
      value: JSON.stringify([]),
    });
    
    return true;
  } catch (error) {
    console.error('خطأ في مسح قائمة المقارنة:', error);
    throw error;
  }
};

/**
 * الحصول على عدد المنتجات في قائمة المقارنة
 * @returns وعد بعدد المنتجات
 */
export const getCompareCount = async (): Promise<number> => {
  try {
    const compareList = await getCompareList();
    return compareList.length;
  } catch (error) {
    console.error('خطأ في الحصول على عدد منتجات المقارنة:', error);
    return 0;
  }
};

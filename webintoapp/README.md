# سوق المتجر - نسخة WebIntoApp

هذا المجلد يحتوي على الملفات اللازمة لتحويل تطبيق سوق المتجر إلى تطبيق جوال باستخدام خدمة WebIntoApp.

## المحتويات

- `index.html`: الصفحة الرئيسية للتطبيق
- `styles.css`: ملف التنسيقات
- `app.js`: ملف الوظائف والتفاعلات
- `manifest.json`: ملف تكوين التطبيق
- `images/`: مجلد الصور والأيقونات

## خطوات التحويل إلى تطبيق

1. قم بضغط محتويات هذا المجلد في ملف ZIP
2. قم بزيارة [WebIntoApp](https://www.webintoapp.com/app-maker)
3. قم برفع ملف ZIP
4. أكمل معلومات التطبيق (الاسم، الوصف، الأيقونة، إلخ)
5. قم بتنزيل ملف APK للأندرويد أو قم بإنشاء حساب مطور Apple لنشر التطبيق على متجر App Store

## ملاحظات مهمة

- يجب تغيير قيمة `API_URL` في ملف `app.js` لتشير إلى عنوان API الخاص بك
- يمكنك استبدال الصور في مجلد `images/` بصور خاصة بك
- تأكد من إنشاء أيقونات بالأحجام المحددة في ملف `manifest.json`

## الميزات المدعومة

- واجهة مستخدم متجاوبة
- تصفح المنتجات والتصنيفات
- البحث عن المنتجات
- سلة التسوق
- المفضلة
- إدارة الحساب
- تتبع الطلبات

## التخصيص

يمكنك تخصيص الألوان والتنسيقات من خلال تعديل المتغيرات في ملف `styles.css`:

```css
:root {
    --primary-color: #FF7A00;
    --secondary-color: #333333;
    --background-color: #f8f8f8;
    --text-color: #333333;
    --light-gray: #e0e0e0;
    --dark-gray: #666666;
    --white: #ffffff;
    --success: #4CAF50;
    --danger: #F44336;
    --warning: #FFC107;
    --info: #2196F3;
}
```

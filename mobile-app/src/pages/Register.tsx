import React, { useState } from 'react';
import { useHistory } from 'react-router-dom';
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonItem,
  IonLabel,
  IonInput,
  IonButton,
  IonIcon,
  IonSelect,
  IonSelectOption,
  IonText,
  IonLoading,
  IonBackButton,
  IonButtons,
  IonSegment,
  IonSegmentButton
} from '@ionic/react';
import { personAddOutline, personOutline, lockClosedOutline, mailOutline, phonePortraitOutline, locationOutline, storefrontOutline } from 'ionicons/icons';
import { register } from '../services/auth';
import './Register.css';

const Register: React.FC = () => {
  const history = useHistory();
  const [userType, setUserType] = useState<'customer' | 'store'>('customer');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [phone, setPhone] = useState('');
  const [location, setLocation] = useState('');
  const [description, setDescription] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const validateForm = () => {
    // التحقق من إدخال الحقول المطلوبة
    if (!name || !email || !password || !confirmPassword) {
      setError('يرجى ملء جميع الحقول المطلوبة');
      return false;
    }

    // التحقق من تطابق كلمتي المرور
    if (password !== confirmPassword) {
      setError('كلمتا المرور غير متطابقتين');
      return false;
    }

    // التحقق من صحة البريد الإلكتروني
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      setError('يرجى إدخال بريد إلكتروني صحيح');
      return false;
    }

    // التحقق من طول كلمة المرور
    if (password.length < 6) {
      setError('يجب أن تحتوي كلمة المرور على 6 أحرف على الأقل');
      return false;
    }

    // إذا كان نوع الحساب متجر، تحقق من إدخال الهاتف والموقع
    if (userType === 'store' && (!phone || !location)) {
      setError('يرجى إدخال رقم الهاتف والموقع للمتجر');
      return false;
    }

    return true;
  };

  const handleRegister = async () => {
    // التحقق من صحة النموذج
    if (!validateForm()) {
      return;
    }

    try {
      setLoading(true);
      setError('');
      
      // إعداد بيانات التسجيل
      const userData: any = {
        name,
        email,
        password,
        user_type: userType
      };

      // إضافة بيانات إضافية للمتجر
      if (userType === 'store') {
        userData.phone = phone;
        userData.location = location;
        userData.description = description;
      }
      
      // محاولة التسجيل
      await register(userData);
      
      // إعادة توجيه المستخدم بناءً على نوع الحساب
      if (userType === 'customer') {
        history.replace('/home');
      } else {
        history.replace('/profile'); // صفحة لوحة تحكم المتجر
      }
    } catch (error) {
      console.error('Registration error:', error);
      setError('فشل التسجيل. قد يكون البريد الإلكتروني مستخدمًا بالفعل.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonButtons slot="start">
            <IonBackButton defaultHref="/login" />
          </IonButtons>
          <IonTitle>إنشاء حساب جديد</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        <div className="register-container">
          <div className="logo-container">
            <div className="logo">
              <IonIcon icon={personAddOutline} color="light" />
            </div>
            <h1>إنشاء حساب جديد</h1>
            <p>انضم إلى منصة سوق للتسوق الإلكتروني</p>
          </div>

          <div className="form-container">
            {error && (
              <IonText color="danger" className="error-message">
                <p>{error}</p>
              </IonText>
            )}

            <IonSegment value={userType} onIonChange={e => setUserType(e.detail.value as 'customer' | 'store')}>
              <IonSegmentButton value="customer">
                <IonLabel>عميل</IonLabel>
              </IonSegmentButton>
              <IonSegmentButton value="store">
                <IonLabel>متجر</IonLabel>
              </IonSegmentButton>
            </IonSegment>

            <IonItem>
              <IonIcon icon={personOutline} slot="start" color="medium" />
              <IonLabel position="floating">الاسم {userType === 'store' ? 'التجاري' : 'الكامل'}</IonLabel>
              <IonInput
                value={name}
                onIonChange={e => setName(e.detail.value!)}
                required
              />
            </IonItem>

            <IonItem>
              <IonIcon icon={mailOutline} slot="start" color="medium" />
              <IonLabel position="floating">البريد الإلكتروني</IonLabel>
              <IonInput
                type="email"
                value={email}
                onIonChange={e => setEmail(e.detail.value!)}
                required
              />
            </IonItem>

            <IonItem>
              <IonIcon icon={lockClosedOutline} slot="start" color="medium" />
              <IonLabel position="floating">كلمة المرور</IonLabel>
              <IonInput
                type="password"
                value={password}
                onIonChange={e => setPassword(e.detail.value!)}
                required
              />
            </IonItem>

            <IonItem>
              <IonIcon icon={lockClosedOutline} slot="start" color="medium" />
              <IonLabel position="floating">تأكيد كلمة المرور</IonLabel>
              <IonInput
                type="password"
                value={confirmPassword}
                onIonChange={e => setConfirmPassword(e.detail.value!)}
                required
              />
            </IonItem>

            {userType === 'store' && (
              <>
                <IonItem>
                  <IonIcon icon={phonePortraitOutline} slot="start" color="medium" />
                  <IonLabel position="floating">رقم الهاتف</IonLabel>
                  <IonInput
                    type="tel"
                    value={phone}
                    onIonChange={e => setPhone(e.detail.value!)}
                  />
                </IonItem>

                <IonItem>
                  <IonIcon icon={locationOutline} slot="start" color="medium" />
                  <IonLabel position="floating">الموقع</IonLabel>
                  <IonInput
                    value={location}
                    onIonChange={e => setLocation(e.detail.value!)}
                  />
                </IonItem>

                <IonItem>
                  <IonIcon icon={storefrontOutline} slot="start" color="medium" />
                  <IonLabel position="floating">وصف المتجر</IonLabel>
                  <IonInput
                    value={description}
                    onIonChange={e => setDescription(e.detail.value!)}
                  />
                </IonItem>
              </>
            )}

            <IonButton
              expand="block"
              color="warning"
              onClick={handleRegister}
              className="register-button"
            >
              <IonIcon slot="start" icon={personAddOutline} />
              إنشاء حساب
            </IonButton>

            <div className="login-link">
              <p>لديك حساب بالفعل؟ <IonButton fill="clear" routerLink="/login">تسجيل الدخول</IonButton></p>
            </div>
          </div>
        </div>

        <IonLoading
          isOpen={loading}
          message={'جاري إنشاء الحساب...'}
        />
      </IonContent>
    </IonPage>
  );
};

export default Register;

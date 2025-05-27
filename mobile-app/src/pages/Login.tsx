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
  IonButtons
} from '@ionic/react';
import { logInOutline, personOutline, lockClosedOutline, storefrontOutline } from 'ionicons/icons';
import { login } from '../services/auth';
import './Login.css';

const Login: React.FC = () => {
  const history = useHistory();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [userType, setUserType] = useState<'customer' | 'store'>('customer');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = async () => {
    // التحقق من صحة المدخلات
    if (!email || !password) {
      setError('يرجى إدخال البريد الإلكتروني وكلمة المرور');
      return;
    }

    try {
      setLoading(true);
      setError('');
      
      // محاولة تسجيل الدخول
      await login({
        email,
        password,
        user_type: userType
      });
      
      // إعادة توجيه المستخدم بناءً على نوع الحساب
      if (userType === 'customer') {
        history.replace('/home');
      } else {
        history.replace('/profile'); // صفحة لوحة تحكم المتجر
      }
    } catch (error) {
      console.error('Login error:', error);
      setError('فشل تسجيل الدخول. يرجى التحقق من بيانات الاعتماد الخاصة بك.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonButtons slot="start">
            <IonBackButton defaultHref="/home" />
          </IonButtons>
          <IonTitle>تسجيل الدخول</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        <div className="login-container">
          <div className="logo-container">
            <div className="logo">
              <IonIcon icon={storefrontOutline} color="light" />
            </div>
            <h1>سوق</h1>
            <p>منصة التسوق الإلكتروني</p>
          </div>

          <div className="form-container">
            {error && (
              <IonText color="danger" className="error-message">
                <p>{error}</p>
              </IonText>
            )}

            <IonItem>
              <IonIcon icon={personOutline} slot="start" color="medium" />
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
              <IonLabel>نوع الحساب</IonLabel>
              <IonSelect
                value={userType}
                onIonChange={e => setUserType(e.detail.value)}
                interface="popover"
              >
                <IonSelectOption value="customer">عميل</IonSelectOption>
                <IonSelectOption value="store">متجر</IonSelectOption>
              </IonSelect>
            </IonItem>

            <IonButton
              expand="block"
              color="warning"
              onClick={handleLogin}
              className="login-button"
            >
              <IonIcon slot="start" icon={logInOutline} />
              تسجيل الدخول
            </IonButton>

            <div className="register-link">
              <p>ليس لديك حساب؟ <IonButton fill="clear" routerLink="/register">إنشاء حساب جديد</IonButton></p>
            </div>
          </div>
        </div>

        <IonLoading
          isOpen={loading}
          message={'جاري تسجيل الدخول...'}
        />
      </IonContent>
    </IonPage>
  );
};

export default Login;

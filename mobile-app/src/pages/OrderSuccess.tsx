import React from 'react';
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonButton,
  IonIcon,
  IonText
} from '@ionic/react';
import { checkmarkCircleOutline, homeOutline, cartOutline } from 'ionicons/icons';
import './OrderSuccess.css';

const OrderSuccess: React.FC = () => {
  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonTitle>تأكيد الطلب</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        <div className="success-container">
          <div className="success-icon">
            <IonIcon icon={checkmarkCircleOutline} color="success" />
          </div>
          <h1>تم تأكيد طلبك بنجاح!</h1>
          <p>شكراً لك على الطلب. سنقوم بمعالجة طلبك في أقرب وقت ممكن.</p>
          
          <div className="order-details">
            <div className="order-detail-item">
              <span className="label">رقم الطلب:</span>
              <span className="value">#ORD-{Math.floor(100000 + Math.random() * 900000)}</span>
            </div>
            <div className="order-detail-item">
              <span className="label">تاريخ الطلب:</span>
              <span className="value">{new Date().toLocaleDateString('ar-SA')}</span>
            </div>
            <div className="order-detail-item">
              <span className="label">حالة الطلب:</span>
              <span className="value status-pending">قيد المعالجة</span>
            </div>
          </div>
          
          <IonText color="medium" className="info-text">
            <p>سيتم إرسال تفاصيل الطلب إلى بريدك الإلكتروني.</p>
            <p>يمكنك متابعة حالة طلبك من صفحة "طلباتي" في الملف الشخصي.</p>
          </IonText>
          
          <div className="action-buttons">
            <IonButton routerLink="/home" expand="block" color="light">
              <IonIcon slot="start" icon={homeOutline} />
              العودة للرئيسية
            </IonButton>
            <IonButton routerLink="/profile" expand="block" color="warning">
              <IonIcon slot="start" icon={cartOutline} />
              متابعة طلباتي
            </IonButton>
          </div>
        </div>
      </IonContent>
    </IonPage>
  );
};

export default OrderSuccess;

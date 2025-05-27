import React, { useEffect, useState } from 'react';
import { useHistory } from 'react-router-dom';
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonList,
  IonItem,
  IonLabel,
  IonIcon,
  IonButton,
  IonAvatar,
  IonImg,
  IonCard,
  IonCardHeader,
  IonCardContent,
  IonCardTitle,
  IonGrid,
  IonRow,
  IonCol,
  IonSegment,
  IonSegmentButton,
  IonBadge,
  IonAlert,
  IonSkeletonText,
  IonText
} from '@ionic/react';
import { 
  personOutline, 
  logOutOutline, 
  settingsOutline, 
  cartOutline, 
  heartOutline, 
  starOutline,
  storefrontOutline,
  addOutline,
  statsChartOutline,
  notificationsOutline,
  helpCircleOutline,
  lockClosedOutline
} from 'ionicons/icons';
import { isAuthenticated, getUser, logout, isUserType } from '../services/auth';
import './Profile.css';

const Profile: React.FC = () => {
  const history = useHistory();
  const [user, setUser] = useState<any>(null);
  const [activeTab, setActiveTab] = useState<'orders' | 'favorites' | 'reviews'>('orders');
  const [showLogoutAlert, setShowLogoutAlert] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // التحقق مما إذا كان المستخدم مسجل الدخول
    if (!isAuthenticated()) {
      history.replace('/login');
      return;
    }

    // جلب بيانات المستخدم
    const userData = getUser();
    setUser(userData);
    setLoading(false);
  }, [history]);

  // دالة لتسجيل الخروج
  const handleLogout = () => {
    logout();
    history.replace('/login');
  };

  // دالة لعرض المحتوى المناسب حسب نوع المستخدم
  const renderContent = () => {
    if (loading) {
      return (
        <div className="loading-container">
          <IonSkeletonText animated style={{ width: '80%', height: '30px', margin: '10px auto' }} />
          <IonSkeletonText animated style={{ width: '60%', height: '20px', margin: '10px auto' }} />
          <IonSkeletonText animated style={{ width: '90%', height: '150px', margin: '20px auto' }} />
        </div>
      );
    }

    if (!user) {
      return (
        <div className="not-logged-in">
          <IonText color="medium">
            <h2>يرجى تسجيل الدخول</h2>
            <p>يجب عليك تسجيل الدخول لعرض الملف الشخصي.</p>
          </IonText>
          <IonButton routerLink="/login" expand="block">
            تسجيل الدخول
          </IonButton>
        </div>
      );
    }

    // محتوى مختلف حسب نوع المستخدم
    if (isUserType('store')) {
      return renderStoreProfile();
    } else {
      return renderCustomerProfile();
    }
  };

  // عرض ملف العميل
  const renderCustomerProfile = () => {
    return (
      <>
        <div className="profile-header">
          <IonAvatar className="profile-avatar">
            <IonImg src="https://gravatar.com/avatar/dba6bae8c566f9d4041fb9cd9ada7741?d=identicon&f=y" />
          </IonAvatar>
          <h2>{user.name}</h2>
          <p>{user.email}</p>
        </div>

        <IonSegment value={activeTab} onIonChange={e => setActiveTab(e.detail.value as any)}>
          <IonSegmentButton value="orders">
            <IonLabel>طلباتي</IonLabel>
          </IonSegmentButton>
          <IonSegmentButton value="favorites">
            <IonLabel>المفضلة</IonLabel>
          </IonSegmentButton>
          <IonSegmentButton value="reviews">
            <IonLabel>تقييماتي</IonLabel>
          </IonSegmentButton>
        </IonSegment>

        <div className="tab-content">
          {activeTab === 'orders' && (
            <div className="orders-tab">
              <div className="empty-state">
                <IonIcon icon={cartOutline} />
                <h3>لا توجد طلبات</h3>
                <p>لم تقم بإجراء أي طلبات بعد.</p>
                <IonButton routerLink="/products" color="warning">تسوق الآن</IonButton>
              </div>
            </div>
          )}

          {activeTab === 'favorites' && (
            <div className="favorites-tab">
              <div className="empty-state">
                <IonIcon icon={heartOutline} />
                <h3>لا توجد منتجات مفضلة</h3>
                <p>لم تقم بإضافة أي منتجات إلى المفضلة بعد.</p>
                <IonButton routerLink="/products" color="warning">تصفح المنتجات</IonButton>
              </div>
            </div>
          )}

          {activeTab === 'reviews' && (
            <div className="reviews-tab">
              <div className="empty-state">
                <IonIcon icon={starOutline} />
                <h3>لا توجد تقييمات</h3>
                <p>لم تقم بإضافة أي تقييمات بعد.</p>
                <IonButton routerLink="/products" color="warning">تصفح المنتجات</IonButton>
              </div>
            </div>
          )}
        </div>

        <IonList className="profile-menu">
          <IonItem button routerLink="/settings">
            <IonIcon slot="start" icon={settingsOutline} color="medium" />
            <IonLabel>إعدادات الحساب</IonLabel>
          </IonItem>
          <IonItem button routerLink="/notifications">
            <IonIcon slot="start" icon={notificationsOutline} color="medium" />
            <IonLabel>الإشعارات</IonLabel>
            <IonBadge color="danger" slot="end">3</IonBadge>
          </IonItem>
          <IonItem button routerLink="/help">
            <IonIcon slot="start" icon={helpCircleOutline} color="medium" />
            <IonLabel>المساعدة والدعم</IonLabel>
          </IonItem>
          <IonItem button routerLink="/privacy">
            <IonIcon slot="start" icon={lockClosedOutline} color="medium" />
            <IonLabel>الخصوصية والأمان</IonLabel>
          </IonItem>
          <IonItem button onClick={() => setShowLogoutAlert(true)}>
            <IonIcon slot="start" icon={logOutOutline} color="danger" />
            <IonLabel color="danger">تسجيل الخروج</IonLabel>
          </IonItem>
        </IonList>
      </>
    );
  };

  // عرض ملف المتجر
  const renderStoreProfile = () => {
    return (
      <>
        <div className="store-profile-header">
          <div className="store-logo-container">
            <IonImg src="https://gravatar.com/avatar/dba6bae8c566f9d4041fb9cd9ada7741?d=identicon&f=y" className="store-logo" />
          </div>
          <h2>{user.name}</h2>
          <p>{user.email}</p>
        </div>

        <div className="store-stats">
          <IonGrid>
            <IonRow>
              <IonCol>
                <IonCard className="stat-card">
                  <IonCardContent>
                    <div className="stat-icon">
                      <IonIcon icon={cartOutline} color="warning" />
                    </div>
                    <div className="stat-value">0</div>
                    <div className="stat-label">الطلبات</div>
                  </IonCardContent>
                </IonCard>
              </IonCol>
              <IonCol>
                <IonCard className="stat-card">
                  <IonCardContent>
                    <div className="stat-icon">
                      <IonIcon icon={storefrontOutline} color="warning" />
                    </div>
                    <div className="stat-value">0</div>
                    <div className="stat-label">المنتجات</div>
                  </IonCardContent>
                </IonCard>
              </IonCol>
              <IonCol>
                <IonCard className="stat-card">
                  <IonCardContent>
                    <div className="stat-icon">
                      <IonIcon icon={starOutline} color="warning" />
                    </div>
                    <div className="stat-value">0</div>
                    <div className="stat-label">التقييمات</div>
                  </IonCardContent>
                </IonCard>
              </IonCol>
            </IonRow>
          </IonGrid>
        </div>

        <IonCard className="store-actions">
          <IonCardHeader>
            <IonCardTitle>إدارة المتجر</IonCardTitle>
          </IonCardHeader>
          <IonCardContent>
            <IonButton expand="block" routerLink="/store/add-product">
              <IonIcon slot="start" icon={addOutline} />
              إضافة منتج جديد
            </IonButton>
            <IonButton expand="block" routerLink="/store/products" fill="outline" className="mt-10">
              <IonIcon slot="start" icon={storefrontOutline} />
              إدارة المنتجات
            </IonButton>
            <IonButton expand="block" routerLink="/store/orders" fill="outline" className="mt-10">
              <IonIcon slot="start" icon={cartOutline} />
              إدارة الطلبات
            </IonButton>
            <IonButton expand="block" routerLink="/store/stats" fill="outline" className="mt-10">
              <IonIcon slot="start" icon={statsChartOutline} />
              إحصائيات المتجر
            </IonButton>
          </IonCardContent>
        </IonCard>

        <IonList className="profile-menu">
          <IonItem button routerLink="/store/settings">
            <IonIcon slot="start" icon={settingsOutline} color="medium" />
            <IonLabel>إعدادات المتجر</IonLabel>
          </IonItem>
          <IonItem button routerLink="/notifications">
            <IonIcon slot="start" icon={notificationsOutline} color="medium" />
            <IonLabel>الإشعارات</IonLabel>
            <IonBadge color="danger" slot="end">2</IonBadge>
          </IonItem>
          <IonItem button routerLink="/help">
            <IonIcon slot="start" icon={helpCircleOutline} color="medium" />
            <IonLabel>المساعدة والدعم</IonLabel>
          </IonItem>
          <IonItem button onClick={() => setShowLogoutAlert(true)}>
            <IonIcon slot="start" icon={logOutOutline} color="danger" />
            <IonLabel color="danger">تسجيل الخروج</IonLabel>
          </IonItem>
        </IonList>
      </>
    );
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonTitle>الملف الشخصي</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        {renderContent()}
        
        <IonAlert
          isOpen={showLogoutAlert}
          onDidDismiss={() => setShowLogoutAlert(false)}
          header="تأكيد"
          message="هل أنت متأكد من رغبتك في تسجيل الخروج؟"
          buttons={[
            {
              text: 'إلغاء',
              role: 'cancel'
            },
            {
              text: 'تسجيل الخروج',
              handler: handleLogout
            }
          ]}
        />
      </IonContent>
    </IonPage>
  );
};

export default Profile;

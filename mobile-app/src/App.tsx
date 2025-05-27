import { Redirect, Route } from 'react-router-dom';
import {
  IonApp,
  IonIcon,
  IonLabel,
  IonRouterOutlet,
  IonTabBar,
  IonTabButton,
  IonTabs,
  setupIonicReact
} from '@ionic/react';
import { IonReactRouter } from '@ionic/react-router';
import { homeOutline, searchOutline, cartOutline, personOutline, storefront, notificationsOutline, heartOutline } from 'ionicons/icons';

// صفحات التطبيق
import Home from './pages/Home';
import ProductList from './pages/ProductList';
import ProductDetail from './pages/ProductDetail';
import StoreList from './pages/StoreList';
import StoreDetail from './pages/StoreDetail';
import Cart from './pages/Cart';
import Profile from './pages/Profile';
import Login from './pages/Login';
import Register from './pages/Register';
import Notifications from './pages/Notifications';
import Favorites from './pages/Favorites';
import OrderSuccess from './pages/OrderSuccess';
import Orders from './pages/Orders';
import OrderDetail from './pages/OrderDetail';
import AdvancedSearch from './pages/AdvancedSearch';
import ProductCompare from './pages/ProductCompare';

/* ملفات CSS الأساسية */
import '@ionic/react/css/core.css';
import '@ionic/react/css/normalize.css';
import '@ionic/react/css/structure.css';
import '@ionic/react/css/typography.css';
import '@ionic/react/css/padding.css';
import '@ionic/react/css/float-elements.css';
import '@ionic/react/css/text-alignment.css';
import '@ionic/react/css/text-transformation.css';
import '@ionic/react/css/flex-utils.css';
import '@ionic/react/css/display.css';

/* ملفات CSS المخصصة */
import './theme/variables.css';
import './theme/app.css';

setupIonicReact();

const App: React.FC = () => (
  <IonApp>
    <IonReactRouter>
      <IonTabs>
        <IonRouterOutlet>
          <Route exact path="/home" component={Home} />
          <Route exact path="/products" component={ProductList} />
          <Route path="/product/:id" component={ProductDetail} />
          <Route exact path="/stores" component={StoreList} />
          <Route path="/store/:id" component={StoreDetail} />
          <Route path="/cart" component={Cart} />
          <Route path="/profile" component={Profile} />
          <Route path="/login" component={Login} />
          <Route path="/register" component={Register} />
          <Route path="/notifications" component={Notifications} />
          <Route path="/favorites" component={Favorites} />
          <Route path="/order-success" component={OrderSuccess} />
          <Route exact path="/orders" component={Orders} />
          <Route path="/order/:id" component={OrderDetail} />
          <Route path="/advanced-search" component={AdvancedSearch} />
          <Route path="/product-compare" component={ProductCompare} />
          <Route exact path="/">
            <Redirect to="/home" />
          </Route>
        </IonRouterOutlet>
        <IonTabBar slot="bottom">
          <IonTabButton tab="home" href="/home">
            <IonIcon icon={homeOutline} />
            <IonLabel>الرئيسية</IonLabel>
          </IonTabButton>
          <IonTabButton tab="products" href="/products">
            <IonIcon icon={searchOutline} />
            <IonLabel>المنتجات</IonLabel>
          </IonTabButton>
          <IonTabButton tab="stores" href="/stores">
            <IonIcon icon={storefront} />
            <IonLabel>المتاجر</IonLabel>
          </IonTabButton>
          <IonTabButton tab="cart" href="/cart">
            <IonIcon icon={cartOutline} />
            <IonLabel>السلة</IonLabel>
          </IonTabButton>
          <IonTabButton tab="profile" href="/profile">
            <IonIcon icon={personOutline} />
            <IonLabel>حسابي</IonLabel>
          </IonTabButton>
        </IonTabBar>
      </IonTabs>
    </IonReactRouter>
  </IonApp>
);

export default App;

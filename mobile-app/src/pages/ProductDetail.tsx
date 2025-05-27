import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router';
import {
  IonContent,
  IonHeader,
  IonPage,
  IonTitle,
  IonToolbar,
  IonBackButton,
  IonButtons,
  IonSlides,
  IonSlide,
  IonImg,
  IonButton,
  IonIcon,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonItem,
  IonLabel,
  IonBadge,
  IonGrid,
  IonRow,
  IonCol,
  IonSkeletonText,
  IonSpinner,
  IonText,
  IonChip,
  IonAvatar,
  IonTextarea,
  IonInput,
  IonToast
} from '@ionic/react';
import { 
  heartOutline, 
  heart, 
  cartOutline, 
  addOutline, 
  removeOutline, 
  starOutline, 
  star,
  chatbubbleOutline,
  storefront,
  swapHorizontalOutline
} from 'ionicons/icons';
import { getProductById, ProductDetailResponse, addReview, likeProduct } from '../services/products';
import { addToCart } from '../services/cart';
import { isAuthenticated, getUser } from '../services/auth';
import { addToCompare, isInCompareList } from '../services/compare';
import './ProductDetail.css';

const ProductDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [product, setProduct] = useState<ProductDetailResponse | null>(null);
  const [loading, setLoading] = useState(true);
  const [quantity, setQuantity] = useState(1);
  const [liked, setLiked] = useState(false);
  const [showReviewForm, setShowReviewForm] = useState(false);
  const [rating, setRating] = useState(0);
  const [review, setReview] = useState('');
  const [submittingReview, setSubmittingReview] = useState(false);
  const [showToast, setShowToast] = useState(false);
  const [toastMessage, setToastMessage] = useState('');
  const [inCompareList, setInCompareList] = useState(false);

  // إعدادات السلايدر
  const slideOpts = {
    initialSlide: 0,
    speed: 400,
    zoom: true
  };

  // جلب بيانات المنتج
  useEffect(() => {
    const fetchProduct = async () => {
      try {
        setLoading(true);
        const data = await getProductById(parseInt(id));
        setProduct(data);
        setLoading(false);
      } catch (error) {
        console.error('Error fetching product:', error);
        setLoading(false);
      }
    };

    fetchProduct();
  }, [id]);

  // التحقق مما إذا كان المستخدم قد أعجب بالمنتج بالفعل
  useEffect(() => {
    if (isAuthenticated() && product) {
      // في التطبيق الفعلي، يجب التحقق من قاعدة البيانات
      // هذا مجرد مثال بسيط
      setLiked(false);
    }
  }, [product]);

  // التحقق مما إذا كان المنتج موجوداً في قائمة المقارنة
  useEffect(() => {
    const checkCompareStatus = async () => {
      if (product) {
        const isCompared = await isInCompareList(product.id);
        setInCompareList(isCompared);
      }
    };
    
    checkCompareStatus();
  }, [product]);

  // دالة لزيادة الكمية
  const increaseQuantity = () => {
    if (product && quantity < product.quantity) {
      setQuantity(quantity + 1);
    }
  };

  // دالة لتقليل الكمية
  const decreaseQuantity = () => {
    if (quantity > 1) {
      setQuantity(quantity - 1);
    }
  };

  // دالة لإضافة المنتج إلى سلة التسوق
  const handleAddToCart = () => {
    if (product) {
      addToCart(product, quantity);
      setToastMessage('تمت إضافة المنتج إلى سلة التسوق');
      setShowToast(true);
    }
  };

  // دالة لإضافة المنتج إلى قائمة المقارنة
  const handleAddToCompare = async () => {
    if (product) {
      try {
        await addToCompare(product.id);
        setInCompareList(true);
        setToastMessage('تمت إضافة المنتج إلى قائمة المقارنة');
        setShowToast(true);
      } catch (error: any) {
        console.error('Error adding product to compare:', error);
        setToastMessage(error.message || 'حدث خطأ أثناء إضافة المنتج إلى قائمة المقارنة');
        setShowToast(true);
      }
    }
  };

  // دالة للإعجاب بالمنتج
  const handleLike = async () => {
    if (!isAuthenticated()) {
      setToastMessage('يرجى تسجيل الدخول أولاً');
      setShowToast(true);
      return;
    }

    try {
      if (product) {
        await likeProduct(product.id);
        setLiked(!liked);
        
        // تحديث عدد الإعجابات
        if (product) {
          setProduct({
            ...product,
            likes_count: liked ? product.likes_count - 1 : product.likes_count + 1
          });
        }
      }
    } catch (error) {
      console.error('Error liking product:', error);
      setToastMessage('حدث خطأ أثناء الإعجاب بالمنتج');
      setShowToast(true);
    }
  };

  // دالة لإرسال تقييم
  const submitReview = async () => {
    if (!isAuthenticated()) {
      setToastMessage('يرجى تسجيل الدخول أولاً');
      setShowToast(true);
      return;
    }

    if (rating === 0) {
      setToastMessage('يرجى اختيار تقييم');
      setShowToast(true);
      return;
    }

    try {
      setSubmittingReview(true);
      await addReview(parseInt(id), rating, review);
      
      // إعادة تحميل المنتج للحصول على التقييمات المحدثة
      const updatedProduct = await getProductById(parseInt(id));
      setProduct(updatedProduct);
      
      // إعادة تعيين نموذج التقييم
      setRating(0);
      setReview('');
      setShowReviewForm(false);
      setSubmittingReview(false);
      
      setToastMessage('تم إضافة تقييمك بنجاح');
      setShowToast(true);
    } catch (error) {
      console.error('Error submitting review:', error);
      setSubmittingReview(false);
      setToastMessage('حدث خطأ أثناء إرسال التقييم');
      setShowToast(true);
    }
  };

  // دالة لعرض نجوم التقييم
  const renderStars = (value: number, max: number = 5, interactive: boolean = false) => {
    return Array.from({ length: max }).map((_, index) => (
      <IonIcon
        key={index}
        icon={index < value ? star : starOutline}
        color={index < value ? 'warning' : 'medium'}
        onClick={interactive ? () => setRating(index + 1) : undefined}
        className={interactive ? 'interactive-star' : ''}
      />
    ));
  };

  return (
    <IonPage>
      <IonHeader>
        <IonToolbar color="dark">
          <IonButtons slot="start">
            <IonBackButton defaultHref="/products" />
          </IonButtons>
          <IonTitle>تفاصيل المنتج</IonTitle>
        </IonToolbar>
      </IonHeader>
      <IonContent fullscreen>
        {loading ? (
          <div className="loading-container">
            <div className="product-image-placeholder">
              <IonSkeletonText animated style={{ height: '100%' }} />
            </div>
            <div className="ion-padding">
              <IonSkeletonText animated style={{ width: '70%', height: '30px', margin: '10px 0' }} />
              <IonSkeletonText animated style={{ width: '50%', height: '20px', margin: '10px 0' }} />
              <IonSkeletonText animated style={{ width: '90%', height: '100px', margin: '20px 0' }} />
            </div>
          </div>
        ) : product ? (
          <>
            {/* صور المنتج */}
            <IonSlides options={slideOpts} className="product-slides">
              <IonSlide>
                <div className="slide-zoom">
                  <IonImg src={product.image} alt={product.name} className="product-detail-image" />
                </div>
              </IonSlide>
              {product.additional_images && product.additional_images.map((image, index) => (
                <IonSlide key={index}>
                  <div className="slide-zoom">
                    <IonImg src={image.image_url} alt={`${product.name} ${index + 1}`} className="product-detail-image" />
                  </div>
                </IonSlide>
              ))}
            </IonSlides>

            {/* معلومات المنتج */}
            <div className="ion-padding product-info">
              <div className="product-header">
                <h1>{product.name}</h1>
                <IonButton 
                  fill="clear" 
                  color={liked ? 'danger' : 'medium'} 
                  onClick={handleLike}
                >
                  <IonIcon slot="icon-only" icon={liked ? heart : heartOutline} />
                </IonButton>
              </div>
              
              <div className="product-meta">
                <div className="product-rating">
                  {renderStars(product.avg_rating || 0)}
                  <span>({product.reviews ? product.reviews.length : 0} تقييم)</span>
                </div>
                <div className="product-likes">
                  <IonIcon icon={heart} color="danger" />
                  <span>{product.likes_count} إعجاب</span>
                </div>
              </div>
              
              <div className="product-price-container">
                <h2 className="product-price">{product.price} ريال</h2>
                {product.quantity > 0 ? (
                  <IonBadge color="success">متوفر</IonBadge>
                ) : (
                  <IonBadge color="medium">غير متوفر</IonBadge>
                )}
              </div>
              
              <IonCard className="store-info-card" routerLink={`/store/${product.store_id}`}>
                <IonCardHeader>
                  <IonCardTitle>
                    <IonIcon icon={storefront} color="warning" />
                    <span>{product.store_name}</span>
                  </IonCardTitle>
                </IonCardHeader>
                <IonCardContent>
                  <IonButton fill="outline" color="warning" size="small">
                    زيارة المتجر
                  </IonButton>
                </IonCardContent>
              </IonCard>
              
              <div className="product-description">
                <h3>الوصف</h3>
                <p>{product.description}</p>
              </div>
              
              {product.quantity > 0 && (
                <div className="quantity-selector">
                  <IonButton fill="clear" onClick={decreaseQuantity}>
                    <IonIcon slot="icon-only" icon={removeOutline} />
                  </IonButton>
                  <span>{quantity}</span>
                  <IonButton fill="clear" onClick={increaseQuantity}>
                    <IonIcon slot="icon-only" icon={addOutline} />
                  </IonButton>
                </div>
              )}
              
              <IonButton 
                expand="block" 
                color="warning" 
                onClick={handleAddToCart}
                disabled={product.quantity <= 0}
              >
                <IonIcon slot="start" icon={cartOutline} />
                إضافة إلى السلة
              </IonButton>
              
              <IonButton 
                expand="block" 
                color="medium" 
                fill="outline" 
                className="compare-button"
                onClick={handleAddToCompare}
                disabled={inCompareList}
              >
                <IonIcon slot="start" icon={swapHorizontalOutline} />
                {inCompareList ? 'تمت الإضافة للمقارنة' : 'أضف للمقارنة'}
              </IonButton>
              
              {/* قسم التقييمات */}
              <div className="reviews-section">
                <div className="section-header">
                  <h3>التقييمات والمراجعات</h3>
                  <IonButton 
                    fill="clear" 
                    size="small" 
                    onClick={() => setShowReviewForm(!showReviewForm)}
                  >
                    {showReviewForm ? 'إلغاء' : 'إضافة تقييم'}
                  </IonButton>
                </div>
                
                {showReviewForm && (
                  <div className="review-form">
                    <div className="rating-stars">
                      {renderStars(rating, 5, true)}
                    </div>
                    <IonTextarea
                      placeholder="اكتب مراجعتك هنا..."
                      value={review}
                      onIonChange={e => setReview(e.detail.value!)}
                      rows={4}
                    />
                    <IonButton 
                      expand="block" 
                      onClick={submitReview}
                      disabled={submittingReview}
                    >
                      {submittingReview ? <IonSpinner name="dots" /> : 'إرسال التقييم'}
                    </IonButton>
                  </div>
                )}
                
                {product.reviews && product.reviews.length > 0 ? (
                  <div className="reviews-list">
                    {product.reviews.map((review, index) => (
                      <IonCard key={index} className="review-card">
                        <IonCardHeader>
                          <div className="review-header">
                            <div className="reviewer-info">
                              <IonAvatar>
                                <IonImg src="https://gravatar.com/avatar/dba6bae8c566f9d4041fb9cd9ada7741?d=identicon&f=y" />
                              </IonAvatar>
                              <IonLabel>{review.customer_name}</IonLabel>
                            </div>
                            <div className="review-rating">
                              {renderStars(review.rating)}
                            </div>
                          </div>
                        </IonCardHeader>
                        <IonCardContent>
                          <p>{review.comment}</p>
                          <div className="review-date">
                            {new Date(review.created_at).toLocaleDateString('ar-SA')}
                          </div>
                        </IonCardContent>
                      </IonCard>
                    ))}
                  </div>
                ) : (
                  <div className="no-reviews">
                    <IonIcon icon={chatbubbleOutline} color="medium" />
                    <p>لا توجد تقييمات بعد. كن أول من يقيم هذا المنتج!</p>
                  </div>
                )}
              </div>
            </div>
          </>
        ) : (
          <div className="error-container ion-padding">
            <IonText color="danger">
              <h2>عذراً، لم يتم العثور على المنتج</h2>
              <p>قد يكون المنتج غير متوفر أو تم حذفه.</p>
            </IonText>
            <IonButton routerLink="/products" expand="block">
              العودة إلى المنتجات
            </IonButton>
          </div>
        )}
        
        <IonToast
          isOpen={showToast}
          onDidDismiss={() => setShowToast(false)}
          message={toastMessage}
          duration={2000}
          position="bottom"
          color="dark"
        />
      </IonContent>
    </IonPage>
  );
};

export default ProductDetail;

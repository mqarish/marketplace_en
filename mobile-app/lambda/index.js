// This sample demonstrates handling intents from an Alexa skill using the Alexa Skills Kit SDK (v2).
// Please visit https://alexa.design/cookbook for additional examples on implementing slots, dialog management,
// session persistence, api calls, and more.
const Alexa = require('ask-sdk-core');
const i18n = require('i18next');
const axios = require('axios');

// تكوين الاتصال بواجهة برمجة التطبيقات الخاصة بالسوق
const API_ENDPOINT = 'https://example.com/marketplace/api';

const LaunchRequestHandler = {
    canHandle(handlerInput) {
        return Alexa.getRequestType(handlerInput.requestEnvelope) === 'LaunchRequest';
    },
    handle(handlerInput) {
        const speakOutput = 'مرحبا بك في سوق المتجر. يمكنك البحث عن المنتجات، أو التحقق من حالة طلباتك، أو معرفة أحدث العروض.';
        return handlerInput.responseBuilder
            .speak(speakOutput)
            .reprompt(speakOutput)
            .getResponse();
    }
};

const SearchProductsIntentHandler = {
    canHandle(handlerInput) {
        return Alexa.getRequestType(handlerInput.requestEnvelope) === 'IntentRequest'
            && Alexa.getIntentName(handlerInput.requestEnvelope) === 'SearchProductsIntent';
    },
    async handle(handlerInput) {
        const query = handlerInput.requestEnvelope.request.intent.slots.query.value;
        
        try {
            // استدعاء واجهة برمجة التطبيقات للبحث عن المنتجات
            const response = await axios.get(`${API_ENDPOINT}/products/search?q=${encodeURIComponent(query)}`);
            
            if (response.data && response.data.products && response.data.products.length > 0) {
                const products = response.data.products;
                const productCount = products.length;
                
                let speakOutput = `وجدت ${productCount} منتجات تطابق بحثك عن ${query}. `;
                
                // وصف أول 3 منتجات
                if (productCount > 0) {
                    speakOutput += 'إليك بعض النتائج: ';
                    for (let i = 0; i < Math.min(3, productCount); i++) {
                        const product = products[i];
                        speakOutput += `${product.name} بسعر ${product.price} ريال سعودي. `;
                    }
                    
                    speakOutput += 'هل ترغب في معرفة المزيد عن أي من هذه المنتجات؟';
                }
                
                return handlerInput.responseBuilder
                    .speak(speakOutput)
                    .reprompt('هل ترغب في معرفة المزيد عن أي من هذه المنتجات؟')
                    .getResponse();
            } else {
                return handlerInput.responseBuilder
                    .speak(`عذراً، لم أجد أي منتجات تطابق بحثك عن ${query}. هل ترغب في تجربة بحث آخر؟`)
                    .reprompt('هل ترغب في تجربة بحث آخر؟')
                    .getResponse();
            }
        } catch (error) {
            console.log(`Error calling API: ${error}`);
            return handlerInput.responseBuilder
                .speak('عذراً، حدث خطأ أثناء البحث عن المنتجات. يرجى المحاولة مرة أخرى لاحقاً.')
                .getResponse();
        }
    }
};

const CheckOrderStatusIntentHandler = {
    canHandle(handlerInput) {
        return Alexa.getRequestType(handlerInput.requestEnvelope) === 'IntentRequest'
            && Alexa.getIntentName(handlerInput.requestEnvelope) === 'CheckOrderStatusIntent';
    },
    async handle(handlerInput) {
        const orderId = handlerInput.requestEnvelope.request.intent.slots.orderId.value;
        
        try {
            // استدعاء واجهة برمجة التطبيقات للتحقق من حالة الطلب
            const response = await axios.get(`${API_ENDPOINT}/orders/${orderId}`);
            
            if (response.data && response.data.order) {
                const order = response.data.order;
                let speakOutput = `طلبك رقم ${orderId} `;
                
                switch (order.status) {
                    case 'pending':
                        speakOutput += 'قيد الانتظار وسيتم معالجته قريباً.';
                        break;
                    case 'processing':
                        speakOutput += 'قيد المعالجة حالياً.';
                        break;
                    case 'shipped':
                        speakOutput += `تم شحنه بتاريخ ${order.shipped_date} ومن المتوقع وصوله بتاريخ ${order.estimated_delivery}.`;
                        break;
                    case 'delivered':
                        speakOutput += `تم توصيله بتاريخ ${order.delivery_date}.`;
                        break;
                    case 'cancelled':
                        speakOutput += 'تم إلغاؤه.';
                        break;
                    default:
                        speakOutput += `حالته هي ${order.status}.`;
                }
                
                return handlerInput.responseBuilder
                    .speak(speakOutput)
                    .getResponse();
            } else {
                return handlerInput.responseBuilder
                    .speak(`عذراً، لم أتمكن من العثور على طلب برقم ${orderId}. يرجى التحقق من الرقم والمحاولة مرة أخرى.`)
                    .reprompt('هل ترغب في التحقق من طلب آخر؟')
                    .getResponse();
            }
        } catch (error) {
            console.log(`Error calling API: ${error}`);
            return handlerInput.responseBuilder
                .speak('عذراً، حدث خطأ أثناء التحقق من حالة طلبك. يرجى المحاولة مرة أخرى لاحقاً.')
                .getResponse();
        }
    }
};

const GetLatestOffersIntentHandler = {
    canHandle(handlerInput) {
        return Alexa.getRequestType(handlerInput.requestEnvelope) === 'IntentRequest'
            && Alexa.getIntentName(handlerInput.requestEnvelope) === 'GetLatestOffersIntent';
    },
    async handle(handlerInput) {
        try {
            // استدعاء واجهة برمجة التطبيقات للحصول على أحدث العروض
            const response = await axios.get(`${API_ENDPOINT}/offers/latest`);
            
            if (response.data && response.data.offers && response.data.offers.length > 0) {
                const offers = response.data.offers;
                const offerCount = offers.length;
                
                let speakOutput = `هناك ${offerCount} عروض متاحة حالياً. `;
                
                // وصف أول 3 عروض
                if (offerCount > 0) {
                    speakOutput += 'إليك بعض العروض المميزة: ';
                    for (let i = 0; i < Math.min(3, offerCount); i++) {
                        const offer = offers[i];
                        speakOutput += `${offer.product_name} بخصم ${offer.discount_percentage}% والسعر بعد الخصم ${offer.discounted_price} ريال سعودي. `;
                    }
                    
                    speakOutput += 'هل ترغب في معرفة المزيد عن أي من هذه العروض؟';
                }
                
                return handlerInput.responseBuilder
                    .speak(speakOutput)
                    .reprompt('هل ترغب في معرفة المزيد عن أي من هذه العروض؟')
                    .getResponse();
            } else {
                return handlerInput.responseBuilder
                    .speak('عذراً، لا توجد عروض متاحة حالياً. يرجى التحقق مرة أخرى لاحقاً.')
                    .getResponse();
            }
        } catch (error) {
            console.log(`Error calling API: ${error}`);
            return handlerInput.responseBuilder
                .speak('عذراً، حدث خطأ أثناء البحث عن العروض. يرجى المحاولة مرة أخرى لاحقاً.')
                .getResponse();
        }
    }
};

const AddToCartIntentHandler = {
    canHandle(handlerInput) {
        return Alexa.getRequestType(handlerInput.requestEnvelope) === 'IntentRequest'
            && Alexa.getIntentName(handlerInput.requestEnvelope) === 'AddToCartIntent';
    },
    async handle(handlerInput) {
        const productName = handlerInput.requestEnvelope.request.intent.slots.productName.value;
        
        try {
            // استدعاء واجهة برمجة التطبيقات للبحث عن المنتج أولاً
            const searchResponse = await axios.get(`${API_ENDPOINT}/products/search?q=${encodeURIComponent(productName)}&limit=1`);
            
            if (searchResponse.data && searchResponse.data.products && searchResponse.data.products.length > 0) {
                const product = searchResponse.data.products[0];
                
                // إضافة المنتج إلى سلة التسوق
                const cartResponse = await axios.post(`${API_ENDPOINT}/cart/add`, {
                    product_id: product.id,
                    quantity: 1
                });
                
                if (cartResponse.data && cartResponse.data.success) {
                    return handlerInput.responseBuilder
                        .speak(`تمت إضافة ${product.name} إلى سلة التسوق الخاصة بك. هل ترغب في إضافة منتج آخر؟`)
                        .reprompt('هل ترغب في إضافة منتج آخر؟')
                        .getResponse();
                } else {
                    return handlerInput.responseBuilder
                        .speak(`عذراً، لم أتمكن من إضافة ${product.name} إلى سلة التسوق. يرجى المحاولة مرة أخرى لاحقاً.`)
                        .getResponse();
                }
            } else {
                return handlerInput.responseBuilder
                    .speak(`عذراً، لم أجد منتجاً باسم ${productName}. هل يمكنك تحديد اسم المنتج بشكل أكثر دقة؟`)
                    .reprompt('هل يمكنك تحديد اسم المنتج بشكل أكثر دقة؟')
                    .getResponse();
            }
        } catch (error) {
            console.log(`Error calling API: ${error}`);
            return handlerInput.responseBuilder
                .speak('عذراً، حدث خطأ أثناء إضافة المنتج إلى سلة التسوق. يرجى المحاولة مرة أخرى لاحقاً.')
                .getResponse();
        }
    }
};

const HelpIntentHandler = {
    canHandle(handlerInput) {
        return Alexa.getRequestType(handlerInput.requestEnvelope) === 'IntentRequest'
            && Alexa.getIntentName(handlerInput.requestEnvelope) === 'AMAZON.HelpIntent';
    },
    handle(handlerInput) {
        const speakOutput = 'يمكنك البحث عن المنتجات بقول "ابحث عن هاتف ذكي"، أو التحقق من حالة طلبك بقول "ما هي حالة طلبي رقم 123"، أو معرفة أحدث العروض بقول "ما هي أحدث العروض"، أو إضافة منتج إلى سلة التسوق بقول "أضف هاتف آيفون إلى سلة التسوق". كيف يمكنني مساعدتك؟';

        return handlerInput.responseBuilder
            .speak(speakOutput)
            .reprompt(speakOutput)
            .getResponse();
    }
};

const CancelAndStopIntentHandler = {
    canHandle(handlerInput) {
        return Alexa.getRequestType(handlerInput.requestEnvelope) === 'IntentRequest'
            && (Alexa.getIntentName(handlerInput.requestEnvelope) === 'AMAZON.CancelIntent'
                || Alexa.getIntentName(handlerInput.requestEnvelope) === 'AMAZON.StopIntent');
    },
    handle(handlerInput) {
        const speakOutput = 'شكراً لاستخدامك سوق المتجر. إلى اللقاء!';
        return handlerInput.responseBuilder
            .speak(speakOutput)
            .getResponse();
    }
};

const SessionEndedRequestHandler = {
    canHandle(handlerInput) {
        return Alexa.getRequestType(handlerInput.requestEnvelope) === 'SessionEndedRequest';
    },
    handle(handlerInput) {
        // أي منطق تنظيف يجب أن يوضع هنا
        return handlerInput.responseBuilder.getResponse();
    }
};

// معالج الأخطاء العام - يتعامل مع جميع الأخطاء والاستثناءات
const ErrorHandler = {
    canHandle() {
        return true;
    },
    handle(handlerInput, error) {
        console.log(`~~~~ Error handled: ${error.stack}`);
        const speakOutput = 'عذراً، حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى.';

        return handlerInput.responseBuilder
            .speak(speakOutput)
            .reprompt(speakOutput)
            .getResponse();
    }
};

// تصدير الدالة الرئيسية
exports.handler = Alexa.SkillBuilders.custom()
    .addRequestHandlers(
        LaunchRequestHandler,
        SearchProductsIntentHandler,
        CheckOrderStatusIntentHandler,
        GetLatestOffersIntentHandler,
        AddToCartIntentHandler,
        HelpIntentHandler,
        CancelAndStopIntentHandler,
        SessionEndedRequestHandler)
    .addErrorHandlers(
        ErrorHandler)
    .lambda();

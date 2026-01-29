/**
 * Facebook Pixel Composable
 * 
 * Provides utilities for tracking Facebook Pixel events throughout the application.
 * 
 * Usage:
 * import { useFacebookPixel } from '@/Composables/useFacebookPixel';
 * const { trackEvent, trackPurchase } = useFacebookPixel();
 * 
 * trackEvent('InitiateCheckout', { value: 100.00, currency: 'USD' });
 * trackPurchase({ value: 100.00, currency: 'USD', content_ids: ['123'] });
 */

export function useFacebookPixel() {
    /**
     * Check if Facebook Pixel is loaded and enabled
     */
    const isEnabled = () => {
        return typeof window !== 'undefined' && 
               typeof window.fbq !== 'undefined' &&
               window.fbq !== null;
    };

    /**
     * Track a standard Facebook Pixel event
     * 
     * @param {string} eventName - Event name (e.g., 'PageView', 'ViewContent', 'AddToCart')
     * @param {object} params - Event parameters
     */
    const trackEvent = (eventName, params = {}) => {
        if (!isEnabled()) {
            console.warn('Facebook Pixel not loaded. Event not tracked:', eventName);
            return;
        }

        try {
            window.fbq('track', eventName, params);
            console.log('Facebook Pixel event tracked:', eventName, params);
        } catch (error) {
            console.error('Error tracking Facebook Pixel event:', error);
        }
    };

    /**
     * Track a Purchase event
     * 
     * @param {object} purchaseData - Purchase data
     * @param {number} purchaseData.value - Total purchase value
     * @param {string} purchaseData.currency - Currency code (default: 'USD')
     * @param {array} purchaseData.content_ids - Array of content/product IDs
     * @param {string} purchaseData.content_name - Content/product name
     * @param {string} purchaseData.content_type - Content type (e.g., 'product', 'shipment')
     * @param {number} purchaseData.num_items - Number of items
     * @param {object} purchaseData.customerData - Customer data for advanced matching (email, phone, etc.)
     */
    const trackPurchase = (purchaseData) => {
        if (!isEnabled()) {
            console.warn('Facebook Pixel not loaded. Purchase event not tracked.');
            return;
        }

        try {
            const {
                value,
                currency = 'USD',
                content_ids = [],
                content_name = '',
                content_type = 'shipment',
                num_items = 1,
                customerData = {}
            } = purchaseData;

            const eventData = {
                value: parseFloat(value) || 0,
                currency: currency.toUpperCase(),
                content_ids: Array.isArray(content_ids) ? content_ids : [content_ids],
                content_name,
                content_type,
                num_items: parseInt(num_items) || 1,
            };

            // Track Purchase event
            window.fbq('track', 'Purchase', eventData);

            // If customer data is provided, set it for advanced matching
            if (customerData && Object.keys(customerData).length > 0) {
                window.fbq('track', 'Purchase', eventData, {
                    eventID: `purchase_${Date.now()}`,
                    ...customerData
                });
            }

            console.log('Facebook Pixel Purchase event tracked:', eventData);
        } catch (error) {
            console.error('Error tracking Facebook Pixel Purchase event:', error);
        }
    };

    /**
     * Track InitiateCheckout event
     * 
     * @param {object} checkoutData - Checkout data
     * @param {number} checkoutData.value - Total checkout value
     * @param {string} checkoutData.currency - Currency code
     * @param {array} checkoutData.content_ids - Array of content/product IDs
     * @param {number} checkoutData.num_items - Number of items
     */
    const trackInitiateCheckout = (checkoutData) => {
        const {
            value,
            currency = 'USD',
            content_ids = [],
            num_items = 1
        } = checkoutData;

        trackEvent('InitiateCheckout', {
            value: parseFloat(value) || 0,
            currency: currency.toUpperCase(),
            content_ids: Array.isArray(content_ids) ? content_ids : [content_ids],
            num_items: parseInt(num_items) || 1,
        });
    };

    /**
     * Track ViewContent event
     * 
     * @param {object} contentData - Content data
     * @param {string} contentData.content_name - Content name
     * @param {string} contentData.content_type - Content type
     * @param {string|array} contentData.content_ids - Content IDs
     * @param {number} contentData.value - Content value
     * @param {string} contentData.currency - Currency code
     */
    const trackViewContent = (contentData) => {
        const {
            content_name = '',
            content_type = 'page',
            content_ids = [],
            value = 0,
            currency = 'USD'
        } = contentData;

        trackEvent('ViewContent', {
            content_name,
            content_type,
            content_ids: Array.isArray(content_ids) ? content_ids : [content_ids],
            value: parseFloat(value) || 0,
            currency: currency.toUpperCase(),
        });
    };

    /**
     * Track AddToCart event
     * 
     * @param {object} cartData - Cart data
     * @param {number} cartData.value - Item value
     * @param {string} cartData.currency - Currency code
     * @param {string|array} cartData.content_ids - Content IDs
     * @param {string} cartData.content_name - Content name
     */
    const trackAddToCart = (cartData) => {
        const {
            value,
            currency = 'USD',
            content_ids = [],
            content_name = ''
        } = cartData;

        trackEvent('AddToCart', {
            value: parseFloat(value) || 0,
            currency: currency.toUpperCase(),
            content_ids: Array.isArray(content_ids) ? content_ids : [content_ids],
            content_name,
        });
    };

    /**
     * Track Search event
     * 
     * @param {string} searchString - Search query
     */
    const trackSearch = (searchString) => {
        trackEvent('Search', {
            search_string: searchString
        });
    };

    /**
     * Track CompleteRegistration event
     * 
     * @param {object} registrationData - Registration data
     * @param {string} registrationData.status - Registration status
     * @param {string} registrationData.method - Registration method
     */
    const trackCompleteRegistration = (registrationData = {}) => {
        trackEvent('CompleteRegistration', {
            status: registrationData.status || 'completed',
            method: registrationData.method || 'email'
        });
    };

    /**
     * Track Lead event
     * 
     * @param {object} leadData - Lead data
     * @param {string} leadData.content_name - Lead source/content name
     * @param {string} leadData.content_category - Lead category
     */
    const trackLead = (leadData = {}) => {
        trackEvent('Lead', {
            content_name: leadData.content_name || 'Contact Form',
            content_category: leadData.content_category || 'General'
        });
    };

    return {
        isEnabled,
        trackEvent,
        trackPurchase,
        trackInitiateCheckout,
        trackViewContent,
        trackAddToCart,
        trackSearch,
        trackCompleteRegistration,
        trackLead,
    };
}

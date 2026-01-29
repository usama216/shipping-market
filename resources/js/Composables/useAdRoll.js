/**
 * AdRoll Pixel Composable
 * 
 * Provides utilities for tracking AdRoll pixel events throughout the application.
 * 
 * Usage:
 * import { useAdRoll } from '@/Composables/useAdRoll';
 * const { trackEvent, trackPurchase } = useAdRoll();
 * 
 * trackEvent('pageView');
 * trackPurchase({ order_id: '123', total: 100.00 });
 */

export function useAdRoll() {
    /**
     * Check if AdRoll pixel is loaded and enabled
     */
    const isEnabled = () => {
        return typeof window !== 'undefined' && 
               typeof window.__adroll !== 'undefined' &&
               typeof window.__adroll_loaded !== 'undefined' &&
               window.__adroll_loaded === true;
    };

    /**
     * Wait for AdRoll to load before tracking events
     */
    const waitForAdRoll = (callback, maxAttempts = 50) => {
        if (isEnabled()) {
            callback();
            return;
        }

        let attempts = 0;
        const checkInterval = setInterval(() => {
            attempts++;
            if (isEnabled()) {
                clearInterval(checkInterval);
                callback();
            } else if (attempts >= maxAttempts) {
                clearInterval(checkInterval);
                console.warn('AdRoll pixel did not load in time. Event may not be tracked.');
            }
        }, 100);
    };

    /**
     * Track a standard AdRoll event
     * 
     * @param {string} eventName - Event name (e.g., 'pageView', 'addToCart', 'beginCheckout')
     * @param {object} params - Event parameters
     */
    const trackEvent = (eventName, params = {}) => {
        waitForAdRoll(() => {
            try {
                if (typeof window.__adroll !== 'undefined' && window.__adroll.track) {
                    window.__adroll.track(eventName, params);
                    console.log('AdRoll event tracked:', eventName, params);
                } else {
                    // Fallback: Use AdRoll's standard tracking method
                    if (window.__adroll && window.__adroll.record) {
                        window.__adroll.record({
                            event: eventName,
                            ...params
                        });
                    }
                }
            } catch (error) {
                console.error('Error tracking AdRoll event:', error);
            }
        });
    };

    /**
     * Track a Purchase event
     * 
     * @param {object} purchaseData - Purchase data
     * @param {string} purchaseData.order_id - Order/Shipment ID
     * @param {number} purchaseData.total - Total purchase value
     * @param {string} purchaseData.currency - Currency code (default: 'USD')
     * @param {array} purchaseData.product_ids - Array of product/package IDs
     * @param {string} purchaseData.email - Customer email (for customer matching)
     */
    const trackPurchase = (purchaseData) => {
        if (!purchaseData || !purchaseData.order_id) {
            console.warn('AdRoll Purchase event requires order_id');
            return;
        }

        waitForAdRoll(() => {
            try {
                const {
                    order_id,
                    total = 0,
                    currency = 'USD',
                    product_ids = [],
                    email = ''
                } = purchaseData;

                const eventData = {
                    order_id: order_id.toString(),
                    total: parseFloat(total) || 0,
                    currency: currency.toUpperCase(),
                    product_ids: Array.isArray(product_ids) ? product_ids : [product_ids],
                };

                // Track purchase event
                trackEvent('purchase', eventData);

                // If email is provided, set it for customer matching
                if (email) {
                    if (window.__adroll && window.__adroll.setCustomerData) {
                        window.__adroll.setCustomerData({
                            email: email
                        });
                    }
                }

                console.log('AdRoll Purchase event tracked:', eventData);
            } catch (error) {
                console.error('Error tracking AdRoll Purchase event:', error);
            }
        });
    };

    /**
     * Track BeginCheckout event
     * 
     * @param {object} checkoutData - Checkout data
     * @param {number} checkoutData.total - Total checkout value
     * @param {string} checkoutData.currency - Currency code
     * @param {array} checkoutData.product_ids - Array of product/package IDs
     */
    const trackBeginCheckout = (checkoutData) => {
        const {
            total = 0,
            currency = 'USD',
            product_ids = []
        } = checkoutData;

        trackEvent('beginCheckout', {
            total: parseFloat(total) || 0,
            currency: currency.toUpperCase(),
            product_ids: Array.isArray(product_ids) ? product_ids : [product_ids],
        });
    };

    /**
     * Track AddToCart event
     * 
     * @param {object} cartData - Cart data
     * @param {string|number} cartData.product_id - Product/package ID
     * @param {number} cartData.value - Item value
     * @param {string} cartData.currency - Currency code
     */
    const trackAddToCart = (cartData) => {
        const {
            product_id,
            value = 0,
            currency = 'USD'
        } = cartData;

        trackEvent('addToCart', {
            product_id: product_id?.toString() || '',
            value: parseFloat(value) || 0,
            currency: currency.toUpperCase(),
        });
    };

    /**
     * Track ViewContent event
     * 
     * @param {object} contentData - Content data
     * @param {string|number} contentData.product_id - Product/package ID
     * @param {string} contentData.product_name - Product name
     * @param {number} contentData.value - Content value
     * @param {string} contentData.currency - Currency code
     */
    const trackViewContent = (contentData) => {
        const {
            product_id,
            product_name = '',
            value = 0,
            currency = 'USD'
        } = contentData;

        trackEvent('viewContent', {
            product_id: product_id?.toString() || '',
            product_name,
            value: parseFloat(value) || 0,
            currency: currency.toUpperCase(),
        });
    };

    /**
     * Track PageView event
     */
    const trackPageView = () => {
        trackEvent('pageView');
    };

    /**
     * Track Search event
     * 
     * @param {string} searchString - Search query
     */
    const trackSearch = (searchString) => {
        trackEvent('search', {
            search_string: searchString
        });
    };

    /**
     * Set customer data for better matching
     * 
     * @param {object} customerData - Customer data
     * @param {string} customerData.email - Customer email
     * @param {string} customerData.phone - Customer phone
     * @param {string} customerData.first_name - First name
     * @param {string} customerData.last_name - Last name
     */
    const setCustomerData = (customerData) => {
        waitForAdRoll(() => {
            try {
                if (window.__adroll && window.__adroll.setCustomerData) {
                    window.__adroll.setCustomerData(customerData);
                    console.log('AdRoll customer data set:', customerData);
                }
            } catch (error) {
                console.error('Error setting AdRoll customer data:', error);
            }
        });
    };

    return {
        isEnabled,
        trackEvent,
        trackPurchase,
        trackBeginCheckout,
        trackAddToCart,
        trackViewContent,
        trackPageView,
        trackSearch,
        setCustomerData,
    };
}

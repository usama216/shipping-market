export default {
    methods: {
        __format_date_time(date) {
            try {
                const parsedDate = new Date(date);
                if (isNaN(parsedDate)) return "";

                const options = { day: '2-digit', month: 'short', year: 'numeric' };
                return new Intl.DateTimeFormat('en-US', options).format(parsedDate);

            } catch (error) {
                console.log("🚀 ~ __format_date_time ~ error:", error);
            }
        },
        __to_fixed_number(num) {
            if (num) {
                return Number(num)?.toFixed(2);
            } else {
                return "0.00";
            }
        },
        __currency_format(amount, currency = 'USD', locale = 'en-US') {
            return new Intl.NumberFormat(locale, {
                style: 'currency',
                currency: currency
            }).format(amount);
        },
        __number_format(amount, decimals = 2) {
            return Number(amount).toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        },
        // User type detection helpers
        isCustomer() {
            return this.$page?.props?.auth?.userType === 'customer';
        },
        isSystemUser() {
            return this.$page?.props?.auth?.userType === 'system';
        },
        getUserType() {
            return this.$page?.props?.auth?.userType || null;
        }
    },
}

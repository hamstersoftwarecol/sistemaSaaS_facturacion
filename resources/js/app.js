import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

/**
 * Formateo de dinero en el navegador (coincide con el helper money() de PHP).
 */
window.formatMoney = (value, symbol = window.APP_CURRENCY || '$') => {
    const number = Number(value || 0);
    const formatted = Math.abs(number).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return (number < 0 ? '-' : '') + symbol + ' ' + formatted;
};

/**
 * Editor de líneas de documentos (facturas, presupuestos, compras...).
 */
Alpine.data('documentEditor', (config) => ({
    items: [],
    products: config.products || [],
    taxRates: config.taxRates || [],
    priceLists: config.priceLists || {},
    parties: config.parties || [],
    partyId: config.partyId ? String(config.partyId) : '',
    priceListId: config.priceListId ? String(config.priceListId) : '',
    defaultTaxRateId: config.defaultTaxRateId ? String(config.defaultTaxRateId) : '',
    calculateUrl: config.calculateUrl,
    serverTotals: null,
    loading: false,
    timer: null,

    init() {
        const initial = (config.items || []).length ? config.items : [{}];
        this.items = initial.map((item) => this.makeItem(item));
        this.$watch('items', () => this.scheduleCalculate(), { deep: true });
        this.scheduleCalculate();
    },

    makeItem(item = {}) {
        return {
            uid: Math.random().toString(36).slice(2),
            product_id: item.product_id ? String(item.product_id) : '',
            description: item.description || '',
            unit: item.unit || '',
            quantity: item.quantity !== undefined ? Number(item.quantity) : 1,
            unit_price: item.unit_price !== undefined ? Number(item.unit_price) : 0,
            discount_percent: item.discount_percent !== undefined ? Number(item.discount_percent) : 0,
            tax_rate_id: item.tax_rate_id ? String(item.tax_rate_id) : (item.product_id ? '' : this.defaultTaxRateId),
            search: '',
            open: false,
        };
    },

    addItem() {
        this.items.push(this.makeItem({ tax_rate_id: this.defaultTaxRateId }));
        this.$nextTick(() => {
            const inputs = this.$root.querySelectorAll('[data-product-search]');
            inputs[inputs.length - 1]?.focus();
        });
    },

    removeItem(index) {
        this.items.splice(index, 1);
        if (!this.items.length) this.addItem();
    },

    productName(id) {
        return this.products.find((p) => String(p.id) === String(id))?.name || '';
    },

    filteredProducts(item) {
        const term = (item.search || '').toLowerCase();
        return this.products
            .filter((p) => !term || p.name.toLowerCase().includes(term) || (p.sku || '').toLowerCase().includes(term) || (p.barcode || '').toLowerCase().includes(term))
            .slice(0, 30);
    },

    currentPriceList() {
        if (this.priceListId && this.priceLists[this.priceListId]) return this.priceLists[this.priceListId];
        return null;
    },

    priceFor(product) {
        const list = this.currentPriceList();
        if (list) {
            if (list.prices && list.prices[product.id] !== undefined) return Number(list.prices[product.id]);
            return Math.round(product.price * (1 + list.percentage / 100) * 100) / 100;
        }
        return Number(product.price);
    },

    selectProduct(item, product) {
        item.product_id = String(product.id);
        item.description = product.name;
        item.unit = product.unit || '';
        item.unit_price = this.priceFor(product);
        item.tax_rate_id = product.tax_rate_id ? String(product.tax_rate_id) : '';
        item.search = '';
        item.open = false;
    },

    clearProduct(item) {
        item.product_id = '';
    },

    onPartyChange() {
        const party = this.parties.find((p) => String(p.id) === String(this.partyId));
        if (party && party.price_list_id) this.priceListId = String(party.price_list_id);
        if (party && config.onPartyTerms && party.payment_terms !== undefined && this.$refs.dueDate && this.$refs.date) {
            const date = new Date(this.$refs.date.value || new Date());
            date.setDate(date.getDate() + Number(party.payment_terms || 30));
            this.$refs.dueDate.value = date.toISOString().slice(0, 10);
        }
    },

    rate(item) {
        const tax = this.taxRates.find((t) => String(t.id) === String(item.tax_rate_id));
        return tax ? Number(tax.rate) : 0;
    },

    lineSubtotal(item) {
        const gross = Number(item.quantity || 0) * Number(item.unit_price || 0);
        return Math.round((gross - gross * Number(item.discount_percent || 0) / 100) * 100) / 100;
    },

    lineTotal(item) {
        const subtotal = this.lineSubtotal(item);
        return Math.round((subtotal + subtotal * this.rate(item) / 100) * 100) / 100;
    },

    get localSubtotal() {
        return this.items.reduce((sum, i) => sum + this.lineSubtotal(i), 0);
    },

    get localTax() {
        return this.items.reduce((sum, i) => sum + (this.lineTotal(i) - this.lineSubtotal(i)), 0);
    },

    get totals() {
        if (this.serverTotals) return this.serverTotals;
        return {
            subtotal: this.localSubtotal,
            discount_total: 0,
            tax_total: this.localTax,
            adjustments: [],
            adjustments_total: 0,
            total: this.localSubtotal + this.localTax,
        };
    },

    scheduleCalculate() {
        this.serverTotals = null;
        clearTimeout(this.timer);
        if (!this.calculateUrl) return;
        this.timer = setTimeout(() => this.calculate(), 350);
    },

    async calculate() {
        this.loading = true;
        try {
            const response = await fetch(this.calculateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({
                    items: this.items.map((i) => ({
                        product_id: i.product_id || null,
                        description: i.description,
                        quantity: i.quantity,
                        unit_price: i.unit_price,
                        discount_percent: i.discount_percent,
                        tax_rate_id: i.tax_rate_id || null,
                    })),
                }),
            });
            if (response.ok) this.serverTotals = await response.json();
        } catch (e) {
            // Si falla el cálculo remoto se mantienen los totales locales.
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.start();

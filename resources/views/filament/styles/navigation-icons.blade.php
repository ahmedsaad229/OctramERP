<style>
    /*
     * Semantic sidebar icon colors.
     *
     * Kept in a panel render hook because this application uses Tailwind 3,
     * while Filament 4's custom-theme source currently requires Tailwind 4.
     * All selectors are scoped to the Filament sidebar.
     */
    .fi-sidebar-nav {
        --octram-nav-dashboard: #4f46e5;
        --octram-nav-customers: #0284c7;
        --octram-nav-suppliers: #ea580c;
        --octram-nav-sales: #059669;
        --octram-nav-purchases: #d97706;
        --octram-nav-inventory: #7c3aed;
        --octram-nav-treasury: #0891b2;
        --octram-nav-reports: #2563eb;
        --octram-nav-settings: #6b7280;
        --octram-nav-category: #7c3aed;
        --octram-nav-item: #2563eb;
        --octram-nav-unit: #0d9488;
        --octram-nav-warehouse: #d97706;
        --octram-nav-opening: #4f46e5;
        --octram-nav-receipt: #059669;
        --octram-nav-issue: #e11d48;
        --octram-nav-balance: #0891b2;
        --octram-nav-movement: #9333ea;
    }

    .dark .fi-sidebar-nav {
        --octram-nav-dashboard: #818cf8;
        --octram-nav-customers: #38bdf8;
        --octram-nav-suppliers: #fb923c;
        --octram-nav-sales: #34d399;
        --octram-nav-purchases: #fbbf24;
        --octram-nav-inventory: #a78bfa;
        --octram-nav-treasury: #22d3ee;
        --octram-nav-reports: #60a5fa;
        --octram-nav-settings: #9ca3af;
        --octram-nav-category: #c4b5fd;
        --octram-nav-item: #60a5fa;
        --octram-nav-unit: #2dd4bf;
        --octram-nav-warehouse: #fbbf24;
        --octram-nav-opening: #818cf8;
        --octram-nav-receipt: #34d399;
        --octram-nav-issue: #fb7185;
        --octram-nav-balance: #22d3ee;
        --octram-nav-movement: #c084fc;
    }

    .fi-sidebar-nav .fi-sidebar-item-icon,
    .fi-sidebar-nav .fi-sidebar-group-btn,
    .fi-sidebar-nav .fi-sidebar-item-btn {
        transition: color 150ms ease, background-color 150ms ease;
    }

    .fi-sidebar-nav .fi-sidebar-group[data-group-label="المخازن"] > .fi-sidebar-group-btn,
    .fi-sidebar-nav .fi-sidebar-group[data-group-label="المخازن"] > .fi-sidebar-group-btn svg {
        color: var(--octram-nav-inventory);
    }

    .fi-sidebar-nav .nav-icon-inventory-management {
        --octram-nav-color: var(--octram-nav-item);
    }

    .fi-sidebar-nav .nav-icon-inventory-operations {
        --octram-nav-color: var(--octram-nav-receipt);
    }

    .fi-sidebar-nav .nav-icon-inventory-reports {
        --octram-nav-color: var(--octram-nav-reports);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href$="/admin"]) {
        --octram-nav-color: var(--octram-nav-dashboard);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/customers"]) {
        --octram-nav-color: var(--octram-nav-customers);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/suppliers"]) {
        --octram-nav-color: var(--octram-nav-suppliers);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/sales-"]),
    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/receipt-vouchers"]),
    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/customer-purchase-order"]),
    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/customer-statement"]) {
        --octram-nav-color: var(--octram-nav-sales);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/purchase-"]),
    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/supplier-payment"]),
    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/due-obligations"]),
    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/supplier-statement"]) {
        --octram-nav-color: var(--octram-nav-purchases);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/treasur"]),
    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/cash-"]) {
        --octram-nav-color: var(--octram-nav-treasury);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/report"]) {
        --octram-nav-color: var(--octram-nav-reports);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/company-settings"]) {
        --octram-nav-color: var(--octram-nav-settings);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/categories"]) {
        --octram-nav-color: var(--octram-nav-category);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/items"]) {
        --octram-nav-color: var(--octram-nav-item);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/units"]) {
        --octram-nav-color: var(--octram-nav-unit);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/warehouses"]) {
        --octram-nav-color: var(--octram-nav-warehouse);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/opening-stock"]) {
        --octram-nav-color: var(--octram-nav-opening);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/goods-receipt"]) {
        --octram-nav-color: var(--octram-nav-receipt);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/goods-issue"]) {
        --octram-nav-color: var(--octram-nav-issue);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/stock-balances"]) {
        --octram-nav-color: var(--octram-nav-balance);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href*="/admin/item-movement"]) {
        --octram-nav-color: var(--octram-nav-movement);
    }

    .fi-sidebar-nav .fi-sidebar-item:has(> .fi-sidebar-item-btn[href]) .fi-sidebar-item-icon,
    .fi-sidebar-nav .nav-icon-inventory-management .fi-sidebar-item-icon,
    .fi-sidebar-nav .nav-icon-inventory-operations .fi-sidebar-item-icon,
    .fi-sidebar-nav .nav-icon-inventory-reports .fi-sidebar-item-icon {
        color: var(--octram-nav-color, currentColor) !important;
    }

    .fi-sidebar-nav .fi-sidebar-item.fi-active > .fi-sidebar-item-btn,
    .fi-sidebar-nav .fi-sidebar-item.fi-sidebar-item-has-active-child-items > .fi-sidebar-item-btn {
        background-color: color-mix(in srgb, var(--octram-nav-color, currentColor) 10%, transparent);
    }

    .fi-sidebar-nav .fi-sidebar-item > .fi-sidebar-item-btn:hover {
        background-color: color-mix(in srgb, var(--octram-nav-color, currentColor) 7%, transparent);
    }
</style>

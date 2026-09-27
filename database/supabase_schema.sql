-- ==============================================================================
-- SUPABASE POSTGRESQL SCHEMA FOR NEXUSBIZ POS & INVENTORY
-- Copy and paste this script directly into your Supabase Dashboard SQL Editor
-- ==============================================================================

-- 1. Business Settings & Dynamic Configuration
CREATE TABLE IF NOT EXISTS business_settings (
    id VARCHAR(50) PRIMARY KEY DEFAULT 'default',
    business_name VARCHAR(150) NOT NULL DEFAULT 'Nexus Retail Store',
    tagline VARCHAR(255) DEFAULT 'Quality Goods & Everyday Essentials',
    business_type VARCHAR(50) NOT NULL DEFAULT 'retail', -- retail, apparel, grocery, cafe, services
    tax_id VARCHAR(50) DEFAULT 'TAX-889922-PH',
    address TEXT DEFAULT '123 Commerce Avenue, Business Hub, Suite 400',
    phone VARCHAR(50) DEFAULT '+1 (555) 019-2834',
    email VARCHAR(100) DEFAULT 'support@nexusbiz.local',
    currency_symbol VARCHAR(10) DEFAULT '$',
    tax_name VARCHAR(30) DEFAULT 'Sales Tax',
    tax_rate NUMERIC(5, 2) DEFAULT 8.50,
    tax_inclusive BOOLEAN DEFAULT false,
    receipt_header TEXT DEFAULT 'Thank you for shopping with us!',
    receipt_footer TEXT DEFAULT 'Items in original condition can be exchanged within 7 days with valid receipt.',
    dynamic_fields JSONB DEFAULT '["Brand", "SKU/Model", "Supplier", "Warranty"]'::jsonb,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 2. Categories
CREATE TABLE IF NOT EXISTS categories (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(20) DEFAULT '#0d6efd',
    icon VARCHAR(50) DEFAULT 'bi-tag',
    description TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 3. Products with Dynamic Attributes
CREATE TABLE IF NOT EXISTS products (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    barcode VARCHAR(100) UNIQUE,
    sku VARCHAR(100),
    category_id VARCHAR(50) REFERENCES categories(id) ON DELETE SET NULL,
    cost_price NUMERIC(12, 2) DEFAULT 0.00,
    selling_price NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    stock_quantity INTEGER NOT NULL DEFAULT 0,
    min_stock_alert INTEGER DEFAULT 5,
    unit VARCHAR(30) DEFAULT 'pcs',
    dynamic_attributes JSONB DEFAULT '{}'::jsonb,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 4. Shifts / Cash Drawer Management
CREATE TABLE IF NOT EXISTS shifts (
    id VARCHAR(50) PRIMARY KEY,
    cashier_name VARCHAR(100) NOT NULL DEFAULT 'Main Cashier',
    starting_cash NUMERIC(12, 2) NOT NULL DEFAULT 100.00,
    ending_cash NUMERIC(12, 2) DEFAULT 0.00,
    expected_cash NUMERIC(12, 2) DEFAULT 0.00,
    cash_sales NUMERIC(12, 2) DEFAULT 0.00,
    card_sales NUMERIC(12, 2) DEFAULT 0.00,
    qr_sales NUMERIC(12, 2) DEFAULT 0.00,
    other_sales NUMERIC(12, 2) DEFAULT 0.00,
    cash_in NUMERIC(12, 2) DEFAULT 0.00,
    cash_out NUMERIC(12, 2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'open', -- open, closed
    notes TEXT,
    opened_at TIMESTAMPTZ DEFAULT NOW(),
    closed_at TIMESTAMPTZ
);

-- 5. Cash Drawer In/Out Movements
CREATE TABLE IF NOT EXISTS cash_movements (
    id VARCHAR(50) PRIMARY KEY,
    shift_id VARCHAR(50) REFERENCES shifts(id) ON DELETE CASCADE,
    type VARCHAR(20) NOT NULL, -- 'in' or 'out'
    amount NUMERIC(12, 2) NOT NULL,
    reason TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 6. Sales / Invoices
CREATE TABLE IF NOT EXISTS sales (
    id VARCHAR(50) PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    shift_id VARCHAR(50) REFERENCES shifts(id) ON DELETE SET NULL,
    cashier_name VARCHAR(100) DEFAULT 'Main Cashier',
    customer_name VARCHAR(100) DEFAULT 'Walk-in Customer',
    subtotal NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    discount_amount NUMERIC(12, 2) DEFAULT 0.00,
    discount_type VARCHAR(20) DEFAULT 'flat', -- flat, percent
    tax_amount NUMERIC(12, 2) DEFAULT 0.00,
    total_amount NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'cash', -- cash, card, qr, split, credit
    payment_details JSONB DEFAULT '{}'::jsonb,
    amount_tendered NUMERIC(12, 2) DEFAULT 0.00,
    change_amount NUMERIC(12, 2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'completed', -- completed, refunded, void
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 7. Sale Items (Line Items)
CREATE TABLE IF NOT EXISTS sale_items (
    id VARCHAR(50) PRIMARY KEY,
    sale_id VARCHAR(50) REFERENCES sales(id) ON DELETE CASCADE,
    product_id VARCHAR(50) REFERENCES products(id) ON DELETE SET NULL,
    product_name VARCHAR(200) NOT NULL,
    unit_price NUMERIC(12, 2) NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    subtotal NUMERIC(12, 2) NOT NULL,
    tax_amount NUMERIC(12, 2) DEFAULT 0.00,
    total NUMERIC(12, 2) NOT NULL,
    dynamic_details JSONB DEFAULT '{}'::jsonb
);

-- 8. Stock Adjustments Audit Trail
CREATE TABLE IF NOT EXISTS stock_adjustments (
    id VARCHAR(50) PRIMARY KEY,
    product_id VARCHAR(50) REFERENCES products(id) ON DELETE CASCADE,
    type VARCHAR(30) NOT NULL, -- restock, damage, expired, audit_correction, return
    quantity_change INTEGER NOT NULL,
    previous_stock INTEGER NOT NULL,
    new_stock INTEGER NOT NULL,
    reason TEXT,
    adjusted_by VARCHAR(100) DEFAULT 'Store Manager',
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 9. Parked / Held Sales (Temporary carts)
CREATE TABLE IF NOT EXISTS parked_sales (
    id VARCHAR(50) PRIMARY KEY,
    customer_note VARCHAR(150),
    items JSONB NOT NULL,
    subtotal NUMERIC(12, 2) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Enable RLS and create permissive policies for anon access (configurable for auth later)
ALTER TABLE business_settings ENABLE ROW LEVEL SECURITY;
ALTER TABLE categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE products ENABLE ROW LEVEL SECURITY;
ALTER TABLE shifts ENABLE ROW LEVEL SECURITY;
ALTER TABLE cash_movements ENABLE ROW LEVEL SECURITY;
ALTER TABLE sales ENABLE ROW LEVEL SECURITY;
ALTER TABLE sale_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE stock_adjustments ENABLE ROW LEVEL SECURITY;
ALTER TABLE parked_sales ENABLE ROW LEVEL SECURITY;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE policyname = 'Allow public access business_settings') THEN
        CREATE POLICY "Allow public access business_settings" ON business_settings FOR ALL USING (true);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE policyname = 'Allow public access categories') THEN
        CREATE POLICY "Allow public access categories" ON categories FOR ALL USING (true);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE policyname = 'Allow public access products') THEN
        CREATE POLICY "Allow public access products" ON products FOR ALL USING (true);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE policyname = 'Allow public access shifts') THEN
        CREATE POLICY "Allow public access shifts" ON shifts FOR ALL USING (true);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE policyname = 'Allow public access cash_movements') THEN
        CREATE POLICY "Allow public access cash_movements" ON cash_movements FOR ALL USING (true);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE policyname = 'Allow public access sales') THEN
        CREATE POLICY "Allow public access sales" ON sales FOR ALL USING (true);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE policyname = 'Allow public access sale_items') THEN
        CREATE POLICY "Allow public access sale_items" ON sale_items FOR ALL USING (true);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE policyname = 'Allow public access stock_adjustments') THEN
        CREATE POLICY "Allow public access stock_adjustments" ON stock_adjustments FOR ALL USING (true);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE policyname = 'Allow public access parked_sales') THEN
        CREATE POLICY "Allow public access parked_sales" ON parked_sales FOR ALL USING (true);
    END IF;
END $$;

-- Seed Initial Default Settings if empty
INSERT INTO business_settings (id, business_name, tagline, business_type, tax_id, address, phone, currency_symbol, tax_rate, tax_inclusive)
VALUES ('default', 'Nexus Retail Store', 'Quality Essentials & General Goods', 'retail', 'TAX-889922-PH', '123 Commerce Avenue, City Center', '+1 555-0199', '$', 8.50, false)
ON CONFLICT (id) DO NOTHING;

-- Seed Initial Sample Categories
INSERT INTO categories (id, name, color, icon, description) VALUES
('cat_beverages', 'Beverages & Drinks', '#0d6efd', 'bi-cup-straw', 'Cold drinks, juices, soda, water'),
('cat_snacks', 'Snacks & Bakery', '#198754', 'bi-basket', 'Chips, cookies, bakery items'),
('cat_personal', 'Personal Care', '#6f42c1', 'bi-heart', 'Toiletries, skincare, hygiene'),
('cat_general', 'General Merchandise', '#fd7e14', 'bi-box-seam', 'Household items, stationeries, batteries')
ON CONFLICT (id) DO NOTHING;

-- Seed Initial Products
INSERT INTO products (id, name, barcode, sku, category_id, cost_price, selling_price, stock_quantity, min_stock_alert, dynamic_attributes) VALUES
('prod_1', 'Premium Arabica Coffee Beans (250g)', '890123450001', 'COF-ARA-250', 'cat_beverages', 4.50, 8.99, 45, 10, '{"Brand": "Mountain Roast", "Origin": "Highlands", "Expiry": "2027-06-30"}'::jsonb),
('prod_2', 'Organic Green Tea Box (20 bags)', '890123450002', 'TEA-GRN-020', 'cat_beverages', 2.00, 4.50, 32, 8, '{"Brand": "Zen Leaf", "Flavor": "Jasmine", "Expiry": "2027-12-31"}'::jsonb),
('prod_3', 'Artisan Dark Chocolate Bar 70%', '890123450003', 'CHO-DRK-070', 'cat_snacks', 1.80, 3.75, 55, 15, '{"Brand": "ChocoCraft", "Dietary": "Vegan", "Cocoa": "70%"}'::jsonb),
('prod_4', 'Sea Salt Almonds (150g)', '890123450004', 'NUT-ALM-150', 'cat_snacks', 2.20, 5.25, 18, 5, '{"Brand": "CrunchNut", "PackSize": "150g"}'::jsonb),
('prod_5', 'Hydrating Aloe Vera Hand Soap', '890123450005', 'HYG-SOAP-250', 'cat_personal', 1.50, 3.99, 28, 6, '{"Brand": "PureGlow", "Volume": "250ml"}'::jsonb),
('prod_6', 'Heavy Duty AA Batteries (4 Pack)', '890123450006', 'BAT-AA-004', 'cat_general', 1.90, 4.99, 60, 10, '{"Brand": "VoltMax", "Type": "Alkaline", "Warranty": "1 Year"}'::jsonb),
('prod_7', 'Microfiber Cleaning Cloth 3-Pack', '890123450007', 'CLO-MIC-003', 'cat_general', 1.20, 3.49, 4, 10, '{"Brand": "CleanPro", "Color": "Assorted"}'::jsonb)
ON CONFLICT (id) DO NOTHING;

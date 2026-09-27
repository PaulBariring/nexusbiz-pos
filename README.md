# NexusBiz POS & Dynamic Business Manager

> A modern, high-speed Point of Sale (POS) and Dynamic Inventory Management web application designed for small businesses. Built with **HTML5, Bootstrap 5.3, PHP 8.4, and Supabase (PostgreSQL)**, with 1-click cloud deployment ready for **Render** and **Vercel**.

---

## 🌟 Key Features

### 1. High-Speed Point of Sale (POS)
- **Fast Touch & Barcode Checkout**: Instant barcode scanning listener or live name/SKU search (Press `F2` shortcut anytime).
- **Interactive Cart**: Quantity buttons, line-item pricing, real-time stock alert indicators.
- **Discounts & Tax Engine**: Flat or percentage order discounts. Configurable tax rate (inclusive or exclusive).
- **Hold & Park Sales**: Park an order with a customer note when someone steps away, and resume it with 1 click.
- **Multiple Payment Methods**: Cash (with Quick Cash buttons `$10, $20, $50, $100` and live Change Calculator), Card/POS, QR/Digital Wallet, and Store Tab credit.
- **Thermal Receipt Printing**: 58mm & 80mm receipt preview with one-click print (`Ctrl+P` / `window.print()` with clean print styling).

### 2. Dynamic Inventory Management
- **Catalog Management**: Products, categories, barcodes, SKU, cost price, selling price, and real-time margin percentage.
- **Dynamic Business Attributes**: Add custom fields without database migrations (e.g. *Size, Color, Brand, Warranty, Expiry Date, Batch/Lot*).
- **Stock Audit & Adjustments**: Record stock movements (+ Restock, - Damaged, - Expired, +/- Count Correction, + Customer Return) with a full audit log.
- **Low Stock Warnings**: Visual threshold warnings and filter to keep your shelves stocked.
- **Category Manager**: Organize inventory with custom colors and Bootstrap icons.

### 3. Shifts & Cash Register Management
- **Register Opening**: Record starting cash drawer float.
- **Petty Cash In / Out**: Log expenses (e.g., buying ice, paying couriers) or cash drops during a shift.
- **Shift Close & Reconciliation (Z-Report)**: Automatically calculates expected cash in drawer, prompts for actual physical cash count, and flags any over/short variance.

### 4. Dynamic Business Presets
Switch business profiles with one click:
- 🛒 **Retail & General Merchandise**: Barcode, SKU, Brand, Supplier, Warranty.
- 👗 **Fashion & Apparel**: Size (XS, S, M, L, XL), Color, Material, Season.
- 🥖 **Grocery & Convenience**: Unit of measure (kg, pcs, box), Batch #, Expiration Date.
- ☕ **Food & Beverage / Cafe**: Serving Size, Sugar/Sweetness, Temperature, Kitchen Station.
- 🔧 **Electronics & Repair**: Serial/IMEI #, Model, Warranty Coverage, Technician.

### 5. Sales Reports & Financial Analytics
- Revenue, gross profit, and order count metrics.
- Time range filters: Today, Past 7 Days, This Month, All Time.
- Payment method breakdown (Cash vs Card vs QR vs Tab).
- Top 10 best-selling products.

---

## 🚀 Quick Start (Running Locally)

Since PHP 8.4 is already installed on your system, you can start the application immediately:

```powershell
# Open terminal in project directory
cd "d:\AntiGravity Projects\business-manager"

# Start the PHP development server
php -S localhost:8000 -t public
```

Now open your browser and navigate to:
👉 **[http://localhost:8000](http://localhost:8000)**

*Note: The app starts out-of-the-box using the built-in **Local Storage Engine** pre-seeded with sample retail products, so you can test all features right away without configuring any external services!*

---

## 🗄️ Setting Up Supabase (Cloud PostgreSQL)

Supabase gives your business a cloud-hosted PostgreSQL database with real-time backups and a web dashboard:

1. Go to [supabase.com](https://supabase.com) and create a free project.
2. In your Supabase dashboard, click **SQL Editor** on the left menu.
3. Open [`database/supabase_schema.sql`](file:///d:/AntiGravity%20Projects/business-manager/database/supabase_schema.sql) from this project.
4. Copy and paste the entire SQL script into the SQL editor and click **Run**.
5. Go to **Project Settings &rarr; API**, and copy:
   - **Project URL** (e.g., `https://yourproject.supabase.co`)
   - **anon public key** (or `service_role` key)
6. Open the app in your browser, go to **Settings &rarr; Supabase Cloud Database**, paste your credentials, and click **Save Credentials**.
7. Click **Sync Local Data to Supabase** to upload all your initial products and settings to the cloud!

---

## ☁️ Deployment Guides

### Option 1: Deploying to Render (Recommended for PHP & Background Services)
Render can host the application using Docker with 1 click:
1. Push this project to your GitHub or GitLab repository:
   ```bash
   git init
   git add .
   git commit -m "Initial commit of NexusBiz POS"
   git branch -M main
   git remote add origin https://github.com/yourusername/nexusbiz-pos.git
   git push -u origin main
   ```
2. Log into [render.com](https://render.com) and click **New &rarr; Web Service**.
3. Select your repository. Render will automatically detect the [`Dockerfile`](file:///d:/AntiGravity%20Projects/business-manager/Dockerfile) or [`render.yaml`](file:///d:/AntiGravity%20Projects/business-manager/render.yaml).
4. Add your environment variables:
   - `SUPABASE_URL`: Your Supabase URL
   - `SUPABASE_KEY`: Your Supabase API key
5. Click **Create Web Service**. Your POS will be live with an SSL HTTPS URL!

### Option 2: Deploying to Vercel
1. Install Vercel CLI or link via GitHub:
   ```bash
   npm i -g vercel
   vercel
   ```
2. Vercel uses the included [`vercel.json`](file:///d:/AntiGravity%20Projects/business-manager/vercel.json) to serve the PHP application serverlessly.
3. Add `SUPABASE_URL` and `SUPABASE_KEY` in your Vercel Project Settings &rarr; Environment Variables.

---

## 📁 Project Structure

```
business-manager/
├── config/
│   └── config.php               # Environment & app config
├── src/
│   ├── Controllers/             # MVC Controllers (Pos, Inventory, Shifts, Reports, Settings, Api)
│   ├── Helpers/                 # Formatting, Currency, Invoice generation
│   └── Services/                # Unified DatabaseService, SupabaseService, LocalStorageService
├── views/
│   ├── layouts/                 # Header & Footer with Bootstrap 5.3 & Icons
│   ├── pos/                     # POS Terminal, Cart & Printable Thermal Receipt
│   ├── inventory/               # Product catalog, stock adjustments & categories
│   ├── shifts/                  # Cash register floats & Z-Reports
│   ├── reports/                 # Revenue, profit & top selling charts
│   └── settings/                # Store profile, dynamic presets & Supabase setup
├── public/
│   ├── css/app.css              # Custom styling & @media print thermal receipt CSS
│   ├── js/app.js                # Global toast notifications & hotkeys
│   ├── js/pos.js                # Core POS cart engine & barcode scanner
│   └── index.php                # Front controller & PSR-4 router
├── database/
│   ├── supabase_schema.sql      # Supabase PostgreSQL database schema & RLS policies
│   └── seed_data.json           # Initial retail seed data
├── Dockerfile                   # Cloud container for Render
├── render.yaml                  # Render Blueprint definition
├── vercel.json                  # Vercel deployment definition
└── README.md
```

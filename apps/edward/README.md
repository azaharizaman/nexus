# Edward - Terminal-based ERP Interface

**Edward** is a pure command-line demonstration application for **Nexus ERP**, showcasing the power of headless ERP systems through a terminal interface—a homage to the classic JD Edwards ERP systems that ran entirely in green-screen terminals.

---

## 🎯 What is Edward?

Edward proves that modern ERP systems don't need flashy web interfaces to be powerful. By consuming the `nexus/erp` package, Edward demonstrates:

✅ **Pure Terminal Interface** - No web routes, no controllers, no views  
✅ **Headless Architecture** - All logic from Nexus ERP package  
✅ **Interactive Menus** - Using Laravel Prompts for UX  
✅ **Full ERP Capabilities** - Tenant management, users, inventory, settings  
✅ **CLI-First Approach** - Perfect for automation and scripting

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.3+
- Composer
- PostgreSQL or MySQL
- Redis (optional)

### Installation

```bash
# Clone the repository
cd /path/to/nexus-erp/apps/edward

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env with your database credentials
# Then run migrations
php artisan migrate

# Launch Edward!
php artisan edward:menu
```

---

## 🖥️ Using Edward

### Main Menu

```bash
php artisan edward:menu
```

This launches the interactive terminal interface:

```
╔═══════════════════════════════════════════════════════════════════════╗
║                                                                       ║
║   ███████╗██████╗ ██╗    ██╗ █████╗ ██████╗ ██████╗                 ║
║   ██╔════╝██╔══██╗██║    ██║██╔══██╗██╔══██╗██╔══██╗                ║
║   █████╗  ██║  ██║██║ █╗ ██║███████║██████╔╝██║  ██║                ║
║   ██╔══╝  ██║  ██║██║███╗██║██╔══██║██╔══██╗██║  ██║                ║
║   ███████╗██████╔╝╚███╔███╔╝██║  ██║██║  ██║██████╔╝                ║
║   ╚══════╝╚═════╝  ╚══╝╚══╝ ╚═╝  ╚═╝╚═╝  ╚═╝╚═════╝                 ║
║                                                                       ║
║          Terminal-based ERP powered by Nexus ERP                     ║
║          A homage to classic JD Edwards systems                      ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝

═══ EDWARD MAIN MENU ═══

  ❯ 🏢 Tenant Management
    👤 User Management
    📦 Inventory Management
    ⚙️  Settings & Configuration
    📊 Reports & Analytics
    🔍 Search & Query
    📝 Audit Logs
    🚪 Exit Edward
```

### Available Commands

```bash
# Launch main menu
php artisan edward:menu

# Direct module access (coming soon)
php artisan edward:tenant:list
php artisan edward:user:list
php artisan edward:inventory:list
php artisan edward:settings:list
php artisan edward:audit:list
```

---

## 🏗️ Architecture

Edward is a **minimal Laravel application** that consumes the `nexus/erp` package:

```
apps/edward/
├── app/
│   └── Console/
│       └── Commands/
│           └── EdwardMenuCommand.php  # Main terminal interface
├── config/                             # Minimal Laravel config
├── database/
│   └── migrations/                     # Database schema
├── composer.json                       # Requires nexus/erp
└── artisan                             # CLI entry point
```

### What's NOT in Edward
- ❌ No web routes (`routes/web.php`, `routes/api.php`)
- ❌ No HTTP controllers
- ❌ No Blade views
- ❌ No public assets
- ❌ No frontend JavaScript

### What Edward Demonstrates
✅ Consuming `nexus/erp` package  
✅ Pure terminal interface for ERP operations  
✅ Laravel Prompts for interactive UX  
✅ Real-world CLI application architecture  
✅ Headless ERP integration patterns

---

## 🎓 Why "Edward"?

**Edward** is a tribute to **JD Edwards ERP** (now Oracle JD Edwards EnterpriseOne), one of the pioneering ERP systems that:

- Ran entirely in **terminal/green-screen interfaces**
- Proved ERP didn't need GUIs to be powerful
- Dominated the market in the 1980s-1990s
- Set standards for modular ERP architecture

By naming our CLI demo "Edward," we honor that legacy while proving that modern headless ERP systems can deliver the same power with contemporary technology.

---

## 🔮 Future Enhancements

Edward is currently a **demonstration framework**. Future enhancements will include:

- [ ] **Full tenant management** - Create, list, suspend, activate tenants
- [ ] **User management** - RBAC, permissions, account lifecycle
- [ ] **Inventory operations** - Item master, stock movements, warehouses
- [ ] **Settings management** - System and tenant-specific configuration
- [ ] **Reports & exports** - Activity logs, analytics, CSV/JSON exports
- [ ] **Search interface** - Global search powered by Laravel Scout
- [ ] **Batch operations** - Import/export via CSV
- [ ] **Automation scripts** - Seeders and demo data generators

---

## 📦 Package Dependency

Edward requires only **one dependency**:

```json
{
  "require": {
    "nexus/erp": "dev-main"
  }
}
```

All business logic, models, actions, and services come from the `nexus/erp` package. Edward is purely a **presentation layer** demonstrating terminal-based interaction.

---

## 🤝 Contributing

Edward is a demonstration app. To contribute:

1. **For ERP features** - Contribute to the `nexus/erp` package at `/src/`
2. **For CLI interface** - Enhance Edward's terminal commands in `/apps/edward/app/Console/Commands/`
3. **For new modules** - Add submenu commands following the `EdwardMenuCommand` pattern

---

## 📄 License

Edward is part of the Nexus ERP project and shares the same license (MIT).

---

## 🌟 Key Takeaway

**Edward proves that headless ERP systems can power ANY interface** - from web SPAs to mobile apps to pure terminal interfaces. The future of ERP is API-first, and Edward showcases exactly that vision.

```bash
# The power of Nexus ERP, right in your terminal
php artisan edward:menu
```

# Laravel Exchange & Ecommerce Platform

یک پلتفرم کامل خرید و فروش رمز ارز ساخته شده با Laravel که شامل تمامی قابلیت‌های مدرن یک صرافی آنلاین است.

## 🚀 ویژگی‌ها

### 📱 قابلیت‌های کاربر
- **احراز هویت**: ثبت نام، ورود، خروج با Laravel Sanctum
- **مدیریت کیف پول**: واریز، برداشت، انتقال، مشاهده پرتفولیو
- **معاملات**: خرید، فروش، تبدیل ارزها
- **تاریخچه تراکنش‌ها**: فیلتر بر اساس نوع، وضعیت، تاریخ
- **پروفایل کاربری**: مدیریت اطلاعات شخصی

### 🛡️ پنل مدیریت
- **داشبورد**: آمار کلی، گزارشات درآمد
- **مدیریت کاربران**: مشاهده، جستجو، تغییر وضعیت
- **مدیریت ارزها**: CRUD کامل، مدیریت خزانه
- **مدیریت سفارشات**: تأیید، رد، تغییر وضعیت
- **سیستم تخفیف**: ایجاد و مدیریت کدهای تخفیف
- **نرخ تبدیل**: مدیریت نرخ‌های ارز

## 🛠️ تکنولوژی‌ها

- **Backend**: Laravel 11.x
- **Database**: SQLite (قابل تغییر به MySQL/PostgreSQL)
- **Authentication**: Laravel Sanctum (API Token)
- **Testing**: PHPUnit (98 تست - 100% موفق)
- **API**: RESTful API

## 📋 پیش‌نیازها

- PHP >= 8.2
- Composer
- Laravel 11.x
- SQLite/MySQL/PostgreSQL

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## ⚡ نصب و راه‌اندازی

### 1. کلون کردن پروژه
```bash
git clone https://github.com/amirreza1307/exchange-ecommerce-laravel.git
cd exchange-ecommerce-laravel
```

### 2. نصب وابستگی‌ها
```bash
composer install
```

### 3. تنظیم محیط
```bash
cp .env.example .env
php artisan key:generate
```

### 4. تنظیم دیتابیس
```bash
# اجرای migration ها
php artisan migrate

# اجرای seeders (داده‌های تست)
php artisan db:seed
```

### 5. اجرای سرور
```bash
php artisan serve
```

## 🧪 اجرای تست‌ها

```bash
# اجرای همه تست‌ها
php artisan test

# نتیجه: OK (98 tests, 489 assertions)
```

## 👤 کاربران پیش‌فرض

### ادمین
- **ایمیل**: admin@exchange.com
- **رمز عبور**: password

### کاربر تست
- **ایمیل**: user@exchange.com
- **رمز عبور**: password

## 📚 مستندات

- [مستندات کامل API](API_DOCUMENTATION.md)
- [گزارش تست‌ها](TEST_FINAL_REPORT.md)
- [خلاصه پروژه](PROJECT_COMPLETION_SUMMARY.md)

## 🔐 احراز هویت

پروژه از Laravel Sanctum استفاده می‌کند:

```http
POST /api/login
{
    "email": "user@exchange.com",
    "password": "password"
}

# سپس در همه درخواست‌ها:
Authorization: Bearer YOUR_TOKEN
```

## 🤝 مشارکت

1. Fork کنید
2. Branch جدید ایجاد کنید
3. تغییرات را commit کنید
4. Pull Request ایجاد کنید

## 📄 مجوز

MIT License

---

⭐ اگر پروژه مفید بود، ستاره بدهید!

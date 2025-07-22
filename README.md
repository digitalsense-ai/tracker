# ORB Trading Dashboard – Laravel Project

This is a Laravel-based web dashboard for tracking and managing Opening Range Breakout (ORB) trading setups.  
The system includes components for **forecast**, **breakout**, **retest**, **entry**, and **exit**, and is built for easy expansion and integration with real-time market data.

---

## 📁 Project Structure

```
app/
├── Http/Controllers/DashboardController.php
├── Models/Stock.php

database/
├── migrations/xxxx_xx_xx_create_stocks_table.php
├── seeders/StockSeeder.php

resources/views/
├── dashboard.blade.php
├── partials/
│   ├── breakout.blade.php
│   ├── retest.blade.php
│   ├── entry_exit.blade.php
│   └── strategy-settings.blade.php

routes/
├── web.php
```

---

## 🚀 Getting Started

1. **Clone the repository**
```bash
git clone https://github.com/[YOUR_USERNAME]/tracker.git
cd tracker
```

2. **Install dependencies**
```bash
composer install
npm install && npm run dev
```

3. **Create your environment file**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure the database in `.env`:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=orb_dashboard
DB_USERNAME=root
DB_PASSWORD=your_password
```

5. **Run migrations and seed the database**
```bash
php artisan migrate
php artisan db:seed --class=StockSeeder
```

6. **Start local development server**
```bash
php artisan serve
```

7. **Open in your browser**
```
http://localhost:8000/dashboard
```

---

## 🧠 Features

- ORB lifecycle tracking: forecast → breakout → retest → entry → exit
- Forecast filter configuration with input forms
- Live market data ready (mocked for now)
- Modular views using Laravel Blade components
- Tailwind CSS UI layout

---

## 📈 Roadmap

- Real-time data from Yahoo, TradingView, or Alpaca
- User-based strategy profiles and settings
- Signal alerts via webhook or notifications
- Semi-automatic trading (Saxo Bank / Nordnet integrations)
- Dashboard analytics and trade journaling

---

## 🤝 Contributing

Pull requests and issues are welcome.  
Let’s build something powerful together!

---

Made with ❤️ by Digitalsense + ChatGPT

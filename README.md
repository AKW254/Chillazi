
# 🤖 Smart AI Assistant — Helpdesk & Waiter System

An intelligent conversational AI assistant built in **PHP (with MySQL)** that acts as both a **helpdesk agent** and a **virtual waiter**.  
It can chat naturally with customers, take food orders, confirm them, generate receipts, and manage ongoing conversations — all powered by **LLM (Gemini API)** integration.

---

## 🚀 Features

### 🧠 Conversational AI
- Understands natural language (e.g. “I’d like two milkshakes” or “Get me 3 chicken burgers”).
- Handles both **textual and numeric quantities** (`two` / `2` / `2x` / `cup 2` etc.).
- Learns from conversation flow to provide context-aware replies.

### 🍔 Smart Order Management
- Extracts **food items** and **quantities** from free text.
- Matches orders against a **menu** dynamically (auto-fixes minor spelling mistakes).
- Detects when an order is **confirmed** (e.g. “that’s all”, “just that”, “done”).
- Generates a detailed **receipt** with tax, subtotal, delivery, and payment info.
- Supports **multiple active customers** using `conversation_token`.

### 💬 Helpdesk Mode
- Acts as a helpdesk assistant for general inquiries (e.g. “Where’s my order?” or “What’s on the menu?”).
- Maintains chat context for up to 20 previous messages.
- Handles polite and natural customer engagement.

### 💾 Data Persistence
- Stores all:
  - Customer details  
  - Conversations  
  - Orders and order status  
- Uses MySQL database with prepared statements (secure against SQL injection).

---

## 🧩 Architecture Overview

```text
app/
├── Config/
│   └── config.php              # Database connection & API keys
├── Functions/
│   ├── Gemini.php              # AI request to Gemini API
│   ├── getConversationContext.php # Retrieves conversation + intent
│   ├── detectOrder.php         # Extracts food & quantities
│   ├── processOrder.php        # Saves and totals confirmed orders
│   ├── buildCondensedSystemPrompt.php # AI system prompt context
│   ├── getStaticContext.php    # Loads menu & static info
│   └── getPendingOrderContext.php # Retrieves unconfirmed or past orders
├── public/
│   └── chat-endpoint.php       # Main API endpoint
└── vendor/                     # Composer dependencies
````

---

## 🧮 Example Conversation

**User:**

> I wish to order 2 milkshakes

**AI:**

> Got it! 2 milkshakes for KSH 500. Anything else?

**User:**

> Just that

**AI:**

```
Receipt
Items:
2 x Milkshake @ KSH 250 = KSH 500
Subtotal: KSH 500
Tax (16%): KSH 80
Delivery Fee: KSH 100
Total: KSH 680
PAYMENT: Paybill 90800 | Cash/Card on Delivery
```

---

## 🧠 Core Logic

### 1️⃣ `extractOrderWithMenu()`

Parses user text for food items, detects reversed formats (e.g. “cup 2 coffee”), fuzzy matches names, and maps them to menu prices.

### 2️⃣ `detectConfirmation()`

Checks for confirmation words (“done”, “just that”, “that’s all”) to finalize order.

### 3️⃣ `processOrder()`

Calculates total, applies tax and delivery, saves to database, and generates receipt text.

### 4️⃣ `getConversationContext()`

Loads up to 20 recent messages and isolates **current unconfirmed intent** for continued context.

---

## 🧾 Database Tables (Simplified)

| Table           | Description                                  |
| --------------- | -------------------------------------------- |
| `customers`     | Stores customer name, phone, email           |
| `conversations` | Logs AI ↔ user messages                      |
| `orders`        | Stores structured order data (JSON + totals) |

---

## 🧱 Requirements

* PHP 8.1+
* MySQL 8.0+
* Composer dependencies
* Gemini API Key (or similar LLM endpoint)
* `vendor/autoload.php` enabled

---

## ⚙️ Setup

```bash
git clone https://github.com/YOURUSERNAME/smart-ai-assistant.git
cd smart-ai-assistant
composer install
cp Config/config.example.php Config/config.php
```

Edit `Config/config.php` and set:

```php
$geminiApiKey = 'YOUR_API_KEY';
$mysqli = new mysqli('localhost', 'username', 'password', 'database');
```

Then start your local server:

```bash
php -S localhost:8000 -t public
```

---

## 📈 Pending / Upcoming Features

* [ ] 🗣️ **Speech input** for voice orders
* [ ] 💬 **Multilingual support** (English, Kiswahili, etc.)
* [ ] 🧾 **PDF receipt generation** using DomPDF
* [ ] 🕐 **Real-time order tracking** with status updates
* [ ] 🧍‍♂️ **Staff dashboard** to approve / decline orders
* [ ] 🔔 **Notifications** (Email / WhatsApp) for new orders
* [ ] 🧩 **AI Feedback Loop** — improve intent extraction from real data

---

## 🧑‍💻 Author

**Antony Kilonzo Wambua**
💼 IT Staff & Web Developer
📍 Machakos, Kenya
📧 [kilonzowambua254@gmail.com](mailto:kilonzowambua254@gmail.com)
🔗 [LinkedIn](https://www.linkedin.com/in/antony-wambua-293459265/) | [GitHub](https://github.com/AKW254)

---

## 📝 License

MIT License © 2025 Antony Kilonzo Wambua
Feel free to fork, contribute, and build upon this project!

---

```

---

Would you like me to include **a “demo API call example” (using cURL or Axios)** in the README too, so others can test your assistant easily from Postman or JavaScript frontend?
```

# Chillazi Foodmart Ordering Assistant

This project is an **AI-powered food ordering assistant** for **Chillazi Foodmart**.  
It allows customers to place food orders naturally through conversation. The system tracks order items, asks for confirmation, and generates receipts.

---

## Features

- 🛒 Natural conversation ordering (e.g., *"I want 2 burgers and one juice"*)
- ✅ Confirmation step before finalizing any order
- 🧾 Automatic receipt generation with totals, tax, and delivery fee
- 📦 Pending order handling (one order per customer session)
- 💬 Conversation history stored in database
- 🔍 Supports both numeric and text-based quantities (*"2 burgers"* or *"two burgers"*)

---

## Ordering Flow (Business Rules)

1. **Customer mentions items** (e.g. *"2 juices"*) → Assistant acknowledges.  
   Example:  
   `Got it! 2 juices for KSH 300. Anything else?`

2. **Assistant keeps track of items** until customer confirms.

3. **When customer confirms** (says *"just that"*, *"that's all"*, *"done"*, etc.) →  
   System proceeds to **checkout**.

4. **On checkout**:
   - If a pending DB order exists → finalize and generate receipt for that order.  
   - Otherwise → finalize with items from conversation and generate receipt.

5. **After receipt**, assistant asks for delivery address + payment method.

---


---

## Tech Stack

- **PHP 8+**
- **MySQL** (for customers, conversations, and orders)
- **Gemini AI API** (for conversation responses)
- **AJAX/JSON** (for frontend integration)

---

## Setup

1. Clone the repo:
   ```bash
   git clone https://github.com/your-username/chillazi-foodmart.git
   cd chillazi-foodmart



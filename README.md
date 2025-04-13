# 🧠 Satscribe

**Satscribe** is a PHP app that takes a Bitcoin transaction ID or block height, fetches blockchain data, and generates an AI-written paragraph describing it — using OpenAI's GPT models. It also stores all descriptions in a database for easy reference.

---

## 🚀 Features

- 🔎 Input a **TXID** or **block height**
- 🧠 AI-generated paragraph using GPT-4
- ⛓️ Uses the [Blockstream.info API](https://github.com/Blockstream/esplora/blob/master/API.md) for Bitcoin data
- 💾 Saves each description to the database
- 🗂️ View and paginate all previous descriptions

![Satscribe Demo](docs/demo-homepage.png)

---

## 📦 Requirements

- PHP 8.3+
- Composer
- SQLite
- Laravel 12+
- OpenAI API Key

---

## ⚙️ Installation

```bash
git clone https://github.com/Chemaclass/satscribe.git
cd satscribe

composer install
cp .env.example .env
php artisan key:generate
```
Then configure your .env

```dotenv
DB_CONNECTION=sqlite

OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini
```
And migrate the DB

```bash
php artisan migrate
```

Run the app: `php artisan serve`

# Modular Architecture

The codebase is organized as a set of Laravel modules located in the
[`modules/`](../modules) directory. Every module follows a simple structure:

```
<Module>
├── Application      # Use cases and high level services
├── Domain           # Interfaces and domain data objects
├── Infrastructure   # Controllers, repositories and middleware
└── <Module>ServiceProvider.php
```

The main modules are:

- **Blockchain** – Fetches blockchain information from external services.
- **Chat** – Handles chat creation and messaging.
- **Faq** – Provides FAQ pages and management.
- **OpenAI** – Wraps all interactions with the OpenAI API.
- **Payment** – Deals with invoices and Lightning payments.
- **UtxoTrace** – Generates UTXO traces for transactions.
- **Shared** – Cross‑cutting helpers and middleware used by other modules.

Modules expose their services through a `*ServiceProvider` class which binds
interfaces to concrete implementations. Routes live in `routes/` and reference
controllers under each module's `Infrastructure` folder.

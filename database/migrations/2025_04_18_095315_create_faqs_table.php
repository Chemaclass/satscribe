<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer_beginner');
            $table->text('answer_advance');
            $table->text('answer_tldr');
            $table->string('categories')->default('unknown');
            $table->boolean('highlight')->default(false);
            $table->integer('priority')->default(100); // lower = higher priority
            $table->string('link')->nullable();
            $table->timestamps();
        });

        // Insert initial FAQs
        DB::table('faqs')->insert([
            [
                'question' => 'What is Bitcoin?',
                'answer_beginner' => 'Bitcoin is digital money that you can send and receive over the internet without using a bank or government.',
                'answer_advance' => 'Bitcoin is a digital form of money that works without banks or governments. It allows people anywhere in the world to send and receive payments over the internet directly — securely, quickly, and without needing permission from a central authority. Bitcoin is powered by a public network called the blockchain, which ensures every transaction is verified and recorded.',
                'answer_tldr' => 'A digital currency that works without banks or governments.',
                'categories' => 'basics,crypto',
                'highlight' => true,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What is Proof of Work?',
                'answer_beginner' => 'Proof of Work is a system where miners solve complex puzzles to secure Bitcoin.',
                'answer_advance' => 'Proof of Work is the system that keeps Bitcoin secure and decentralized. It’s a consensus mechanism that requires miners to use powerful computers to solve complex math problems. The first to solve the puzzle earns the right to add the next block to the blockchain and receives a reward. This process makes it very difficult to cheat, helping ensure that all transactions are honest and verified by the network.',
                'answer_tldr' => 'Miners solve puzzles to add blocks and secure the network.',
                'categories' => 'mining,security',
                'highlight' => true,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What is a block?',
                'answer_beginner' => 'A block is a container that holds a group of Bitcoin transactions.',
                'answer_advance' => 'A block is a digital "container" that holds a group of Bitcoin transactions. Once filled, the block is added to the blockchain, which is a public record of all transactions. Each block is linked to the one before it, forming a secure and permanent chain that grows over time.',
                'answer_tldr' => 'A container of Bitcoin transactions added to the blockchain.',
                'categories' => 'basics,blockchain',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What is a transaction?',
                'answer_beginner' => 'A transaction is when someone sends Bitcoin to another person.',
                'answer_advance' => 'A Bitcoin transaction is the process of sending Bitcoin from one wallet address to another. Each transaction is recorded on the blockchain — a public ledger — and includes details like the sender, receiver, and amount (without revealing personal identities). Once confirmed by the network, the transaction becomes permanent and tamper-proof.',
                'answer_tldr' => 'Sending Bitcoin from one address to another.',
                'categories' => 'transactions,basics',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What is a miner?',
                'answer_beginner' => 'A miner uses a computer to confirm Bitcoin transactions and earn rewards.',
                'answer_advance' => 'A miner is a participant in the Bitcoin network who uses computers to verify transactions and add them to the blockchain. To do this, miners solve complex cryptographic puzzles — a process called proof of work. The first miner to solve the puzzle gets to add the next block and earns a Bitcoin reward in return.',
                'answer_tldr' => 'Miners confirm transactions and secure the blockchain.',
                'categories' => 'mining,network',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'How long does it take to confirm a transaction?',
                'answer_beginner' => 'Bitcoin transactions are usually confirmed every 10 minutes.',
                'answer_advance' => 'On average, Bitcoin transactions are confirmed every 10 minutes — that’s how often a new block is added to the blockchain. However, the exact time can vary depending on how busy the network is and how much fee you attach to your transaction. Higher fees are usually confirmed faster, while low-fee transactions may wait longer or even be delayed.',
                'answer_tldr' => 'Typically around 10 minutes, but varies with fees and traffic.',
                'categories' => 'transactions,timing',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What is hash difficulty?',
                'answer_beginner' => 'Hash difficulty measures how hard it is to find a valid block when mining Bitcoin.',
                'answer_advance' => 'It’s how hard the Bitcoin network makes it to "win" the right to add a new block. The more people mining, the harder it gets — so that a block is still found about every 10 minutes.',
                'answer_tldr' => 'Hash difficulty controls how hard it is to mine a new block.',
                'categories' => 'unknown',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Who created Bitcoin?',
                'answer_beginner' => 'Bitcoin was created by someone using the name Satoshi Nakamoto in 2009.',
                'answer_advance' => 'Bitcoin was created by someone using the name Satoshi Nakamoto in 2009. Their true identity is still unknown, but their invention started a global movement for decentralized digital money.',
                'answer_tldr' => 'Satoshi Nakamoto created Bitcoin in 2009.',
                'categories' => 'unknown',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Is Bitcoin real money?',
                'answer_beginner' => 'Yes, Bitcoin can be used to pay for things and transfer value.',
                'answer_advance' => 'Yes. Bitcoin can be used to pay for products, services, or even send money across the world — just like traditional currencies, but digitally and without banks.',
                'answer_tldr' => 'Yes, Bitcoin works as digital money.',
                'categories' => 'unknown',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Is Bitcoin safe?',
                'answer_beginner' => 'Bitcoin’s technology is secure, but users must keep their wallets and keys safe.',
                'answer_advance' => 'Bitcoin’s technology is incredibly secure, thanks to cryptography and a decentralized network of thousands of computers. But like cash, you’re responsible for keeping it safe. If you lose your private keys or fall for a scam, there’s no bank to recover your funds. That’s why using trusted wallets, strong passwords, and good security practices is essential.',
                'answer_tldr' => 'Bitcoin is secure, but your safety depends on how you use it.',
                'categories' => 'unknown',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'How does Bitcoin work?',
                'answer_beginner' => 'Bitcoin uses a network of computers to verify and record transactions.',
                'answer_advance' => 'Bitcoin is a digital currency powered by a global network of computers. When someone sends Bitcoin, the transaction is broadcast to the network, verified by participants, and added to a chain of blocks called the blockchain. This system ensures that transactions are transparent, tamper-proof, and don’t require any central authority to function.',
                'answer_tldr' => 'Bitcoin works using a decentralized network and the blockchain.',
                'categories' => 'unknown',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Do I need a bank to use Bitcoin?',
                'answer_beginner' => 'No. You can use Bitcoin without a bank.',
                'answer_advance' => 'Not at all. Bitcoin was designed to work outside of traditional banking systems. You can create a wallet, store funds, and make payments without needing approval from any institution. This gives people more financial freedom and access, especially in places where banking services are limited or unreliable.',
                'answer_tldr' => 'Bitcoin works without banks — it’s peer-to-peer.',
                'categories' => 'unknown',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Can I buy a coffee with Bitcoin?',
                'answer_beginner' => 'Yes, some shops and cafes accept Bitcoin.',
                'answer_advance' => 'You can in some places! A growing number of cafes, restaurants, and retailers accept Bitcoin, especially in tech-forward cities. You can also use payment apps that instantly convert Bitcoin into local currency at checkout.',
                'answer_tldr' => 'Yes, in some places you can spend Bitcoin directly.',
                'categories' => 'unknown',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What is a cryptocurrency wallet?',
                'answer_beginner' => 'A wallet is a tool to store and use your Bitcoin securely.',
                'answer_advance' => 'A cryptocurrency wallet is a digital tool that lets you store, send, and receive Bitcoin securely. It doesn’t actually hold coins like a physical wallet — instead, it manages the private keys that give you access to your crypto on the blockchain.',
                'answer_tldr' => 'A wallet lets you store and manage your Bitcoin.',
                'categories' => 'unknown',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What makes Bitcoin valuable?',
                'answer_beginner' => 'Bitcoin is valuable because it’s limited and people trust it.',
                'answer_advance' => 'Bitcoin is valuable because it is scarce, secure, and decentralized. Its supply is limited to 21 million coins, making it resistant to inflation. It operates without central control, giving users freedom and transparency. Like gold, its value also grows as more people believe in its usefulness and adopt it.',
                'answer_tldr' => 'Scarcity, trust, and decentralization make Bitcoin valuable.',
                'categories' => 'unknown',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Is Bitcoin legal?',
                'answer_beginner' => 'Yes, Bitcoin is legal in most countries, though rules vary.',
                'answer_advance' => 'While governments can regulate how people access or use Bitcoin (such as through exchanges), they can’t shut it down, because no one controls the network. This resilience and independence are what give Bitcoin its true power and global potential. Bitcoin can be regulated — but not turned off. Its decentralized design makes it unstoppable.',
                'answer_tldr' => 'Bitcoin can be regulated — but not shut down.',
                'categories' => 'unknown',
                'highlight' => false,
                'priority' => 100,
                'link' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};

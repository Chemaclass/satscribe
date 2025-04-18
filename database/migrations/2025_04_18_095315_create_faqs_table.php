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
            $table->text('answer');
            $table->string('categories');
            $table->boolean('highlight')->default(false);
            $table->unsignedInteger('priority')->default(100); // lower = higher priority
            $table->string('link')->nullable();
            $table->timestamps();
        });

        // Insert initial FAQs
        DB::table('faqs')->insert([
            [
                'question' => 'What is Bitcoin?',
                'answer' => 'Bitcoin is a digital form of money that works without banks or governments. It allows people anywhere in the world to send and receive payments over the internet directly — securely, quickly, and without needing permission from a central authority. Bitcoin is powered by a public network called the blockchain, which ensures every transaction is verified and recorded.',
                'categories' => 'basics,crypto',
                'highlight' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What is a block?',
                'answer' => 'A block is a collection of Bitcoin transactions that are bundled and added to the blockchain.',
                'categories' => 'basics,blockchain',
                'highlight' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What is a transaction?',
                'answer' => 'A transaction is the transfer of Bitcoin from one address to another, recorded in a block.',
                'categories' => 'transactions,basics',
                'highlight' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What is a miner?',
                'answer' => 'Miners verify transactions and add them to the blockchain by solving cryptographic puzzles.',
                'categories' => 'mining,network',
                'highlight' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What is Proof of Work?',
                'answer' => 'Proof of Work is a consensus mechanism that requires miners to perform computational work to add blocks.',
                'categories' => 'mining,security',
                'highlight' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'How long does it take to confirm a transaction?',
                'answer' => 'On average, Bitcoin transactions are confirmed every 10 minutes, depending on network congestion and fees.',
                'categories' => 'transactions,timing',
                'highlight' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What is hash difficulty?',
                'answer' => 'Hash difficulty is a measure of how hard it is to find a valid block. It adjusts every 2016 blocks to maintain 10-minute intervals.',
                'categories' => 'mining,difficulty',
                'highlight' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Who created Bitcoin?',
                'answer' => 'Bitcoin was created in 2009 by an anonymous person or group known as Satoshi Nakamoto.',
                'categories' => 'basics',
                'highlight' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Is Bitcoin real money?',
                'answer' => 'Yes, Bitcoin can be used to buy goods and services, just like traditional money.',
                'categories' => 'basics',
                'highlight' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Is Bitcoin safe?',
                'answer' => 'Bitcoin is secure when used correctly, especially with secure wallets and personal responsibility.',
                'categories' => 'basics',
                'highlight' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'How does Bitcoin work?',
                'answer' => 'Bitcoin uses a decentralized network of computers to record and verify transactions using blockchain technology.',
                'categories' => 'basics',
                'highlight' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Do I need a bank to use Bitcoin?',
                'answer' => 'No, Bitcoin can be used without a bank — it’s peer-to-peer.',
                'categories' => 'basics',
                'highlight' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Can I buy a coffee with Bitcoin?',
                'answer' => 'Yes, some stores and cafes accept Bitcoin as payment.',
                'categories' => 'basics',
                'highlight' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What is a cryptocurrency wallet?',
                'answer' => 'A wallet is a digital tool that lets you store and manage your Bitcoin securely.',
                'categories' => 'basics',
                'highlight' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What makes Bitcoin valuable?',
                'answer' => 'Bitcoin\'s value comes from supply and demand, decentralization, and limited supply.',
                'categories' => 'basics',
                'highlight' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Is Bitcoin legal?',
                'answer' => 'Bitcoin is legal in most countries, though regulations vary.',
                'categories' => 'basics',
                'highlight' => false,
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

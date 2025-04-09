![AI Access for PHP](https://github.com/user-attachments/assets/cb8eb6fa-fff5-4378-b14e-9c895b3c10e1)

[![Latest Stable Version](https://poser.pugx.org/aiaccess/ai-access/v/stable)](https://github.com/aiaccess/ai-access/releases) [![Downloads this Month](https://img.shields.io/packagist/dm/aiaccess/ai-access.svg)](https://packagist.org/packages/aiaccess/ai-access)

 <!---->

Unified PHP library providing access to various AI models from different providers (OpenAI, Anthropic Claude, Google Gemini, DeepSeek, Grok (xAI)) through a **single, unified PHP interface**.

<h3>

✅ **Consistent API:** Write your code once, interact with multiple AI vendors.<br>
✅ **Easy Switching:** Swapping between OpenAI, Claude, Gemini, DeepSeek, or Grok is often just a one-line change.<br>
✅ **Simplified Workflow:** Focus on your application logic, not vendor-specific SDKs.<br>
✅ **Modern PHP:** Built with strict types and modern PHP features.

</h3>

 <!---->

Installation
============

Download and install the library using Composer:

```shell
composer require ai-access/ai-access
```

AIAccess requires PHP 8.1 or later.

 <!---->

Initializing the Client
=======================

To start interacting with an AI provider, you first need to create a client instance. The specific class depends on the provider, but the core interface remains consistent.

Get your API keys from the respective providers:

*   **OpenAI:** [OpenAI Platform API Keys](https://platform.openai.com/api-keys)
*   **Anthropic Claude:** [Anthropic Console API Keys](https://console.anthropic.com/settings/keys)
*   **Google Gemini:** [Google AI Studio API Keys](https://aistudio.google.com/app/apikey)
*   **DeepSeek:** [DeepSeek Platform API Keys](https://platform.deepseek.com/api_keys)
*   **Grok (xAI):** [xAI API Console API Keys](https://console.x.ai/team/default/api-keys)

```php
$apiKey = trim(file_get_contents('path/to/your/key.txt'));

// Option 1: OpenAI Client
$client = new AIAccess\OpenAI\Client($apiKey);

// Option 2: Claude Client
$client = new AIAccess\Claude\Client($apiKey);

// Option 3: Gemini Client
$client = new AIAccess\Gemini\Client($apiKey);

// Option 4: DeepSeek Client
$client = new AIAccess\DeepSeek\Client($apiKey);

// Option 5: Grok (xAI) Client
$client = new AIAccess\Grok\Client($apiKey);
```

In larger applications, you would typically configure and retrieve the client instance from a [Dependency Injection container](https://doc.nette.org/en/dependency-injection) instead of creating it directly in your application code.

Now you can use the `$client` variable to interact with the chosen provider's API.

**Key Points:**

*   Choose the correct client class (`OpenAI\Client`, `Claude\Client`, `Gemini\Client`, `DeepSeek\Client`, `Grok\Client`).
*   Provide the corresponding API key during instantiation.
*   The `$client` object now provides access to the provider's features (like `createChat()`, `calculateEmbeddings()`, `createBatch()`) through a unified interface where possible.

All subsequent examples will assume you have a `$client` variable initialized corresponding to your desired provider.

 <!---->

Basic Chat Usage
================

Once you have a `$client` instance, interacting with chat models is straightforward.

```php
// --- Choose a model appropriate for your chosen client ---
// $model = 'gpt-4o-mini';             // OpenAI
// $model = 'claude-3-haiku-20240307'; // Claude
// $model = 'gemini-1.5-flash';        // Gemini
// $model = 'deepseek-chat';           // DeepSeek
// $model = 'grok-3-fast-latest';      // Grok (xAI)

echo "Using Model: " . $model . "\n";

// Assuming $client is initialized as shown in the previous section
$chat = $client->createChat($model);
$prompt = 'Write a short haiku about PHP.';
echo "User: " . $prompt . "\n";

// Send the message and get the response
$response = $chat->sendMessage($prompt);

echo "Model: " . $response->getText() . "\n";
echo "Finish Reason: " . ($response->getFinishReason() ?? 'N/A') . "\n";
echo "Usage Info: ";
print_r($response->getUsage()); // Structure varies by provider
```

**Switching Providers:** As shown in the "Initializing the Client" section, switching providers mainly involves changing the client instantiation line and selecting an appropriate model name for that provider. The chat interaction code itself (`createChat`, `sendMessage`, `getText`, etc.) remains largely consistent.

 <!---->

Conversation History
--------------------

Manage multi-turn conversations easily. You can add messages manually using `addMessage()` or let `sendMessage()` handle adding the user prompt and the model's response to the history automatically.

```php
use AIAccess\Role;

// Assuming $client is initialized and $model is set
$chat = $client->createChat($model);

// Manually add messages to history
$chat->addMessage('What is the capital of France?', Role::User);
$chat->addMessage('The capital of France is Paris.', Role::Model); // Simulate a previous response
$chat->addMessage('What is a famous landmark there?', Role::User); // Add the next question

echo "Current message history count: " . count($chat->getMessages()) . "\n"; // Should be 3
echo "User (last added): What is a famous landmark there?\n";

// Send request based on current history.
// Since the last message was already added, call sendMessage() without arguments.
$response = $chat->sendMessage();

echo "Model: " . $response->getText() . "\n";

// The model's response is automatically added to the history by sendMessage().
echo "Full Conversation History (" . count($chat->getMessages()) . " messages):\n"; // Should be 4
$allMessages = $chat->getMessages();
foreach ($allMessages as $message) {
	echo "[" . $message->getRole()->name . "]: " . $message->getText() . "\n";
}
```

 <!---->

System Instructions
-------------------

Guide the model's overall behavior or persona using a system instruction. This instruction is typically considered by the model throughout the conversation.

```php
$chat->setSystemInstruction('You are a helpful assistant that speaks like a pirate.');
```

 <!---->

Model Options
-------------

Fine-tune the model's response generation for specific requests using the `setOptions()` method on the `Chat` object. These options are provider-specific.

Here's a generic example setting the `temperature` (which controls randomness):

```php
// Set a low temperature for less random output
$chat->setOptions(temperature: 0.1);
```

**Provider-Specific Options (Examples):**

*   **OpenAI** [OpenAI API Reference](https://platform.openai.com/docs/api-reference/chat)
	*   `temperature`: Controls randomness (0-2)
	*   `maxOutputTokens`: Max tokens in the response
	*   `topP`: Nucleus sampling threshold
	*   `tools`: Define functions the model can call
	*   `metadata`: Attach custom key-value data

*   **Claude** [Anthropic API Reference](https://docs.anthropic.com/claude/reference/messages_post)
	*   `temperature`: Controls randomness (0-1)
	*   `maxTokens`: Max tokens to generate (*Note: Different name than others*)
	*   `topK`, `topP`: Alternative sampling methods
	*   `stopSequences`: Specify strings that stop generation

*   **Gemini** [Google AI Gemini API Reference](https://ai.google.dev/api/rest/v1beta/models/generateContent).
	*   `temperature`: Controls randomness (0-1)
	*   `maxOutputTokens`: Max tokens in the response
	*   `topK`, `topP`: Alternative sampling methods
	*   `stopSequences`: Specify strings that stop generation
	*   `safetySettings`: Configure content safety filters

*   **DeepSeek** [DeepSeek API Reference](https://api-docs.deepseek.com/api/create-chat-completion)
	*   `temperature`: Controls randomness (0-2, ignored by `deepseek-reasoner`)
	*   `maxOutputTokens`: Max tokens to generate (`max_tokens`)
	*   `topP`: Nucleus sampling (ignored by `deepseek-reasoner`)
	*   `frequencyPenalty`, `presencePenalty`: Control repetition (ignored by `deepseek-reasoner`)
	*   `stop`: Specify strings that stop generation
	*   `responseFormat`: Request JSON output (`['type' => 'json_object']`)
	*   `tools`: Define functions (not supported by `deepseek-reasoner`)

*   **Grok (xAI)** [xAI API Reference](https://docs.x.ai/docs/api-reference#chat-completions)
	*   `temperature`: Controls randomness (0-2)
	*   `maxOutputTokens`: Max tokens in the response (`max_completion_tokens`)
	*   `topP`: Nucleus sampling threshold
	*   `frequencyPenalty`, `presencePenalty`: Control repetition
	*   `stop`: Specify strings that stop generation
	*   `responseFormat`: Request structured output (`['type' => 'json_object']` or `json_schema`)
	*   `tools`: Define functions
	*   `reasoningEffort`: Control thinking effort for reasoning models (`low`, `high`)
	*   `seed`: Attempt deterministic output

Always refer to the specific `Chat` class implementation (`src/<Vendor>/Chat.php`) or the official vendor documentation for the most up-to-date and complete list of available options.

 <!---->

Batch Processing
================

For processing a large number of independent chat requests asynchronously, often at a lower cost, use the Batch API (supported by OpenAI and Claude). This is ideal when you don't need immediate responses, as processing can take significant time (minutes to potentially 24 hours, depending on the provider and queue load).

**Note:** Grok (xAI), DeepSeek, and Gemini do not currently support a batch API via this library.

**Concept:**
1.  Create batch using `Client::createBatch()`
2.  Create multiple `Chat` objects using `Batch::createChat()`, each configured with its own model, messages, system instructions, and options (using `addMessage`, `setSystemInstruction`, `setOptions` just like interactive chat). Assign a unique `customId` to each.
3.  `submit()` the entire `Batch` container at once. This queues the jobs for background processing. **It does not send messages interactively.**
4.  Store the returned `batchId`.
5.  **Handle Asynchronously:** Use a separate mechanism (cron job, queue worker, webhook) to check the job status later using `retrieveBatch($batchId)`.
6.  Once your checking mechanism confirms the job `status` is `Completed`, use `getOutputMessages()` to get the results, mapped by `customId`.

**Example: Preparing and Submitting the Batch**

```php
use AIAccess\Role;

$model = '...'; // Choose a model compatible with the $client

// 1. Create a batch
$batch = $client->createBatch();

// 2. Add individual chat requests
$chat1 = $batch->createChat($model, 'request-greeting-1');
$chat1->setSystemInstruction('Be brief and friendly.');
$chat1->addMessage('Hi!', Role::User);

$chat2 = $batch->createChat($model, 'request-translate-fr');
$chat2->setSystemInstruction('Translate the user message to French.');
$chat2->addMessage('Hello world', Role::User);

$chat3 = $batch->createChat($model, 'request-code-explain');
$chat3->addMessage('Explain what this PHP code does: `echo "Hello";`', Role::User);

// 3. Submit the batch job
echo "Submitting batch job with 3 requests...\n";
$batchResponse = $batch->submit(); // Returns immediately

$batchId = $batchResponse->getId();
echo "Batch job submitted with ID: " . $batchId . "\n";
echo "Initial status: " . $batchResponse->getStatus()->name . "\n";
```

Now, store the `$batchId` (e.g., in a database, queue message) associated with the task or user who initiated it.

Handling Asynchronous Completion
--------------------------------

You need a separate process (cron, queue worker, etc.) to check the status later using the stored `batchId`.

```php
use AIAccess\BatchStatus;

// --- In your separate checking script/job ---
// $batchIdToCheck = ...; // Retrieve the ID from storage
// $client = ...; // Re-initialize the appropriate client

$currentBatch = $client->retrieveBatch($batchIdToCheck);
$status = $currentBatch->getStatus();

if ($status === BatchStatus::Completed) {
	// Mark job as complete, trigger result processing
	echo "Batch $batchIdToCheck completed.\n";

} elseif ($status === BatchStatus::Failed) {
	// Mark job as failed, log error
	$errorDetails = $currentBatch->getError();
	echo "Batch $batchIdToCheck failed: " . ($errorDetails ?? 'Unknown error') . "\n";

} else { // InProgress or Other
	// Job is still running, check again later based on your schedule
	echo "Batch $batchIdToCheck is still in status: " . $status->name . "\n";
}
```

Retrieve Results (After Confirmation)
-------------------------------------

Once your asynchronous checking mechanism confirms that a batch job's status is `BatchStatus::Completed`, you can retrieve the results. This might happen within the checking job itself or in a separate process triggered upon completion.

```php
// Assuming $currentBatch is the COMPLETED BatchResponse object
echo "Retrieving Results for Completed Batch ID: " . $currentBatch->getId() . " ---\n";

$outputMessages = $currentBatch->getOutputMessages(); // Returns ?array<string, AIAccess\Message>

if ($outputMessages === null) {
	echo "Could not retrieve or parse output messages.\n";
	// Inspect raw result: print_r($currentBatch->getRawResult());
	return;
}

echo "Retrieved " . count($outputMessages) . " results:\n\n";
foreach ($outputMessages as $customId => $message) {
	echo "Result for Request ID: '$customId' ---\n";
	echo $message->getText() . "\n";
	// Process the result
}
```

**Batch API Differences & Abstraction:**

While the underlying mechanisms for batch processing differ significantly between providers, **you don't need to worry about these details when using AIAccess.** The library completely abstracts these differences away. When you call the `$batch->submit()` method:

*   If using the `AIAccess\OpenAI\Client`, the library automatically formats your chat requests into the required JSONL structure, uploads the file to OpenAI, and initiates the batch job using the returned file ID.
*   If using the `AIAccess\Claude\Client`, the library sends the prepared chat payloads directly in the batch creation request.

Thanks to this abstraction, you benefit from a **consistent and simplified workflow** for submitting batch jobs, regardless of the chosen backend provider (among those that support batch).

 <!---->

Embeddings
==========

Embeddings transform text into numerical vectors (arrays of floating-point numbers), capturing semantic meaning. These vectors allow machines to understand relationships between texts. Embeddings are fundamental for tasks like:

*   **Semantic Search:** Find documents relevant by meaning, not just keywords.
*   **Clustering:** Group similar documents together.
*   **Recommendations:** Suggest items based on content similarity.
*   **Retrieval-Augmented Generation (RAG):** Provide relevant context to language models before generating answers.

AIAccess provides a common interface (`calculateEmbeddings`) for generating these vectors using supported providers like OpenAI and Gemini.

**Note:** Claude, DeepSeek, and Grok (xAI) do not currently offer embedding endpoints through this library.

```php
// Assuming $client is initialized (must be OpenAI\Client or Gemini\Client)

// $embeddingModel = 'text-embedding-3-small'; // OpenAI Example
$embeddingModel = 'embedding-001'; // Gemini Example

$textsToEmbed = [
	'The quick brown fox jumps over the lazy dog.',
	'PHP is a popular general-purpose scripting language.',
	'Paris is the capital of France.',
];

echo "Calculating embeddings for " . count($textsToEmbed) . " texts using model: " . $embeddingModel . "\n";

$results = $client->calculateEmbeddings(
	model: $embeddingModel,
	input: $textsToEmbed,
	// Provider-specific options go here as named arguments
);
```

The `calculateEmbeddings()` method returns an array of `AIAccess\Embedding` objects, one for each input text. Each `Embedding` object contains the numerical vector representing the text's semantic meaning. You can then iterate through these results to use the vectors, for example, to calculate similarities or store them for later use.

```php
// Assuming $results is the array returned from calculateEmbeddings
echo "Received " . count($results) . " embeddings.\n";

foreach ($results as $index => $embedding) {
	echo "Embedding for text " . ($index + 1) . ": \"" . $textsToEmbed[$index] . "\"\n";
	$vector = $embedding->getVector();
	echo "Dimension: " . count($vector) . "\n";
	echo "First 5 values: [" . implode(', ', array_slice($vector, 0, 5)) . ", ...]\n";

	// Example: Calculate similarity with the first embedding
	if ($index > 0) {
		$similarity = $results[0]->cosineSimilarity($embedding);
		echo "Cosine Similarity with first text: " . number_format($similarity, 4) . "\n";
	}
}
```

You can serialize embeddings for efficient storage:

```php
use AIAccess\Embedding;

$binaryData = $results[0]->serialize();
// Store $binaryData in a database (e.g., BLOB column)

// Later, retrieve and deserialize:
$embeddingObject = Embedding::deserialize($retrievedBinaryData);
$vector = $embeddingObject->getVector();
```

**Embedding API Options:**

Pass these as additional named arguments to `calculateEmbeddings` when using the specific client:

*   **OpenAI** [OpenAI Embeddings API Reference](https://platform.openai.com/docs/api-reference/embeddings/create)
	*   `dimensions` (int): Optional. Request specific vector size (e.g., 256) for `text-embedding-3-*` models.

*   **Gemini** [Google AI Gemini API Reference (batchEmbedContents)](https://ai.google.dev/api/rest/v1beta/models/batchEmbedContents)
	*   `taskType` (string): Optional. Hint for use case (e.g., `RETRIEVAL_QUERY`, `RETRIEVAL_DOCUMENT`).
	*   `title` (string): Optional. Title when `taskType` is `RETRIEVAL_DOCUMENT`.
	*   `outputDimensionality` (int): Optional. Request specific dimensions.

 <!---->

Error Handling
==============

The library uses specific exceptions for robust error handling.

*   **`AIAccess\Exception`**: Base interface.
*   **`AIAccess\LogicException`**: Incorrect library usage (your code's logic).
*   **`AIAccess\ApiException`**: Error returned by the provider's API (invalid key, rate limit, etc.). Check `$e->getCode()` for HTTP status.
*   **`AIAccess\NetworkException`**: Connection problem (DNS, timeout, SSL).

```php
try {
	$chat = $client->createChat($model);
	$response = $chat->sendMessage('Tell me a story about a brave toaster.');
	echo "Model: " . $response->getText() . "\n";

} catch (AIAccess\ApiException $e) {
	// Handle API-specific errors
	echo "API Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";

} catch (AIAccess\NetworkException $e) {
	// Handle connection problems
	echo "Network Error: " . $e->getMessage() . "\n";

} catch (AIAccess\LogicException $e) {
	// Handle errors in your usage of the library
	echo "Logic Error: " . $e->getMessage() . "\n";

} catch (AIAccess\Exception $e) {
	// Catch any other library-specific exception
	echo "General AIAccess Error: " . $e->getMessage() . "\n";

} catch (Throwable $e) {
	// Catch any unexpected PHP error
	echo "An unexpected error occurred: " . $e->getMessage() . "\n";
}
```

Catch specific exceptions first.

 <!---->

[Support Me](https://github.com/sponsors/dg)
============

Do you like AI Access? Are you looking forward to new features?

[![Buy me a coffee](https://files.nette.org/icons/donation-3.svg)](https://github.com/sponsors/dg)

Thank you!

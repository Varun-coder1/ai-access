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

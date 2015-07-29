# NZBCX Bitcoin Exchange API PHP wrapper

This is a PHP wrapper library for the [NZBCX API][1]

## Installation
Install using [Composer][2].

    composer require haydenw/nzbcx-api

## Authentication

We recommend using [Dotenv][3] to store sensitive API data. Create a `.env` file in the same directory as your code like the below: (you can rename and modify the `.env.example` file)

```
NZBCX_API_USER_ID=1234567
NZBCX_API_KEY=W8X7nzwDxifU8HYo56TzSXqVRhAFhsUc8RWo
NZBCX_API_SECRET=PDmYdflwnE4jQhdvnlDkK3gBQ0E1qmSdX0sL
```

See `example.php` for code usage.

Alternatively you can modify `$apiKey`, `$apiSecret` and `$userId` and hard code the values.

## Basic usage

```php
$nzbcx = new NZBCX($apiKey, $apiSecret, $userId, true); // Create NZBCX instance in test mode

// Make calls to NZBCX functions and use the results returned
$accountBalance = $nzbcx->accountBalance();
if ($accountBalance) {
	echo "BTC Balance: ".$accountBalance->BTC_balance."\n";
	echo "NZD Balance: ".$accountBalance->NZD_balance."\n";
}
```

[1]: https://nzbcx.com/docs/api
[2]: https://getcomposer.org/
[3]: https://github.com/vlucas/phpdotenv
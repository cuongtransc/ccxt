<?php

namespace ccxt;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import

class probit extends Exchange {

    public function describe () {
        return array_replace_recursive (parent::describe (), array (
            'id' => 'probit',
            'name' => 'ProBit',
            'countries' => ['SC', 'KR'],
            'rateLimit' => 250, // ms
            'has' => array (
                'CORS' => true,
                'fetchTime' => true,
                'fetchMarkets' => true,
                'fetchCurrencies' => true,
                'fetchTickers' => true,
                'fetchTicker' => true,
                'fetchOHLCV' => true,
                'fetchOrderBook' => true,
                'fetchTrades' => true,
                'fetchBalance' => true,
                'createOrder' => true,
                'createLimitOrder' => true,
                'createMarketOrder' => true,
                'cancelOrder' => true,
                'fetchOrder' => true,
                'fetchOpenOrders' => true,
                'fetchClosedOrders' => true,
                'fetchMyTrades' => true,
                'fetchDepositAddress' => true,
                'withdraw' => true,
            ),
            'timeframes' => array (
                '1m' => '1m',
                '3m' => '3m',
                '5m' => '5m',
                '10m' => '10m',
                '15m' => '15m',
                '30m' => '30m',
                '1h' => '1h',
                '4h' => '4h',
                '6h' => '6h',
                '12h' => '12h',
                '1d' => '1D',
                '1w' => '1W',
            ),
            'version' => 'v1',
            'urls' => array (
                'logo' => 'https://static.probit.com/landing/assets/images/probit-logo-global.png',
                'api' => array (
                    'account' => 'https://accounts.probit.com',
                    'exchange' => 'https://api.probit.com/api/exchange',
                ),
                'www' => 'https://www.probit.com',
                'doc' => array (
                    'https://docs-en.probit.com',
                    'https://docs-ko.probit.com',
                ),
                'fees' => 'https://support.probit.com/hc/en-us/articles/360020968611-Trading-Fees',
            ),
            'api' => array (
                'public' => array (
                    'get' => ['market', 'currency', 'time', 'ticker', 'order_book', 'trade', 'candle'],
                ),
                'private' => array (
                    'post' => ['new_order', 'cancel_order', 'withdrawal'],
                    'get' => ['balance', 'order', 'open_order', 'order_history', 'trade_history', 'deposit_address'],
                ),
                'auth' => array (
                    'post' => ['token'],
                ),
            ),
            'fees' => array (
                'trading' => array (
                    'tierBased' => false,
                    'percentage' => true,
                    'maker' => 0.2 / 100,
                    'taker' => 0.2 / 100,
                ),
            ),
            'exceptions' => array (
                'INVALID_ARGUMENT' => '\\ccxt\\BadRequest',
                'TRADING_UNAVAILABLE' => '\\ccxt\\ExchangeNotAvailable',
                'NOT_ENOUGH_BALANCE' => '\\ccxt\\InsufficientFunds',
                'NOT_ALLOWED_COMBINATION' => '\\ccxt\\BadRequest',
                'INVALID_ORDER' => '\\ccxt\\InvalidOrder',
            ),
            'requiredCredentials' => array (
                'apiKey' => true,
                'secret' => true,
            ),
            'options' => array (
                'defaultLimitOrderTimeInForce' => 'gtc',
                'defaultMarketOrderTimeInForce' => 'ioc',
            ),
        ));
    }

    public function fetch_markets ($params = array ()) {
        $response = $this->publicGetMarket ();
        $markets = $this->safe_value($response, 'data');
        $result = array();
        for ($i = 0; $i < count ($markets); $i++) {
            $market = $markets[$i];
            $base = $market['base_currency_id'];
            $quote = $market['quote_currency_id'];
            $symbol = $base . '/' . $quote;
            $closed = $market['closed'];
            $result[] = array (
                'id' => $market['id'],
                'symbol' => $symbol,
                'base' => $base,
                'quote' => $quote,
                'baseId' => $base,
                'quoteId' => $quote,
                'active' => !$closed,
                'precision' => array (
                    'amount' => $market['quantity_precision'],
                    'cost' => $market['cost_precision'],
                ),
                'limits' => array (
                    'amount' => array (
                        'min' => $market['min_quantity'],
                        'max' => $market['max_quantity'],
                    ),
                    'price' => array (
                        'min' => $market['min_price'],
                        'max' => $market['max_price'],
                    ),
                    'cost' => array (
                        'min' => $market['min_cost'],
                        'max' => $market['max_cost'],
                    ),
                ),
                'info' => $market,
            );
        }
        return $result;
    }

    public function fetch_currencies ($params = array ()) {
        $response = $this->publicGetCurrency ();
        $currencies = $this->safe_value($response, 'data');
        $result = array();
        for ($i = 0; $i < count ($currencies); $i++) {
            $currency = $currencies[$i];
            $code = $this->safe_currency_code($currency['id']);
            $result[$code] = array (
                'id' => $code,
                'code' => $code,
                'name' => $currency['name'],
                'info' => $currency,
                'precision' => $currency['precision'],
            );
        }
        return $result;
    }

    public function fetch_balance ($params = array ()) {
        $this->load_markets();
        $response = $this->privateGetBalance ();
        $balances = $this->safe_value($response, 'data');
        $result = array( 'info' => $balances );
        for ($i = 0; $i < count ($balances); $i++) {
            $balance = $balances[$i];
            $currencyId = $balance['currency_id'];
            $account = $this->account ();
            $total = $this->safe_float($balance, 'total');
            $available = $this->safe_float($balance, 'available');
            $hold = $this->sum ($total, -$available);
            $account['total'] = $total;
            $account['free'] = $available;
            $account['used'] = $hold;
            $result[$currencyId] = $account;
        }
        return $this->parse_balance($result);
    }

    public function fetch_order_book ($symbol, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array (
            'market_id' => $market['id'],
        );
        $response = $this->publicGetOrderBook (array_merge ($request, $params));
        $orderbook = $this->safe_value($response, 'data');
        return $this->parse_order_book($orderbook, null, 'buy', 'sell', 'price', 'quantity');
    }

    public function parse_order_book ($orderbook, $timestamp = null, $bidsKey = 'buy', $asksKey = 'sell', $priceKey = 'price', $amountKey = 'quantity') {
        $bids = array();
        $asks = array();
        for ($i = 0; $i < count ($orderbook); $i++) {
            $item = $orderbook[$i];
            if ($item['side'] === $bidsKey) {
                $bids[] = $item;
            } else if ($item['side'] === $asksKey) {
                $asks[] = $item;
            }
        }
        return array (
            'bids' => $this->sort_by($this->parse_bids_asks($bids, $priceKey, $amountKey), 0, true),
            'asks' => $this->sort_by($this->parse_bids_asks($asks, $priceKey, $amountKey), 0),
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'nonce' => null,
        );
    }

    public function fetch_tickers ($symbols = null, $params = array ()) {
        $this->load_markets();
        $marketIds = array();
        for ($i = 0; $i < count ($symbols); $i++) {
            $market = $this->market ($symbols[$i]);
            $marketIds[] = $market['id'];
        }
        $request = array (
            'market_ids' => implode(',', $marketIds),
        );
        $response = $this->publicGetTicker (array_merge ($request, $params));
        return $this->parse_tickers ($response['data'], $symbols);
    }

    public function parse_tickers ($rawTickers, $symbols = null) {
        $tickers = array();
        for ($i = 0; $i < count ($rawTickers); $i++) {
            $tickers[] = $this->parse_ticker($rawTickers[$i]);
        }
        return $this->filter_by_array($tickers, 'symbol', $symbols);
    }

    public function fetch_ticker ($symbol, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array (
            'market_ids' => $market['id'],
        );
        $response = $this->publicGetTicker (array_merge ($request, $params));
        return $this->parse_ticker($this->safe_value($response, 'data')[0], $market);
    }

    public function parse_ticker ($ticker, $market = null) {
        $timestamp = $this->parse8601 ($this->safe_string($ticker, 'time'));
        $symbol = $this->find_symbol($this->safe_string($ticker, 'market_id'), $market);
        $last = $this->safe_float($ticker, 'last');
        return array (
            'close' => $last,
            'last' => $last,
            'low' => $this->safe_float($ticker, 'low'),
            'high' => $this->safe_float($ticker, 'high'),
            'change' => $this->safe_float($ticker, 'change'),
            'baseVolume' => $this->safe_float($ticker, 'base_volume'),
            'quoteVolume' => $this->safe_float($ticker, 'quote_volume'),
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'info' => $ticker,
            'bid' => null,
            'bidVolume' => null,
            'ask' => null,
            'askVolume' => null,
            'vwap' => null,
            'open' => null,
            'previousClose' => null,
            'percentage' => null,
            'average' => null,
        );
    }

    public function fetch_my_trades ($symbol = null, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = null;
        $request = array (
            'limit' => 100,
            'start_time' => $this->iso8601 (0),
            'end_time' => $this->iso8601 ($this->milliseconds ()),
        );
        if ($symbol !== null) {
            $market = $this->market ($symbol);
            $request['market_id'] = $market['id'];
        }
        if ($since !== null) {
            $request['start_time'] = $this->iso8601 ($since);
        }
        if ($limit !== null) {
            $request['limit'] = $limit;
        }
        $response = $this->privateGetTradeHistory (array_merge ($request, $params));
        return $this->parse_trades($response['data'], $market, $since, $limit);
    }

    public function fetch_trades ($symbol, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array (
            'market_id' => $market['id'],
            'limit' => 100,
            'start_time' => $this->iso8601 (0),
            'end_time' => $this->iso8601 ($this->milliseconds ()),
        );
        if ($since !== null) {
            $request['start_time'] = $this->iso8601 ($since);
        }
        if ($limit !== null) {
            $request['limit'] = $limit;
        }
        $response = $this->publicGetTrade (array_merge ($request, $params));
        return $this->parse_trades($response['data'], $market, $since, $limit);
    }

    public function parse_trade ($trade, $market = null) {
        $time = $this->safe_string($trade, 'time');
        $timestamp = $this->parse8601 ($time);
        $symbol = $this->safe_string($market, 'symbol');
        $fee = null;
        $feeAmount = $this->safe_float($trade, 'fee_amount');
        if ($feeAmount !== null && $feeAmount !== 0) {
            $fee = array (
                'currency' => $this->safe_string($trade, 'fee_currency_id'),
                'cost' => $feeAmount,
            );
        }
        return array (
            'id' => $this->safe_string($trade, 'id'),
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'symbol' => $symbol,
            'order' => $this->safe_string($trade, 'order_id'),
            'type' => null,
            'side' => $this->safe_string($trade, 'side'),
            'takerOrMaker' => null,
            'price' => $this->safe_float($trade, 'price'),
            'amount' => $this->safe_float($trade, 'quantity'),
            'cost' => $this->safe_float($trade, 'cost'),
            'fee' => $fee,
            'info' => $trade,
        );
    }

    public function fetch_time ($params = array ()) {
        $response = $this->publicGetTime ();
        $timestamp = $this->parse8601 ($this->safe_string($response, 'data'));
        return $timestamp;
    }

    public function normalize_candle_timestamp ($timestamp, $timeframe) {
        $coeff = intval (mb_substr($timeframe, 0, -1 - 0), 10);
        $unitLetter = mb_substr($timeframe, -1);
        $m = 60 * 1000;
        $h = 60 * $m;
        $D = 24 * $h;
        $W = 7 * $D;
        $units = array (
            'm' => $m,
            'h' => $h,
            'D' => $D,
            'W' => $W,
        );
        $unit = $units[$unitLetter];
        $mod = $coeff * $unit;
        $diff = (fmod($timestamp, $mod));
        $timestamp = $timestamp - $diff;
        if ($unit === $W) {
            $timestamp = $timestamp . 3 * $D;
        }
        return $timestamp;
    }

    public function fetch_ohlcv ($symbol, $timeframe = '1m', $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $interval = $this->timeframes[$timeframe];
        if (!$interval) {
            throw new NotSupported('Timeframe ' . $timeframe . ' is not supported.');
        }
        $request = array (
            'market_ids' => $market['id'],
            'interval' => $interval,
            'start_time' => $this->iso8601 ($this->normalize_candle_timestamp (0, $interval)),
            'end_time' => $this->iso8601 ($this->normalize_candle_timestamp ($this->milliseconds (), $interval)),
            'sort' => 'desc',
            'limit' => 100,
        );
        if ($since !== null) {
            $request['start_time'] = $this->iso8601 ($this->normalize_candle_timestamp ($since, $interval));
        }
        if ($limit !== null) {
            $request['limit'] = $limit;
        }
        $response = $this->publicGetCandle (array_merge ($request, $params));
        $data = $this->safe_value($response, 'data');
        return $this->parse_ohlcvs($data, $market, $timeframe, $since, $limit);
    }

    public function parse_ohlcv ($ohlcv, $market = null, $timeframe = '1m', $since = null, $limit = null) {
        return array (
            floatval ($this->parse8601 ($this->safe_string($ohlcv, 'start_time'))),
            floatval ($this->safe_float($ohlcv, 'open')),
            floatval ($this->safe_float($ohlcv, 'high')),
            floatval ($this->safe_float($ohlcv, 'low')),
            floatval ($this->safe_float($ohlcv, 'close')),
            floatval ($this->safe_float($ohlcv, 'base_volume')),
        );
    }

    public function fetch_open_orders ($symbol = null, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $since = $this->parse8601 ($since);
        $request = array();
        if ($symbol) {
            $request['market_id'] = $this->market_id($symbol);
        }
        $resp = $this->privateGetOpenOrder (array_merge ($request, $params));
        $orders = $this->safe_value($resp, 'data');
        $arr = array();
        for ($i = 0; $i < count ($orders); $i++) {
            if ($since && $this->parse8601 ($orders[$i]['time']) < $since) {
                continue;
            }
            $symbol = $this->find_symbol($this->safe_string($orders[$i], 'market_id'));
            $arr[] = $this->parse_order($orders[$i], $symbol);
        }
        // order by desc
        $arr = $this->sort_by($arr, 'timestamp', true);
        if ($limit) {
            $arr = mb_substr($arr, 0, $limit - 0);
        }
        return $arr;
    }

    public function fetch_closed_orders ($symbol = null, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $request = array (
            'start_time' => $this->iso8601 (0),
            'end_time' => $this->iso8601 ($this->milliseconds ()),
            'limit' => 100,
        );
        if ($symbol) {
            $request['market_id'] = $this->market_id($symbol);
        }
        if ($since) {
            $request['start_time'] = $this->iso8601 ($since);
        }
        if ($limit) {
            $request['limit'] = $limit;
        }
        $resp = $this->privateGetOrderHistory (array_merge ($request, $params));
        $orders = $this->safe_value($resp, 'data');
        $arr = array();
        for ($i = 0; $i < count ($orders); $i++) {
            $symbol = $this->find_symbol($this->safe_string($orders[$i], 'market_id'));
            $arr[] = $this->parse_order($orders[$i], $symbol);
        }
        return $arr;
    }

    public function fetch_order ($id, $symbol = null, $params = array ()) {
        if ($symbol === null) {
            throw new ArgumentsRequired($this->id . ' fetchOrder requires a $symbol argument');
        }
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array (
            'market_id' => $market['id'],
            'order_id' => (string) $id,
        );
        $clientOrderId = $this->safe_string($params, 'clientOrderId');
        if ($clientOrderId) {
            $request['client_order_id'] = $clientOrderId;
        }
        $response = $this->privateGetOrder (array_merge ($request, $params));
        $order = $this->safe_value($response, 'data')[0];
        return $this->parse_order($order, $symbol);
    }

    public function parse_order ($order, $symbol) {
        $status = $order['status'];
        if ($status === 'filled') {
            $status = 'closed';
        }
        $time = $order['time'];
        $filledCost = $this->safe_float($order, 'filled_cost');
        $filledQty = $this->safe_float($order, 'filled_quantity');
        $openQty = $this->safe_float($order, 'open_quantity');
        $qty = $this->safe_float($order, 'quantity');
        $price = $this->safe_float($order, 'limit_price');
        if ($order['type'] === 'market') {
            $qty = $this->sum ($filledQty, $openQty);
            if ($filledCost > 0 && $filledQty > 0) {
                $price = $filledCost / $filledQty;
            }
        }
        return array (
            'id' => $order['id'],
            'symbol' => $symbol,
            'type' => $order['type'],
            'side' => $order['side'],
            'datetime' => $time,
            'timestamp' => $this->parse8601 ($time),
            'lastTradeTimestamp' => null,
            'status' => $status,
            'price' => $price,
            'amount' => $qty,
            'filled' => $filledQty,
            'remaining' => $openQty,
            'cost' => $filledCost,
            'info' => $order,
        );
    }

    public function create_order ($symbol, $type, $side, $amount, $price = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $req = array (
            'market_id' => $market['id'],
            'type' => $type,
            'side' => $side,
        );
        $clientOrderId = $this->safe_string($params, 'clientOrderId');
        if ($clientOrderId) {
            $req['client_order_id'] = $clientOrderId;
        }
        $timeInForce = $this->safe_string($params, 'timeInForce');
        if ($type === 'limit') {
            if (!$timeInForce) {
                $timeInForce = $this->options['defaultLimitOrderTimeInForce'];
            }
            $req['time_in_force'] = $timeInForce;
            $req['limit_price'] = $this->price_to_precision($symbol, $price);
            $req['quantity'] = $this->amount_to_precision($symbol, $amount);
        } else if ($type === 'market') {
            if (!$timeInForce) {
                $timeInForce = $this->options['defaultMarketOrderTimeInForce'];
            }
            $req['time_in_force'] = $timeInForce;
            if ($side === 'sell') {
                $req['quantity'] = $this->amount_to_precision($symbol, $amount);
            } else if ($side === 'buy') {
                $req['cost'] = $this->cost_to_precision($symbol, $amount);
            }
        }
        $resp = $this->privatePostNewOrder (array_merge ($req, $params));
        return $this->parse_order($this->safe_value($resp, 'data'), $symbol);
    }

    public function cancel_order ($id, $symbol = null, $params = array ()) {
        $market = $this->market ($symbol);
        $request = array (
            'market_id' => $market['id'],
            'order_id' => (string) $id,
        );
        $resp = $this->privatePostCancelOrder (array_merge ($request, $params));
        return $this->parse_order($this->safe_value($resp, 'data'));
    }

    public function parse_deposit_address ($depositAddress, $currency = null) {
        $address = $this->safe_string($depositAddress, 'address');
        $tag = $this->safe_string($depositAddress, 'destination_tag');
        $currencyId = $this->safe_string($depositAddress, 'currency_id');
        $code = $this->safe_currency_code($currencyId);
        $this->check_address($address);
        return array (
            'currency' => $code,
            'address' => $address,
            'tag' => $tag,
            'info' => $depositAddress,
        );
    }

    public function create_deposit_address ($code, $params = array ()) {
        return $this->fetch_deposit_address ($code, $params);
    }

    public function fetch_deposit_address ($code, $params = array ()) {
        $this->load_markets();
        $currency = $this->currency ($code);
        $request = array (
            'currency_id' => $currency['id'],
        );
        $response = $this->privateGetDepositAddress (array_merge ($request, $params));
        return $this->parse_deposit_address ($response['data'][0]);
    }

    public function fetch_deposit_addresses ($codes = null, $params = array ()) {
        $this->load_markets();
        $request = array();
        if ($codes) {
            $currencyIds = array();
            for ($i = 0; $i < count ($codes); $i++) {
                $currency = $this->currency ($codes[$i]);
                $currencyIds[] = $currency['id'];
            }
            $request['currency_id'] = implode(',', $codes);
        }
        $response = $this->privateGetDepositAddress (array_merge ($request, $params));
        return $this->parse_deposit_addresses ($response['data']);
    }

    public function parse_deposit_addresses ($rawAddresses) {
        $addresses = array();
        for ($i = 0; $i < count ($rawAddresses); $i++) {
            $addresses[] = $this->parse_deposit_address ($rawAddresses[$i]);
        }
        return $addresses;
    }

    public function withdraw ($code, $amount, $address, $tag = null, $params = array ()) {
        $this->check_address($address);
        $this->load_markets();
        $currency = $this->currency ($code);
        if (!$tag) {
            $tag = null;
        }
        $request = array (
            'currency_id' => $currency['id'],
            'address' => $address,
            'destination_tag' => $tag,
            'amount' => $this->number_to_string($amount),
        );
        $response = $this->privatePostWithdrawal (array_merge ($request, $params));
        return $this->parse_transaction($response['data']);
    }

    public function parse_transaction ($transaction, $currency = null) {
        $id = $this->safe_string($transaction, 'id');
        $amount = $this->safe_float($transaction, 'amount');
        $address = $this->safe_string($transaction, 'address');
        $tag = $this->safe_string($transaction, 'destination_tag');
        $txid = $this->safe_string($transaction, 'hash');
        $timestamp = $this->parse8601 ($this->safe_string($transaction, 'time'));
        $type = $this->safe_string($transaction, 'type');
        $currencyId = $this->safe_string($transaction, 'currency_id');
        $code = $this->safe_currency_code($currencyId);
        $status = $this->parse_transaction_status ($this->safe_string($transaction, 'status'));
        $feeCost = $this->safe_float($transaction, 'fee');
        $fee = null;
        if ($feeCost !== null && $feeCost !== 0) {
            $fee = array (
                'currency' => $code,
                'cost' => $feeCost,
            );
        }
        return array (
            'id' => $id,
            'currency' => $code,
            'amount' => $amount,
            'address' => $address,
            'tag' => $tag,
            'status' => $status,
            'type' => $type,
            'txid' => $txid,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'fee' => $fee,
            'info' => $transaction,
        );
    }

    public function parse_transaction_status ($status) {
        $statuses = array (
            'requested' => 'pending',
            'pending' => 'pending',
            'confirming' => 'pending',
            'confirmed' => 'pending',
            'applying' => 'pending',
            'done' => 'ok',
            'cancelled' => 'canceled',
            'cancelling' => 'canceled',
        );
        return $this->safe_string($statuses, $status, $status);
    }

    public function nonce () {
        return $this->milliseconds ();
    }

    public function sign ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $url = $this->urls['api']['exchange'] . '/' . $this->version . '/';
        if ($api === 'auth') {
            $url = $this->urls['api']['account'] . '/';
        }
        $query = $this->omit ($params, $this->extract_params($path));
        if ($api === 'public') {
            $url .= $this->implode_params($path, $params);
            if ($query) {
                $url .= '?' . $this->urlencode ($query);
            }
        } else if ($api === 'private') {
            $this->check_required_credentials();
            $expires = $this->safe_integer($this->options, 'expires');
            if (!$expires || $expires < $this->milliseconds ()) {
                throw new AuthenticationError($this->id . ' accessToken expired, call signIn() method');
            }
            $url .= $this->implode_params($path, $params);
            if ($method === 'GET') {
                if ($query) {
                    $url .= '?' . $this->urlencode ($query);
                }
            } else if ($query) {
                $body = $this->json ($query);
            }
            $headers = array (
                'Authorization' => 'Bearer ' . $this->options['accessToken'],
                'Content-Type' => 'application/json',
            );
        } else if ($api === 'auth') {
            $this->check_required_credentials();
            $url .= $this->implode_params($path, $params);
            $encoded = $this->encode ($this->apiKey . ':' . $this->secret);
            $basicAuth = base64_encode ($encoded);
            $headers = array (
                'Authorization' => 'Basic ' . $this->decode ($basicAuth),
                'Content-Type' => 'application/json',
            );
            if ($method === 'GET') {
                if ($query) {
                    $url .= '?' . $this->urlencode ($query);
                }
            } else if ($query) {
                $body = $this->json ($query);
            }
        }
        return array( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }

    public function sign_in ($params = array ()) {
        $this->check_required_credentials();
        if (!$this->apiKey || !$this->secret) {
            throw new AuthenticationError($this->id . ' signIn() requires $this->apiKey and $this->secret credentials');
        }
        $body = array (
            'grant_type' => 'client_credentials',
        );
        $tokenResponse = $this->authPostToken ($body);
        $expiresIn = $this->safe_integer($tokenResponse, 'expires_in');
        $accessToken = $this->safe_string($tokenResponse, 'access_token');
        $this->options['accessToken'] = $accessToken;
        $this->options['expires'] = $this->sum ($this->milliseconds (), $expiresIn * 1000);
        $this->options['tokenType'] = $this->safe_string($tokenResponse, 'token_type');
        return $tokenResponse;
    }

    public function handle_errors ($httpCode, $reason, $url, $method, $headers, $body, $response) {
        if ($response === null) {
            return; // fallback to default error handler
        }
        if (is_array($response) && array_key_exists('errorCode', $response)) {
            $errorCode = $this->safe_string($response, 'errorCode');
            $message = $this->safe_string($response, 'message');
            if ($errorCode !== null) {
                $feedback = $this->json ($response);
                $exceptions = $this->exceptions;
                if (is_array($exceptions) && array_key_exists($message, $exceptions)) {
                    throw new $exceptions[$message]($feedback);
                } else if (is_array($exceptions) && array_key_exists($errorCode, $exceptions)) {
                    throw new $exceptions[$errorCode]($feedback);
                } else {
                    throw new ExchangeError($feedback);
                }
            }
        }
    }
}
